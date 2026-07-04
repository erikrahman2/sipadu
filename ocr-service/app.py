#!/usr/bin/env python3
"""
PA-Disdukcapil OCR Microservice
================================
Flask REST API wrapping Tesseract OCR 5.x with OpenCV preprocessing.

Endpoints:
  POST /ocr/process      – process an uploaded image/PDF
  GET  /health           – health check
  GET  /version          – engine info
"""

import sys
import io
import json
import logging
import os
import re
import time
import hashlib
import shutil
from functools import wraps
from pathlib import Path

import cv2
import numpy as np
import pytesseract
from flask import Flask, jsonify, request
try:
    import fitz  # PyMuPDF - self-contained PDF library
    PDF_LIBRARY = "fitz"
except ImportError:
    try:
        from pdf2image import convert_from_bytes  # Fallback to pdf2image
        PDF_LIBRARY = "pdf2image"
    except ImportError:
        PDF_LIBRARY = None

from PIL import Image
from dotenv import load_dotenv

# Load local .env for standalone runs (non-Docker) so secret and paths are consistent.
load_dotenv(dotenv_path=Path(__file__).with_name('.env'))

# ─────────────────────────────────────────────────────────────────────────────
# Valid Indonesia Province Codes
# ─────────────────────────────────────────────────────────────────────────────
VALID_PROVINCE_CODES = set(str(i).zfill(2) for i in range(1, 35))

# ─────────────────────────────────────────────────────────────────────────────
# Configuration
# ─────────────────────────────────────────────────────────────────────────────

app = Flask(__name__)
app.config.update(
    MAX_CONTENT_LENGTH = 10 * 1024 * 1024,  # 10 MB
    OCR_SECRET_KEY     = os.getenv("OCR_SECRET_KEY", "change_me"),
    TESSERACT_CMD      = os.getenv("TESSERACT_CMD", "tesseract"),
    TESSDATA_PREFIX    = os.getenv("TESSDATA_PREFIX", ""),
    OCR_LANG           = os.getenv("OCR_LANG", "ind+eng"),
    OCR_PSM_CANDIDATES = os.getenv("OCR_PSM_CANDIDATES", "6,4,11,8,13"),  # PSM 6 primary, expanded modes
    UPLOAD_DPI         = int(os.getenv("UPLOAD_DPI", "300")),
    LOG_LEVEL          = os.getenv("LOG_LEVEL", "INFO"),
)

logging.basicConfig(
    level=getattr(logging, app.config["LOG_LEVEL"]),
    format="%(asctime)s [%(levelname)s] %(name)s – %(message)s",
)
logger = logging.getLogger("ocr-service")

# Resolve Tesseract command with Windows fallback to reduce deployment mismatch.
if (
    app.config["TESSERACT_CMD"] == "tesseract"
    and os.name == "nt"
    and Path(r"C:\Program Files\Tesseract-OCR\tesseract.exe").exists()
):
    app.config["TESSERACT_CMD"] = r"C:\Program Files\Tesseract-OCR\tesseract.exe"

pytesseract.pytesseract.tesseract_cmd = app.config["TESSERACT_CMD"]
if app.config["TESSDATA_PREFIX"]:
    os.environ["TESSDATA_PREFIX"] = app.config["TESSDATA_PREFIX"]

ACCEPTED_MIMES = {"image/jpeg", "image/png", "image/tiff", "application/pdf"}

# ─────────────────────────────────────────────────────────────────────────────
# PDF Library Check
# ─────────────────────────────────────────────────────────────────────────────

if PDF_LIBRARY is None:
    logger.warning("No PDF library available. PDF processing will fail.")
elif PDF_LIBRARY == "fitz":
    logger.info("Using PyMuPDF (fitz) for PDF processing")
else:
    logger.info("Using pdf2image for PDF processing")


def check_ocr_runtime() -> tuple[bool, dict]:
    """Validate Tesseract availability and required language packs."""
    try:
        version = str(pytesseract.get_tesseract_version())
    except Exception as e:
        return False, {
            "error": str(e),
            "tesseract_cmd": app.config["TESSERACT_CMD"],
        }

    try:
        langs = set(pytesseract.get_languages(config=""))
    except Exception as e:
        return False, {
            "error": f"Language query failed: {e}",
            "tesseract_cmd": app.config["TESSERACT_CMD"],
            "tesseract_version": version,
        }

    required_langs = {lang for lang in app.config["OCR_LANG"].split("+") if lang}
    missing_langs = sorted(list(required_langs - langs))
    ready = len(missing_langs) == 0

    return ready, {
        "tesseract_version": version,
        "available_langs": sorted(list(langs)),
        "missing_langs": missing_langs,
        "tesseract_cmd": app.config["TESSERACT_CMD"],
        "ocr_lang": app.config["OCR_LANG"],
    }

# ─────────────────────────────────────────────────────────────────────────────
# Auth decorator
# ─────────────────────────────────────────────────────────────────────────────

