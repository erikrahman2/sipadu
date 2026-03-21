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

import hashlib
import io
import json
import logging
import os
import re
import time
from functools import wraps
from pathlib import Path

import cv2
import numpy as np
import pytesseract
from flask import Flask, jsonify, request
from pdf2image import convert_from_bytes
from PIL import Image

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
    OCR_PSM_CANDIDATES = os.getenv("OCR_PSM_CANDIDATES", "6,11,3"),
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
    ):
        self.grayscale  = grayscale
        self.binarize   = binarize
        self.denoise    = denoise
        self.deskew     = deskew
        self.target_dpi = target_dpi

    def process(self, pil_image: Image.Image) -> np.ndarray:
        img = np.array(pil_image.convert("RGB"))
        img = cv2.cvtColor(img, cv2.COLOR_RGB2BGR)

        if self.grayscale:
            img = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

        if self.denoise:
            if len(img.shape) == 2:
                img = cv2.fastNlMeansDenoising(img, h=10)
            else:
                img = cv2.fastNlMeansDenoisingColored(img, h=10)

        if self.binarize and len(img.shape) == 2:
            img = cv2.adaptiveThreshold(
                img, 255,
                cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
                cv2.THRESH_BINARY, 11, 2,
            )

        if self.deskew and len(img.shape) == 2:
            img = self._deskew(img)

        return img

    def build_variants(self, pil_image: Image.Image) -> list[tuple[str, np.ndarray]]:
        """Build multiple preprocessing variants for OCR fallback."""
        base_rgb = np.array(pil_image.convert("RGB"))
        base_bgr = cv2.cvtColor(base_rgb, cv2.COLOR_RGB2BGR)
        gray = cv2.cvtColor(base_bgr, cv2.COLOR_BGR2GRAY)

        variants: list[tuple[str, np.ndarray]] = []

        # Current default pipeline.
        variants.append(("adaptive", self.process(pil_image)))

        # Simple grayscale fallback can work better on colored KTP backgrounds.
        variants.append(("gray", gray))

        # OTSU threshold fallback for strong foreground/background separation.
        _, otsu = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
        variants.append(("otsu", otsu))

        # Contrast-enhanced grayscale fallback.
        eq = cv2.equalizeHist(gray)
        variants.append(("equalized", eq))

        # Upscale fallback for tiny/blurred text.
        upscaled = cv2.resize(gray, None, fx=2.0, fy=2.0, interpolation=cv2.INTER_CUBIC)
        variants.append(("upscaled", upscaled))

        # Left-panel ROI fallback (KTP text region is usually on the left side).
        h, w = gray.shape[:2]
        left_panel = gray[0:int(h * 0.78), 0:int(w * 0.72)]
        if left_panel.size > 0:
            variants.append(("left_panel", left_panel))

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

    # Common OCR label synonyms on Indonesian ID cards
    FIELD_HEADERS = {
        "nik":           ["nik", "nomor induk kependudukan", "no induk", "no.nik", "nik.", "no nik"],
        "no_kk":         ["no kk", "nomor kk", "kartu keluarga", "no kartu keluarga", "no.kk"],
        "nama":          ["nama", "nama lengkap", "name", "nama:"],
        "tgl_lahir":     ["tanggal lahir", "tgl lahir", "ttl", "tempat tgl lahir", "tgllahir", "tgl.lahir", "tanggal/tempat"],
        "tempat_lahir":  ["tempat lahir", "tempat/tgl", "tempat/tgl lahir"],
        "jenis_kelamin": ["jenis kelamin", "jk", "jenis kelamin:"],
        "alamat":        ["alamat", "alamat:"],
        "rt_rw":         ["rt/rw", "rt/rw:", "rt / rw"],
        "kelurahan":     ["kel/desa", "kelurahan", "desa", "kel/dese", "kevdesa", "kedesa", "ke/desa", "kelurahan/desa"],
        "kecamatan":     ["kecamatan", "kecamatan:"],
        "kabupaten":     ["kabupaten", "kota", "kabupaten/kota"],
        "provinsi":      ["provinsi", "provinsi:"],
    }

    def __init__(self, lang: str = "eng", config: str = "--oem 3 --psm 6"):
        self.lang   = lang
        self.config = config

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

        # Add confidence score
        if conf:
            avg_conf = sum(conf.values()) / max(len(conf), 1)
            score += min(avg_conf, 1.0)

        # Bonus for text volume (sign of good extraction)
        raw_len = len((result.get("raw_text") or {}).get("full") or "")
        score += min(raw_len / 500.0, 1.0)

        return score

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

        for i, line in enumerate(lines):
            lower = line.lower()

            for field, labels in self.FIELD_HEADERS.items():
                value = self._extract_labeled_value(line, lower, labels, lines[i + 1] if i + 1 < len(lines) else "")
                if not value:
                    continue

                value = self._clean_field_value(field, value)
                if not value:
                    continue

                old_value = fields.get(field, "")
                if self._value_quality(field, value) >= self._value_quality(field, old_value):
                    fields[field] = value
                    conf[field] = overall_word_conf

        # Clean NIK extracted from labels (e.g., with spaces or punctuation).
        if "nik" in fields:
            nik_digits = self._normalize_digits(fields["nik"])
            if len(nik_digits) >= 16:
                fields["nik"] = nik_digits[:16]
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

        return fields, conf

    @staticmethod
    def _extract_labeled_value(line: str, line_lower: str, labels: list[str], next_line: str) -> str:
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
            next_val = (next_line or "").strip()
            if next_val:
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

        if field == "nama" and any(token in upper for token in ["TEMPAT", "TGL", "LAHIR"]):
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

    try:
        images = _load_images(data, mime)
    except Exception as e:
        logger.error("Image load error: %s", e)
        return jsonify({"error": f"Failed to load image: {e}"}), 422

    try:
        # Process all pages; merge results (primary = first page)
        results = []
        psm_candidates = [
            p.strip() for p in str(app.config.get("OCR_PSM_CANDIDATES", "6,11,3")).split(",")
            if p.strip().isdigit()
        ] or ["6"]

        for pil_img in images:
            candidates: list[dict] = []

            for variant_name, processed in preprocessor.build_variants(pil_img):
                for psm in psm_candidates:
                    cfg = f"--oem 3 --psm {psm}"
                    result = engine.extract(processed, config=cfg)
                    result["_variant"] = variant_name
                    result["_psm"] = psm
                    candidates.append(result)

            best = max(candidates, key=engine.score_result)
            best.pop("_variant", None)
            best.pop("_psm", None)
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
        return convert_from_bytes(data, dpi=app.config["UPLOAD_DPI"])
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
