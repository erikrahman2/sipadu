#!/usr/bin/env python3
"""Generate ground truth JSON template from image files."""

from __future__ import annotations

import argparse
import json
from pathlib import Path

SUPPORTED_EXTENSIONS = {".jpg", ".jpeg", ".png", ".tif", ".tiff", ".pdf"}
FIELDS = [
    "nik",
    "nama",
    "tempat_lahir",
    "tanggal_lahir",
    "alamat",
    "rt_rw",
    "kelurahan",
    "kecamatan",
]


def build_template(image_dir: Path) -> dict[str, dict[str, str]]:
    items: dict[str, dict[str, str]] = {}
    for file_path in sorted(image_dir.iterdir()):
        if not file_path.is_file():
            continue
        if file_path.suffix.lower() not in SUPPORTED_EXTENSIONS:
            continue
        items[file_path.name] = {field: "" for field in FIELDS}
    return items


def main() -> int:
    parser = argparse.ArgumentParser(description="Create a ground truth template JSON from image files.")
    parser.add_argument("--image-dir", required=True, help="Directory containing OCR test images.")
    parser.add_argument(
        "--output",
        default="ocr-accuracy-test/sample_data/ground_truth_template.json",
        help="Output JSON file path.",
    )
    args = parser.parse_args()

    image_dir = Path(args.image_dir)
    if not image_dir.exists() or not image_dir.is_dir():
        raise SystemExit(f"Invalid image directory: {image_dir}")

    template = build_template(image_dir)

    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    with output_path.open("w", encoding="utf-8") as f:
        json.dump(template, f, ensure_ascii=False, indent=2)

    print(f"Generated template entries: {len(template)}")
    print(f"Output: {output_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