def require_secret(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        secret = request.headers.get("X-OCR-Secret", "")
        if secret != app.config["OCR_SECRET_KEY"]:
            return jsonify({"error": "Unauthorized"}), 401
        return f(*args, **kwargs)
    return decorated

# ─────────────────────────────────────────────────────────────────────────────
# Preprocessing Pipeline
# ─────────────────────────────────────────────────────────────────────────────

class Preprocessor:
    """OpenCV-based image preprocessing pipeline."""

    def __init__(
        self,
        grayscale:  bool = True,
        binarize:   bool = True,
        denoise:    bool = True,
        deskew:     bool = True,
        target_dpi: int  = 300,
        contrast_boost: float = 0.0,
        adaptive_denoise_strength: int = 10,
        enable_variants: bool = True,
        extra_upscale: float = 1.0,
        bilateral_filter: bool = False,
    ):
        self.grayscale  = grayscale
        self.binarize   = binarize
        self.denoise    = denoise
        self.deskew     = deskew
        self.target_dpi = target_dpi
        self.contrast_boost = contrast_boost
        self.adaptive_denoise_strength = adaptive_denoise_strength
        self.enable_variants = enable_variants
        self.extra_upscale = extra_upscale
        self.bilateral_filter = bilateral_filter

    def process(self, pil_image: Image.Image) -> np.ndarray:
        img = np.array(pil_image.convert("RGB"))
        img = cv2.cvtColor(img, cv2.COLOR_RGB2BGR)

        if self.grayscale:
            img = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

        # Apply bilateral filtering for better edge preservation (especially for KTP_ISTRI)
        if self.bilateral_filter and len(img.shape) == 2:
            img = cv2.bilateralFilter(img, 9, 75, 75)

        # Apply contrast boost if specified
        if self.contrast_boost > 0.0 and len(img.shape) == 2:
            img = cv2.convertScaleAbs(img, alpha=1.0 + self.contrast_boost, beta=0)
            img = np.clip(img, 0, 255).astype(np.uint8)

        if self.denoise:
            if len(img.shape) == 2:
                img = cv2.fastNlMeansDenoising(img, h=self.adaptive_denoise_strength)
            else:
                img = cv2.fastNlMeansDenoisingColored(img, h=self.adaptive_denoise_strength)

        if self.binarize and len(img.shape) == 2:
            img = cv2.adaptiveThreshold(
                img, 255,
                cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
                cv2.THRESH_BINARY, 11, 2,
            )

        if self.deskew and len(img.shape) == 2:
            img = self._deskew(img)

        # Apply extra upscaling if specified (for fine text)
        if self.extra_upscale > 1.0 and len(img.shape) == 2:
            h, w = img.shape[:2]
            img = cv2.resize(img, None, fx=self.extra_upscale, fy=self.extra_upscale, 
                           interpolation=cv2.INTER_CUBIC)

        return img

    def build_variants(self, pil_image: Image.Image, fast_only: bool = False) -> list[tuple[str, np.ndarray]]:
        """Build preprocessing variants - OPTIMIZED for low-quality KTP images.

        Order: Fast variants first (early stopping if good), slow ones only if needed.
        Includes: strong denoising for blur, morphology for digit robustness,
        color handling for dominant backgrounds, and ULTRA variants for failed scans.

        Args:
            pil_image: Input PIL image
            fast_only: If True, only use fast variants (for PDF optimization)
        """
        base_rgb = np.array(pil_image.convert("RGB"))
        base_bgr = cv2.cvtColor(base_rgb, cv2.COLOR_RGB2BGR)
        gray = cv2.cvtColor(base_bgr, cv2.COLOR_BGR2GRAY)

        variants: list[tuple[str, np.ndarray]] = []

        # FAST VARIANTS FIRST (likely to succeed on good images)
        # Variant 1: Default adaptive pipeline
        variants.append(("adaptive", self.process(pil_image)))

        # Variant 2: High-contrast variant (fast, effective for most cases)
        alpha = 1.5
        beta = 10
        contrast_img = cv2.convertScaleAbs(gray, alpha=alpha, beta=beta)
        _, contrast_binary = cv2.threshold(contrast_img, 128, 255, cv2.THRESH_BINARY)
        variants.append(("contrast_binary", contrast_binary))

        # Variant 3: Sharpened + OTSU threshold (fast)
        kernel_sharp = np.array([[-1, -1, -1],
                                [-1,  9, -1],
                                [-1, -1, -1]]) / 1.0
        sharpened = cv2.filter2D(gray, -1, kernel_sharp)
        sharpened = np.clip(sharpened, 0, 255).astype(np.uint8)
        _, sharp_otsu = cv2.threshold(sharpened, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
        variants.append(("sharp_otsu", sharp_otsu))

        # For PDF or fast-only mode, return early with just the fast 3 variants
        if fast_only:
            logger.info("Variant generation: fast_only=True, using 3 variants only")
            return variants

        # MEDIUM VARIANTS (for JPG/PNG with quality issues)
        # Variant 4: Aggressive CLAHE + Morphology for digit robustness
        clahe_agg = cv2.createCLAHE(clipLimit=4.0, tileGridSize=(6, 6))
        clahe_img = clahe_agg.apply(gray)
        kernel_close = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (3, 3))
        clahe_img = cv2.morphologyEx(clahe_img, cv2.MORPH_CLOSE, kernel_close)
        kernel_open = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (2, 2))
        clahe_img = cv2.morphologyEx(clahe_img, cv2.MORPH_OPEN, kernel_open)
        variants.append(("clahe_morph", clahe_img))

        # Variant 5: Equalized histogram for difficult lighting (fast)
        eq = cv2.equalizeHist(gray)
        eq_clahe = cv2.createCLAHE(clipLimit=2.5, tileGridSize=(8, 8)).apply(eq)
        variants.append(("equalized_clahe", eq_clahe))

        # ULTRA VARIANTS - For failed/partial scans (KTP_SUAMI, low quality images)
        # Variant 6: STRONG DENOISE + heavy CLAHE (for blur/noise)
        strong_denoised = cv2.fastNlMeansDenoising(gray, h=35, templateWindowSize=7, searchWindowSize=21)
        clahe_strong = cv2.createCLAHE(clipLimit=5.0, tileGridSize=(4, 4)).apply(strong_denoised)
        clahe_strong = cv2.morphologyEx(clahe_strong, cv2.MORPH_CLOSE, kernel_close)
        variants.append(("ultra_denoise_clahe", clahe_strong))

        # Variant 7: Super contrast + OTSU (for faded text)
        super_contrast = cv2.convertScaleAbs(gray, alpha=2.0, beta=20)
        _, super_binary = cv2.threshold(super_contrast, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
        # Connect broken characters
        kernel_dilate = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (2, 2))
        super_binary = cv2.dilate(super_binary, kernel_dilate, iterations=1)
        variants.append(("ultra_contrast_otsu", super_binary))

        # Variant 8: Upscaled image (for small text)
        h, w = gray.shape[:2]
        if h < 800 or w < 800:
            upscaled = cv2.resize(gray, None, fx=2.0, fy=2.0, interpolation=cv2.INTER_CUBIC)
            upscaled = cv2.fastNlMeansDenoising(upscaled, h=15)
            upscaled = cv2.createCLAHE(clipLimit=3.0, tileGridSize=(8, 8)).apply(upscaled)
            _, upscaled_binary = cv2.threshold(upscaled, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
            variants.append(("upscaled_otsu", upscaled_binary))

        # Variant 9: Bilateral filter + high-threshold binarization (preserve edges)
        bilateral = cv2.bilateralFilter(gray, 9, 75, 75)
        _, bilateral_binary = cv2.threshold(bilateral, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
        variants.append(("bilateral_otsu", bilateral_binary))

        # Variant 10: Blackhat morphology for dark text on light background
        kernel_blackhat = cv2.getStructuringElement(cv2.MORPH_RECT, (15, 5))
        blackhat = cv2.morphologyEx(gray, cv2.MORPH_BLACKHAT, kernel_blackhat)
        _, blackhat_binary = cv2.threshold(blackhat, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
        variants.append(("blackhat_otsu", blackhat_binary))

        return variants

    @staticmethod
    def _deskew(gray: np.ndarray) -> np.ndarray:
        coords = np.column_stack(np.where(gray > 0))
        if len(coords) == 0:
            return gray
        angle = cv2.minAreaRect(coords)[-1]
        if angle < -45:
            angle = -(90 + angle)
        else:
            angle = -angle

        (h, w) = gray.shape[:2]
        center = (w // 2, h // 2)
        M = cv2.getRotationMatrix2D(center, angle, 1.0)
        return cv2.warpAffine(gray, M, (w, h), flags=cv2.INTER_CUBIC,
                              borderMode=cv2.BORDER_REPLICATE)

# ─────────────────────────────────────────────────────────────────────────────
# OCR Engine
# ─────────────────────────────────────────────────────────────────────────────

class OCREngine:
    """Tesseract 5.x wrapper with field extraction and validation."""

    PATTERNS = {
        "nik":       re.compile(r"\b(\d{16})\b"),
        "kk":        re.compile(r"\b(\d{16})\b"),
        "tgl_lahir": re.compile(r"\b(\d{2}[-/]\d{2}[-/]\d{4})\b"),
    }
    LOOSE_DIGITS = re.compile(r"(?:\d[\s\-:/.]*){14,20}")
    LOOSE_DATE = re.compile(r"\b(\d{2})\s*[-/.]\s*(\d{2})\s*[-/.]\s*(\d{4})\b")

    # Common OCR label synonyms on Indonesian ID cards - EXPANDED for robustness
    FIELD_HEADERS = {
        "nik":           ["nik", "nomor induk kependudukan", "no induk", "no.nik", "nik.", "no nik", "no. nik", "nomer induk", "no. induk kependudukan"],
        "no_kk":         ["no kk", "nomor kk", "kartu keluarga", "no kartu keluarga", "no.kk", "nomer kk"],
        "nama":          ["nama", "nama lengkap", "name", "nama:", "nama :", "n a m a"],
        "tgl_lahir":     ["tanggal lahir", "tgl lahir", "ttl", "tempat tgl lahir", "tgllahir", "tgl.lahir", "tanggal/tempat", "tangal lahir", "tgl. lahir"],
        "tempat_lahir":  ["tempat lahir", "tempat/tgl", "tempat/tgl lahir", "tempat", "t e m p a t l a h i r"],
        "jenis_kelamin": ["jenis kelamin", "jk", "jenis kelamin:", "jk:", "jenis kelamin :", "j.kelamin"],
        "alamat":        ["alamat", "alamat:", "alamat :", "jalan", "jln"],
        "rt_rw":         ["rt/rw", "rt/rw:", "rt / rw", "rt/rw :", "rt.rw"],
        "kelurahan":     ["kel/desa", "kelurahan", "desa", "kel/dese", "kevdesa", "kedesa", "ke/desa", "kelurahan/desa", "kel.desa", "kelurahan/kel"],
        "kecamatan":     ["kecamatan", "kecamatan:", "kecamatan :", "kec."],
        "kabupaten":     ["kabupaten", "kota", "kabupaten/kota", "kabupaten/kota:"],
        "provinsi":      ["provinsi", "provinsi:"],
    }

    def __init__(self, lang: str = "eng", config: str = "--oem 3 --psm 6"):
        self.lang   = lang
        self.config = config
        
        # Build set of all known field labels for quick lookup to avoid label-as-value confusion
        self.all_field_labels_lower = set()
        for field, labels in self.FIELD_HEADERS.items():
            for lbl in labels:
                self.all_field_labels_lower.add(lbl.lower())

    def extract(self, img: np.ndarray, config: str | None = None) -> dict:
        start = time.time()
        active_config = config or self.config

        # Get full data with confidence
        data = pytesseract.image_to_data(
            img, lang=self.lang, config=active_config,
            output_type=pytesseract.Output.DICT,
        )

        raw_text = pytesseract.image_to_string(
            img, lang=self.lang, config=active_config,
        )

        fields, confidence = self._parse_fields(raw_text, data)
        overall = (sum(confidence.values()) / len(confidence)) if confidence else 0.0

        return {
            "nik":           fields.get("nik"),
            "kk":            fields.get("no_kk"),
            "nama":          fields.get("nama"),
            "tgl_lahir":     fields.get("tgl_lahir"),
            "tempat_lahir":  fields.get("tempat_lahir"),
            "jenis_kelamin": fields.get("jenis_kelamin"),
            "alamat":        fields.get("alamat"),
            "rt_rw":         fields.get("rt_rw"),
            "kelurahan":     fields.get("kelurahan"),
            "kecamatan":     fields.get("kecamatan"),
            "kabupaten":     fields.get("kabupaten"),
            "provinsi":      fields.get("provinsi"),
            "confidence":    confidence,
            "raw_text":      {"full": raw_text},
            "engine_version": pytesseract.get_tesseract_version().public,
            "processing_ms": int((time.time() - start) * 1000),
        }

    @staticmethod
    def score_result(result: dict) -> float:
        """Score extraction result; prioritize critical fields and populated outputs."""
        conf = result.get("confidence") or {}
        score = 0.0

        # Heavily weight successful extraction of critical fields
        if result.get("nik"):
            score += 5.0
        if result.get("tgl_lahir"):
            score += 4.0
        if result.get("nama"):
            score += 3.0

        # Weight other fields
        optional_fields = ["tempat_lahir", "rt_rw", "kelurahan", "kecamatan"]
        score += sum(0.6 for f in optional_fields if result.get(f))

        # Add confidence score with better thresholding
        # More lenient: accept 0.55+ confidence, was limiting at 0.70
        if conf:
            avg_conf = sum(conf.values()) / max(len(conf), 1)
            if avg_conf >= 0.55:  # Lowered from 0.70 for more extraction
                score += min(avg_conf, 1.0)
            else:
                score += avg_conf * 0.3  # Reduced penalty for lower confidence

        # Bonus for text volume (sign of good extraction)
        raw_len = len((result.get("raw_text") or {}).get("full") or "")
        score += min(raw_len / 500.0, 1.0)

        # Bonus: If extracted ANY critical field successfully, boost score
        critical_count = sum(1 for f in ["nik", "nama", "tgl_lahir"] if result.get(f))
        score += critical_count * 0.5

        # NIK VALIDATION BOOST: +2.0 for structurally valid NIK (province code + valid date)
        nik = result.get("nik", "")
        if nik and re.match(r"^\d{16}$", str(nik)):
            nik_valid, nik_boost = OCREngine._validate_nik_structure(str(nik))
            score += nik_boost
            if nik_valid:
                logger.debug(f"NIK validation boost: +{nik_boost:.2f} for valid NIK structure")

        return score

    @staticmethod
    def _validate_nik_structure(nik: str) -> tuple[bool, float]:
        """
        Validate NIK structure using Indonesian rules.
        Returns (is_valid, confidence_boost)
        """
        if not nik or len(nik) != 16:
            return False, 0.0

        boost = 0.0
        valid = True

        # Check 1: Valid province code (first 2 digits: 01-34)
        province_code = nik[:2]
        if province_code not in VALID_PROVINCE_CODES:
            valid = False
        else:
            boost += 0.7  # +0.7 for valid province

        # Check 2: Valid birth date (digits 6-11: YYMMDD)
        try:
            year = int(nik[6:8])
            month = int(nik[8:10])
            day = int(nik[10:12])

            # Handle female date adjustment (+40, +60)
            check_day = day
            if day > 40:
                check_day = day - 40
            elif day > 60:
                check_day = day - 60

            if not (1 <= month <= 12):
                valid = False
            elif not (1 <= check_day <= 31):
                valid = False
            else:
                boost += 0.7  # +0.7 for valid birth date
        except:
            valid = False

        # Check 3: Valid serial number (last 6 digits: 000001-999999)
        try:
            serial = int(nik[10:16])
            if serial <= 0 or serial > 999999:
                valid = False
            else:
                boost += 0.6  # +0.6 for valid serial
        except:
            valid = False

        # Only give full boost if ALL checks pass
        if valid:
            return True, 2.0  # +2.0 for fully valid NIK
        else:
            return False, boost * 0.3  # Partial boost for partially valid

    def _parse_fields(self, raw_text: str, tess_data: dict) -> tuple[dict, dict]:
        lines  = [l.strip() for l in raw_text.splitlines() if l.strip()]
        fields = {}
        conf   = {}

        # Word-level average confidence per field (simple heuristic)
        word_confs = [
            int(c) for c in tess_data["conf"]
            if str(c).lstrip("-").isdigit() and int(c) >= 0
        ]
        overall_word_conf = (sum(word_confs) / len(word_confs) / 100) if word_confs else 0.0
        
        logger.debug(f"Parsing {len(lines)} text lines, avg confidence: {overall_word_conf:.2%}")

        for i, line in enumerate(lines):
            lower = line.lower()

            for field, labels in self.FIELD_HEADERS.items():
                value = self._extract_labeled_value(line, lower, labels, lines[i + 1] if i + 1 < len(lines) else "")
                if not value:
                    continue

                # CRITICAL CHECK: If the extracted value is itself a field label, reject it
                # This prevents "RT/RW" being assigned to "alamat" field
                value_lower = value.lower()
                is_label = value_lower in self.all_field_labels_lower
                if is_label:
                    logger.debug(f"Rejecting value='{value}' for field '{field}' - it's a known label")
                    continue

                value = self._clean_field_value(field, value)
                if not value:
                    continue

                old_value = fields.get(field, "")
                if self._value_quality(field, value) >= self._value_quality(field, old_value):
                    fields[field] = value
                    conf[field] = overall_word_conf
                    logger.debug(f"Extracted {field}: {value[:50]}")

        # Clean NIK extracted from labels (e.g., with spaces or punctuation).
        if "nik" in fields:
            # Apply OCR character cleaning to handle common confusions
            nik_cleaned = self._clean_nik_corruption(fields["nik"])
            if len(nik_cleaned) >= 16:
                fields["nik"] = nik_cleaned[:16]
            elif len(nik_cleaned) >= 14:
                # Accept 14-16 digit NIK if cleaned successfully
                fields["nik"] = nik_cleaned
            else:
                # Try to find 16-digit NIK in raw text if labeled extraction failed
                nik_from_raw = self._extract_best_nik(raw_text, lines)
                if nik_from_raw:
                    fields["nik"] = nik_from_raw
                    conf["nik"] = max(overall_word_conf, 0.85)
                    logger.debug(f"Extracted NIK from raw: {nik_from_raw}")
                else:
                    fields.pop("nik", None)
                    conf.pop("nik", None)

        # Normalize date extracted from labels (e.g., "JAKARTA 10/08/1993").
        if "tgl_lahir" in fields:
            date_in_value = self.LOOSE_DATE.findall(fields["tgl_lahir"])
            if date_in_value:
                normalized_date = self._normalize_date(date_in_value[0])
                if normalized_date:
                    fields["tgl_lahir"] = normalized_date
                else:
                    fields.pop("tgl_lahir", None)
                    conf.pop("tgl_lahir", None)
            else:
                fields.pop("tgl_lahir", None)
                conf.pop("tgl_lahir", None)

        if "nik" not in fields:
            nik_value = self._extract_best_nik(raw_text, lines)
            if nik_value:
                # Apply character cleaning to extracted NIK
                nik_value = self._clean_nik_corruption(nik_value)
                if nik_value and len(nik_value) >= 14:
                    fields["nik"] = nik_value
                    # Higher confidence for label-based extraction, lower for pure regex
                    conf["nik"] = max(overall_word_conf, 0.92)

        if "tgl_lahir" not in fields:
            date_value = self._extract_best_date(raw_text, lines)
            if date_value:
                fields["tgl_lahir"] = date_value
                # Higher confidence for label-based extraction, lower for pure regex
                conf["tgl_lahir"] = max(overall_word_conf, 0.90)

        if "rt_rw" not in fields:
            rt_match = re.search(r"\b(\d{1,3})\s*[/\\]\s*(\d{1,3})\b", raw_text)
            if rt_match:
                fields["rt_rw"] = f"{rt_match.group(1)}/{rt_match.group(2)}"
                conf["rt_rw"] = max(overall_word_conf, 0.78)

        if "tempat_lahir" not in fields:
            ttl_match = re.search(
                r"tempat[^\n]{0,25}lahir[^A-Z0-9]*([A-Z ]+?)\s*,?\s*\d{2}\s*[-/.]\s*\d{2}\s*[-/.]\s*\d{4}",
                raw_text,
                flags=re.IGNORECASE,
            )
            if ttl_match:
                place = re.sub(r"\s+", " ", ttl_match.group(1)).strip(" -,:;")
                if place:
                    fields["tempat_lahir"] = place.upper()
                    conf["tempat_lahir"] = max(overall_word_conf, 0.75)

        if "tempat_lahir" not in fields and fields.get("kabupaten"):
            kab_val = str(fields["kabupaten"])
            km = re.search(
                r"^\s*([A-Z ]+?)\s*,\s*\d{2}\s*[-/.]\s*\d{2}\s*[-/.]\s*\d{4}\s*$",
                kab_val,
                flags=re.IGNORECASE,
            )
            if km:
                place = re.sub(r"\s+", " ", km.group(1)).strip()
                if place:
                    fields["tempat_lahir"] = place.upper()
                    conf["tempat_lahir"] = max(overall_word_conf, 0.72)

        # ═════════════════════════════════════════════════════════════════════════════
        # FINAL CLEANUP: Aggressive cleansing of NIK and other fields to remove OCR garbage
        # ═════════════════════════════════════════════════════════════════════════════
        
        # Final NIK cleanup - NO MATTER HOW IT WAS EXTRACTED, clean it aggressively
        if "nik" in fields and fields["nik"]:
            fields["nik"] = self._clean_nik_corruption(fields["nik"])
            # If cleaning resulted in empty/invalid, remove it
            if not fields["nik"] or len(fields["nik"]) < 14:
                fields.pop("nik", None)
                conf.pop("nik", None)

        return fields, conf

    def _extract_labeled_value(self, line: str, line_lower: str, labels: list[str], next_line: str) -> str:
        for lbl in labels:
            idx = line_lower.find(lbl)
            if idx < 0:
                continue

            remainder = line[idx + len(lbl):]
            # More flexible delimiter handling: accept any non-alphanumeric as separator
            remainder = re.sub(r'^[\s:;,.\-_=\\/"""\'»«]+', '', remainder).strip()
            if remainder:
                return remainder

            # If label found but no value on same line, try next line
            # BUT: Check if next line is actually another field label (e.g., "RT/RW")
            next_val = (next_line or "").strip()
            if next_val:
                # CRITICAL: Check if next_val is a known field label
                next_val_lower = next_val.lower()
                is_field_label = next_val_lower in self.all_field_labels_lower
                
                if is_field_label:
                    # Skip - this is another label, not a value
                    return ""
                
                return next_val

        return ""

    @staticmethod
    def _value_quality(field: str, value: str) -> float:
        if not value:
            return 0.0
        if field == "nik":
            digits = re.sub(r"\D", "", value)
            if len(digits) == 16:
                return 5.0
            return 1.0
        if field == "tgl_lahir":
            return 4.0 if re.search(r"\d{2}\s*[-/.]\s*\d{2}\s*[-/.]\s*\d{4}", value) else 1.0
        return min(len(value), 30) / 10.0

    @staticmethod
    def _clean_field_value(field: str, value: str) -> str:
        cleaned = value.strip()
        upper = cleaned.upper()

        if field == "nama" and any(token in upper for token in ["TEMPAT", "TGL", "LAHIR", "PROVINSI"]):
            return ""

        if field == "kecamatan" and upper.startswith("AGAMA"):
            return ""

        if field == "kecamatan":
            cleaned = re.sub(r"[^A-Z0-9\s]", "", upper)
            cleaned = re.sub(r"\s+", " ", cleaned).strip()
            return cleaned

        if field == "rt_rw":
            m = re.search(r"\d{1,3}\s*/\s*\d{1,3}", cleaned)
            if m:
                return re.sub(r"\s+", "", m.group(0))
            return ""
        
        # Better address handling - keep it flexible for OCR variations
        if field == "alamat":
            upper_cleaned = cleaned.upper()
            
            # REJECT if alamat is actually a field label
            suspicious_alamat_values = ["RT/RW", "KEL/DESA", "KELURAHAN", "DESA", "KECAMATAN", "KABUPATEN", "PROVINSI"]
            if any(susp in upper_cleaned for susp in suspicious_alamat_values):
                logger.debug(f"_clean_field_value: alamat rejected because looks like label: {cleaned}")
                return ""
            
            # Remove trailing punctuation but keep numbers/letters
            cleaned = re.sub(r'[\s,:;.]*$', '', cleaned)
            # Allow mixed case for addresses
            return value.strip()

        return cleaned

    @staticmethod
    def _normalize_digits(text: str) -> str:
        return re.sub(r"\D", "", text or "")

    @staticmethod
    def _normalize_date(match: tuple[str, str, str]) -> str | None:
        day, month, year = (int(match[0]), int(match[1]), int(match[2]))
        if month < 1 or month > 12:
            return None
        if day < 1 or day > 31:
            return None
        if year < 1900 or year > 2100:
            return None
        return f"{day:02d}-{month:02d}-{year:04d}"

    @staticmethod
    def _clean_nik_corruption(nik_str: str) -> str:
        """Clean OCR corruption in NIK values - SMART approach.
        
        Order matters:
        1. Strip leading/trailing garbage
        2. ONLY then apply targeted character mapping
        3. Handle edge cases like extra digits
        """
        nik = str(nik_str).strip()
        
        # Step 1: Remove leading garbage (: . , > | [ { etc) and letters
        # Keep removing until we find a digit or suspected digit pattern
        nik = re.sub(r'^[^0-9]*', '', nik)  # Remove everything before first digit
        
        # Step 2: Remove trailing garbage
        nik = re.sub(r'[^0-9]*$', '', nik)  # Remove everything after last digit
        
        # Step 3: TARGETED character mapping - ONLY common OCR digit confusions
        # These replacements only make sense if they're within/between digit sequences
        nik = nik.replace('+', '0')   # Common: ocr reads + as 0 (definitely wrong)
        nik = nik.replace('O', '0')   # Very common: ocr reads O as 0 in digit context
        nik = nik.replace('o', '0')   # Lowercase o → 0
        nik = nik.replace('l', '1')   # Lowercase L can look like 1 in some fonts
        nik = nik.replace('I', '1')   # Uppercase I → 1
        nik = nik.replace('S', '5')   # S can look like 5
        nik = nik.replace('Z', '2')   # Z can look like 2
        # NOTE: REMOVED B→8, b→8 because 'b' at start of NIK is stray garbage, not digit 8
        # NOTE: REMOVED G→6 for same reason
        
        # Step 4: Remove dashes/spaces
        nik = nik.replace('-', '')
        nik = nik.replace(' ', '')
        
        # Step 5: Keep ONLY digits now
        nik = re.sub(r'[^\d]', '', nik)
        
        # Step 6: Handle extra digits from prefix
        if len(nik) == 17 and nik[0] == '1':
            # Likely :1.prefix case
            candidate = nik[1:17]
            if candidate[0] in '123456':  # Valid province code
                nik = candidate
        elif len(nik) > 16:
            nik = nik[:16]
        elif len(nik) < 14:
            return ""
            
        return nik if len(nik) >= 14 else ""

    def _extract_best_nik(self, raw_text: str, lines: list[str]) -> str | None:
        candidates: list[tuple[str, float]] = []
        loose_candidates: list[tuple[str, float]] = []

        # Prefer candidates that appear close to NIK label lines.
        for i, line in enumerate(lines):
            line_lower = line.lower()
            if "nik" in line_lower or "nomor induk" in line_lower or "no induk" in line_lower:
                window = " ".join(lines[i:min(i + 3, len(lines))])
                for loose in self.LOOSE_DIGITS.findall(window):
                    digits = self._normalize_digits(loose)
                    if len(digits) >= 16:
                        candidates.append((digits[:16], 3.0))
                    elif len(digits) >= 14:
                        loose_candidates.append((digits, 2.0))

        # More aggressive global fallback: any digit sequences 14+ chars
        for loose in self.LOOSE_DIGITS.findall(raw_text):
            digits = self._normalize_digits(loose)
            if len(digits) == 16:
                candidates.append((digits, 2.0))
            elif len(digits) > 16:
                candidates.append((digits[:16], 1.5))
            elif len(digits) >= 14:
                loose_candidates.append((digits, 0.8))

        # Also try strict pattern match as last resort
        strict_matches = self.PATTERNS["nik"].findall(raw_text)
        for match in strict_matches:
            candidates.append((match, 1.2))

        if not candidates:
            if not loose_candidates:
                return None
            loose_scores: dict[str, float] = {}
            for value, weight in loose_candidates:
                loose_scores[value] = loose_scores.get(value, 0.0) + weight
            return max(loose_scores.items(), key=lambda item: item[1])[0]

        scores: dict[str, float] = {}
        for value, weight in candidates:
            scores[value] = scores.get(value, 0.0) + weight

        return max(scores.items(), key=lambda item: item[1])[0]

    def _extract_best_date(self, raw_text: str, lines: list[str]) -> str | None:
        # Try date near birth-date labels first (expanded window)
        for i, line in enumerate(lines):
            line_lower = line.lower()
            if any(token in line_lower for token in ["tgl", "tanggal", "ttl", "lahir", "tgllahir"]):
                window = " ".join(lines[i:min(i + 3, len(lines))])
                for matched in self.LOOSE_DATE.findall(window):
                    normalized = self._normalize_date(matched)
                    if normalized:
                        return normalized

        # Aggressive global fallback over full OCR text
        for matched in self.LOOSE_DATE.findall(raw_text):
            normalized = self._normalize_date(matched)
            if normalized:
                return normalized

        # Try even looser date pattern: any date-like sequence
        loose_date_pattern = re.compile(
            r"(\d{1,2})\s*[-/.\s]\s*(\d{1,2})\s*[-/.\s]\s*(\d{4})",
            re.IGNORECASE
        )
        for matched in loose_date_pattern.findall(raw_text):
            normalized = self._normalize_date(matched)
            if normalized:
                return normalized

        # Backward compatibility with strict pattern
        strict = self.PATTERNS["tgl_lahir"].findall(raw_text)
        if strict:
            parts = strict[0].replace("/", "-").split("-")
            normalized = self._normalize_date((parts[0], parts[1], parts[2]))
            return normalized

        return None

# ─────────────────────────────────────────────────────────────────────────────
# Singletons
# ─────────────────────────────────────────────────────────────────────────────

def get_preprocessor_config(document_type: str = "default") -> dict:
    """Get preprocessor configuration for the given document type.

    ULTRA-AGGRESSIVE approach for failed/partial scans:
    - KTP_SUAMI: Often has lower quality, needs extra preprocessing
    - KTP_ISTRI: Similar treatment, also needs stronger denoising
    """
    # Base configuration from environment
    target_dpi = app.config["UPLOAD_DPI"]
    contrast_boost = float(os.getenv("OCR_CONTRAST_BOOST", "0.5"))
    denoise_strength = int(os.getenv("OCR_DENOISE_STRENGTH", "8"))

    # ULTRA-AGGRESSIVE profile for problematic images (KTP_SUAMI, low quality scans)
    ultra_profile = {
        'grayscale': True,
        'binarize': True,
        'denoise': True,
        'deskew': True,
        'target_dpi': target_dpi,
        'contrast_boost': contrast_boost + 0.3,  # Extra +30% boost
        'adaptive_denoise_strength': denoise_strength + 5,  # Extra denoise
        'enable_variants': True,
        'extra_upscale': 1.3,  # Slight upscale for small text
        'bilateral_filter': True,  # Better edge preservation
    }

    # Standard aggressive profile for normal images
    standard_profile = {
        'grayscale': True,
        'binarize': True,
        'denoise': True,
        'deskew': True,
        'target_dpi': target_dpi,
        'contrast_boost': contrast_boost,
        'adaptive_denoise_strength': denoise_strength,
        'enable_variants': True,
        'extra_upscale': 1.0,
        'bilateral_filter': False,
    }

    # Document-specific profiles - use ultra for both KTP types to improve success rate
    profiles = {
        'KTP_SUAMI': ultra_profile,    # Ultra aggressive for husband KTP (often partial)
        'KTP_ISTRI': ultra_profile,    # Ultra aggressive for wife KTP
        'KTP': ultra_profile,          # Ultra aggressive for generic KTP
        'KK': standard_profile,
        'default': ultra_profile,      # Default to ultra for better coverage
    }

    selected = profiles.get(document_type, profiles['default'])
    logger.info(f"Using preprocessor profile '{document_type}' -> {'ultra' if selected == ultra_profile else 'standard'}")

    return selected

preprocessor = Preprocessor(
    grayscale=True,
    binarize=True,
    denoise=True,
    deskew=True,
    target_dpi=app.config["UPLOAD_DPI"],
)

engine = OCREngine(lang=app.config["OCR_LANG"], config="--oem 3 --psm 6")

# ─────────────────────────────────────────────────────────────────────────────
# Routes
# ─────────────────────────────────────────────────────────────────────────────

@app.get("/health")
def health():
    ready, runtime = check_ocr_runtime()
    status = "ok" if ready else "degraded"

    payload = {
        "status": status,
        "service": "ocr-microservice",
        "ready": ready,
        **runtime,
    }
    if not ready and "error" not in payload:
        payload["error"] = "Required OCR languages are missing."

    return jsonify(payload), (200 if ready else 503)

@app.get("/version")
@require_secret
def version():
    try:
        ver = str(pytesseract.get_tesseract_version())
    except Exception as e:
        ver = f"error: {e}"
    return jsonify({"tesseract": ver, "service": "1.0.0"})


@app.post("/ocr/process")
@require_secret
def ocr_process():
    if "file" not in request.files:
        return jsonify({"error": "No file uploaded."}), 400

    file = request.files["file"]
    mime = file.mimetype or "application/octet-stream"

    if mime not in ACCEPTED_MIMES:
        return jsonify({"error": f"Unsupported mime type: {mime}"}), 422

    data = file.read()
    if not data:
        return jsonify({"error": "Empty file."}), 400

    # Get document type from request header for specialized preprocessing
    document_type = request.headers.get("X-Document-Type", "generic")
    
    try:
        logger.info(f"Processing file: mime={mime}, size={len(data)} bytes")
        images = _load_images(data, mime)
        logger.debug(f"Loaded {len(images)} image(s)")
    except Exception as e:
        logger.error(f"Image load error: {e}", exc_info=True)
        return jsonify({"error": f"Failed to load image: {e}"}), 422

    try:
        # Create preprocessor with document-type-specific configuration
        config = get_preprocessor_config(document_type)
        doc_preprocessor = Preprocessor(**config)
        
        logger.info(f"OCR processing document type: {document_type}", extra={
            "document_type": document_type,
            "config": config,
            "file_mime": mime,
        })
        
        # Use full PSM modes for better quality on all document types
        if mime == "application/pdf":
            # PDF: Use all PSM modes but fewer variants for speed
            psm_candidates = [
                p.strip() for p in str(app.config.get("OCR_PSM_CANDIDATES", "6,4,11,8,13")).split(",")
                if p.strip().isdigit()
            ] or ["6"]
            use_fast_variants_only = False  # But test more variants for quality
        else:
            psm_candidates = [
                p.strip() for p in str(app.config.get("OCR_PSM_CANDIDATES", "6,4,11,8,13")).split(",")
                if p.strip().isdigit()
            ] or ["6"]
            use_fast_variants_only = False  # Full variants for images

        # Process all pages; merge results (primary = first page)
        results: list[dict] = []

        for pil_img in images:
            candidates: list[dict] = []
            found_good_result = False

            for variant_name, processed in doc_preprocessor.build_variants(pil_img, fast_only=use_fast_variants_only):
                for psm in psm_candidates:
                    cfg = f"--oem 3 --psm {psm}"
                    result = engine.extract(processed, config=cfg)
                    result["_variant"] = variant_name
                    result["_psm"] = psm
                    candidates.append(result)

                    # EARLY STOPPING: If we found a result with valid NIK and good score, stop testing
                    # This significantly speeds up processing for good images
                    if result.get("nik") and re.match(r"^\d{16}$", str(result.get("nik"))):
                        score = engine.score_result(result)
                        if score >= 10.0:  # Good enough score with valid NIK
                            logger.info(f"Early stopping: Found good result with variant={variant_name}, psm={psm}, score={score:.2f}")
                            found_good_result = True
                            break
                if found_good_result:
                    break

            # Find best result and log which variant/PSM won
            best_score = -1
            best_variant = None
            best_psm = None
            for c in candidates:
                s = engine.score_result(c)
                if s > best_score:
                    best_score = s
                    best_variant = c.get("_variant")
                    best_psm = c.get("_psm")

            best = max(candidates, key=engine.score_result)
            best.pop("_variant", None)
            best.pop("_psm", None)

            # Log which combination won
            logger.info(f"Best variant: {best_variant}, PSM: {best_psm}, score: {best_score:.2f}, NIK: {best.get('nik', 'NONE')}, candidates tested: {len(candidates)}")

            results.append(best)

        merged = _merge_results(results)
        merged["checksum"] = hashlib.sha256(data).hexdigest()

        logger.info("OCR completed: nik=%s confidence=%s",
                    merged.get("nik"), merged.get("confidence", {}).get("nik"))
        return jsonify(merged), 200

    except Exception as e:
        logger.exception("OCR processing error")
        return jsonify({"error": str(e)}), 500


# ─────────────────────────────────────────────────────────────────────────────
# Helpers
# ─────────────────────────────────────────────────────────────────────────────

def _load_images(data: bytes, mime: str) -> list[Image.Image]:
    """Convert uploaded file bytes to list of PIL Images."""
    if mime == "application/pdf":
        # PDF-specific optimization: use 150 DPI (sufficient for text OCR, 4x faster)
        pdf_dpi = int(os.getenv("PDF_DPI", "150"))
        
        try:
            # Try PyMuPDF first (no system dependencies)
            logger.debug(f"PDF_LIBRARY={PDF_LIBRARY}, attempting PDF conversion...")
            if PDF_LIBRARY == "fitz":
                logger.debug("Using fitz for PDF processing")
                doc = fitz.open(stream=data, filetype="pdf")
                images = []
                # Only process first page (KTP/Istri are single-page documents)
                for page_num in range(min(1, len(doc))):
                    page = doc[page_num]
                    # Render to pixmap at specified DPI
                    mat = fitz.Matrix(pdf_dpi / 72, pdf_dpi / 72)
                    pix = page.get_pixmap(matrix=mat, alpha=False)
                    # Convert to PIL Image
                    img = Image.frombytes("RGB", (pix.width, pix.height), pix.samples)
                    images.append(img)
                doc.close()
                logger.debug(f"PDF conversion successful: {len(images)} image(s)")
                return images
            else:
                logger.debug("Using pdf2image fallback for PDF processing")
                images = convert_from_bytes(data, dpi=pdf_dpi)
                return images[:1] if images else images
                
        except Exception as e:
            logger.error(f"PDF conversion error ({PDF_LIBRARY}): {e}", exc_info=True)
            raise
    else:
        return [Image.open(io.BytesIO(data))]


def _merge_results(results: list[dict]) -> dict:
    """Merge OCR results from multiple pages; keep highest-confidence values."""
    if not results:
        return {}
    if len(results) == 1:
        return results[0]

    merged = results[0].copy()
    for r in results[1:]:
        for key in ["nik", "kk", "nama", "tgl_lahir", "tempat_lahir",
                    "jenis_kelamin", "alamat", "rt_rw", "kelurahan",
                    "kecamatan", "kabupaten", "provinsi"]:
            # Fill missing fields from subsequent pages
            if not merged.get(key) and r.get(key):
                merged[key] = r[key]

        # Merge confidence – take max per field
        for field, score in r.get("confidence", {}).items():
            merged["confidence"][field] = max(
                merged["confidence"].get(field, 0.0), score
            )

        # Merge raw text
        merged["raw_text"]["full"] += "\n\n[PAGE]\n\n" + r["raw_text"]["full"]

    return merged


# ─────────────────────────────────────────────────────────────────────────────

if __name__ == "__main__":
    port = int(os.getenv("PORT", "5001"))
    debug = os.getenv("DEBUG", "false").lower() == "true"

    ready, runtime = check_ocr_runtime()
    if not ready:
        logger.error(
            "OCR runtime not ready. cmd=%s missing_langs=%s error=%s",
            runtime.get("tesseract_cmd"),
            runtime.get("missing_langs"),
            runtime.get("error"),
        )
        raise SystemExit(1)

    logger.info("Starting OCR microservice on port %d", port)
    app.run(host="0.0.0.0", port=port, debug=debug)
