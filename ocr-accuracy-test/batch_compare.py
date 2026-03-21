#!/usr/bin/env python3
"""Batch OCR comparison utility for OCR microservice benchmarking."""

from __future__ import annotations

import argparse
import csv
import json
import math
import mimetypes
import re
from pathlib import Path
from typing import Any

import requests

FIELDS = [
    "nik",
    "nama",
    "tempat_lahir",
    "tanggal_lahir",
    "rt_rw",
    "kelurahan",
    "kecamatan",
]


def normalize(value: str | None, field: str | None = None) -> str:
    if not value:
        return ""
    value = value.upper().strip()
    value = re.sub(r"\s+", " ", value)
    value = re.sub(r"[^A-Z0-9\-/ ]", "", value)

    # Canonicalize common administrative prefixes for fair place-name comparison.
    if field == "tempat_lahir":
        value = re.sub(r"^(KOTA|KABUPATEN)\s+", "", value)

    return value


def levenshtein_distance(a: str, b: str) -> int:
    if a == b:
        return 0
    if not a:
        return len(b)
    if not b:
        return len(a)

    previous = list(range(len(b) + 1))
    for i, ca in enumerate(a, start=1):
        current = [i]
        for j, cb in enumerate(b, start=1):
            insertions = previous[j] + 1
            deletions = current[j - 1] + 1
            substitutions = previous[j - 1] + (ca != cb)
            current.append(min(insertions, deletions, substitutions))
        previous = current
    return previous[-1]


def similarity(a: str, b: str) -> float:
    if not a and not b:
        return 1.0
    if not a or not b:
        return 0.0
    max_len = max(len(a), len(b))
    return 1.0 - (levenshtein_distance(a, b) / max_len)


def cer(reference: str, hypothesis: str) -> float:
    if not reference:
        return 0.0 if not hypothesis else 1.0
    return levenshtein_distance(reference, hypothesis) / len(reference)


def split_words(text: str) -> list[str]:
    return [w for w in text.split(" ") if w]


def wer(reference: str, hypothesis: str) -> float:
    ref_words = split_words(reference)
    hyp_words = split_words(hypothesis)
    if not ref_words:
        return 0.0 if not hyp_words else 1.0

    previous = list(range(len(hyp_words) + 1))
    for i, wr in enumerate(ref_words, start=1):
        current = [i]
        for j, wh in enumerate(hyp_words, start=1):
            insertions = previous[j] + 1
            deletions = current[j - 1] + 1
            substitutions = previous[j - 1] + (wr != wh)
            current.append(min(insertions, deletions, substitutions))
        previous = current
    return previous[-1] / len(ref_words)


def safe_div(num: float, den: float) -> float:
    return num / den if den else 0.0


def precision_recall_f1(reference: str, hypothesis: str) -> tuple[float, float, float]:
    if not reference and not hypothesis:
        return (1.0, 1.0, 1.0)
    if not reference or not hypothesis:
        return (0.0, 0.0, 0.0)

    ref_chars = list(reference)
    hyp_chars = list(hypothesis)

    # Character multiset overlap approximation.
    ref_count: dict[str, int] = {}
    hyp_count: dict[str, int] = {}
    for ch in ref_chars:
        ref_count[ch] = ref_count.get(ch, 0) + 1
    for ch in hyp_chars:
        hyp_count[ch] = hyp_count.get(ch, 0) + 1

    tp = 0
    for ch, count in ref_count.items():
        tp += min(count, hyp_count.get(ch, 0))

    fp = max(len(hyp_chars) - tp, 0)
    fn = max(len(ref_chars) - tp, 0)

    p = safe_div(tp, tp + fp)
    r = safe_div(tp, tp + fn)
    f1 = safe_div(2 * p * r, p + r) if (p + r) else 0.0
    return (p, r, f1)


def post_file(endpoint: str, secret: str, image_path: Path) -> dict[str, Any]:
    with image_path.open("rb") as f:
        mime, _ = mimetypes.guess_type(str(image_path))
        files = {"file": (image_path.name, f, mime or "image/jpeg")}
        headers = {"X-OCR-Secret": secret}
        response = requests.post(endpoint, headers=headers, files=files, timeout=60)
        response.raise_for_status()
        return response.json()


def map_ocr_field(field: str) -> str:
    if field == "tanggal_lahir":
        return "tgl_lahir"
    return field


def run(args: argparse.Namespace) -> int:
    with open(args.ground_truth, "r", encoding="utf-8") as f:
        gt = json.load(f)

    image_dir = Path(args.image_dir)
    out_csv = Path(args.output_csv)
    out_csv.parent.mkdir(parents=True, exist_ok=True)

    rows: list[dict[str, Any]] = []
    summary_scores: list[float] = []

    for image_name, fields in gt.items():
        image_path = image_dir / image_name
        if not image_path.exists():
            print(f"[WARN] Missing image: {image_path}")
            continue

        try:
            ocr = post_file(args.endpoint, args.secret, image_path)
        except Exception as exc:
            print(f"[ERROR] OCR request failed for {image_name}: {exc}")
            continue

        per_image_scores: list[float] = []

        for field in FIELDS:
            reference = normalize(str(fields.get(field, "")), field)
            ocr_value = normalize(str(ocr.get(map_ocr_field(field), "") or ""), field)

            sim = similarity(reference, ocr_value)
            per_image_scores.append(sim)
            summary_scores.append(sim)
            c = cer(reference, ocr_value)
            w = wer(reference, ocr_value)
            p, r, f1 = precision_recall_f1(reference, ocr_value)

            rows.append(
                {
                    "image": image_name,
                    "field": field,
                    "reference": reference,
                    "ocr": ocr_value,
                    "similarity": round(sim * 100, 2),
                    "cer": round(c, 4),
                    "wer": round(w, 4),
                    "precision": round(p, 4),
                    "recall": round(r, 4),
                    "f1": round(f1, 4),
                    "match_ge_90": int(sim >= 0.9),
                }
            )

        image_avg = safe_div(sum(per_image_scores), len(per_image_scores))
        print(f"[OK] {image_name}: avg_similarity={image_avg * 100:.2f}%")

    with out_csv.open("w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(
            f,
            fieldnames=[
                "image",
                "field",
                "reference",
                "ocr",
                "similarity",
                "cer",
                "wer",
                "precision",
                "recall",
                "f1",
                "match_ge_90",
            ],
        )
        writer.writeheader()
        writer.writerows(rows)

    if summary_scores:
        avg = (sum(summary_scores) / len(summary_scores)) * 100
        print(f"\nOverall average similarity: {avg:.2f}%")
        print(f"Compared fields: {len(summary_scores)}")
        print(f"CSV report: {out_csv}")
    else:
        print("No comparable samples found. Check image_dir and ground_truth.")

    return 0


def build_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(description="Batch compare OCR output against ground truth.")
    p.add_argument("--image-dir", required=True, help="Directory containing test images.")
    p.add_argument("--ground-truth", required=True, help="Path to JSON ground-truth file.")
    p.add_argument("--endpoint", default="http://127.0.0.1:5001/ocr/process", help="OCR endpoint.")
    p.add_argument("--secret", default="ocr_rahasia_sipadu_2026", help="OCR secret header value.")
    p.add_argument(
        "--output-csv",
        default="ocr-accuracy-test/results/batch_metrics.csv",
        help="Output CSV path.",
    )
    return p


if __name__ == "__main__":
    parser = build_parser()
    raise SystemExit(run(parser.parse_args()))
