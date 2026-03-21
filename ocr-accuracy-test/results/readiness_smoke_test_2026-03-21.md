# OCR Readiness Smoke Test (2026-03-21)

## Environment Setup
- Tesseract installed: `5.4.0.20240606`
- Local tessdata path: `D:\ProyekTA\ocr-service\tessdata`
- Languages detected in local tessdata: `eng`, `ind`, `osd`
- OCR service language config: `ind+eng`

## Automated Readiness Check
Command:

```powershell
cd ocr-service
.\check-readiness.ps1
```

Result:
- Tesseract binary: PASS
- Language pack: PASS
- Service health: PASS
- Overall: `OCR readiness: PASSED`

## API Smoke Test
Endpoint:
- `POST http://127.0.0.1:5001/ocr/process`

Test file:
- `ocr-accuracy-test/sample_data/images/ktp_synthetic_cv2.png`

Result:
- API reachable and returned JSON payload
- Engine version reported correctly
- Field extraction quality on synthetic image still low (most fields null)

## Remaining Gap Before Production
- Run real-image benchmark (minimum 20 KTP images, target 100+)
- Export metrics to CSV (F1, CER, WER, similarity per field)
- Recalibrate matching thresholds in `OCRValidationService`
- Validate throughput and retry behavior under queue load
