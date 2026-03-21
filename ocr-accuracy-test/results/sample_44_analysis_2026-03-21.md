# OCR Analysis - sample (44).jpg

Test Date: 2026-03-21 12:35:24
Preprocessing: adaptive

## Field Summary
- NIK: 81.2% (partial, digit substitution on multiple positions)
- NAMA: 42.9% (major character confusion)
- TEMPAT_LAHIR: 100.0% (match)
- TANGGAL_LAHIR: 0.0% (not extracted)
- RT_RW: 57.1% (numeric pair mismatch)
- KELURAHAN: 76.9% (token merge)
- KECAMATAN: 58.8% (token drop/merge)

## Aggregate
- Total fields: 7
- Matched (>=90%): 1
- Average similarity: 59.6%
- Classification with current rule: MISMATCH

## Main Failure Modes
- Numeric confusion for NIK/RT_RW (1<->4, 0<->9 style substitutions)
- Name and district fields suffer heavy token distortion
- Date field dropped entirely (likely layout or label-detection miss)

## Recommended Iteration
1. Add strict post-check for NIK candidate scoring by checksum-like heuristics and nearest candidate ranking.
2. Add fallback date extraction using regex over full OCR text before declaring empty.
3. Increase weight of label-local extraction window for NAMA/KELURAHAN/KECAMATAN.
4. Re-run same image with `otsu` and `grayscale` preprocessing and compare per-field metrics.
5. Keep current production gate as MANUAL_REVIEW/MISMATCH for this quality band.
