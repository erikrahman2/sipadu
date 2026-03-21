# Production Deploy Checklist - OCR Rollout

Use this checklist to roll out OCR changes safely with minimal risk.

## 0. Deployment Scope
- Service: OCR microservice and Laravel OCR integration.
- Target branch: main.
- Required gate: pre_merge_ocr_gate.ps1 must PASS.

## 1. Pre-Deploy (Must Pass)
- [ ] Code on server is synced to latest main.
- [ ] Run pre-merge quality gate:
  - Command: powershell -ExecutionPolicy Bypass -File d:\ProyekTA\pre_merge_ocr_gate.ps1
  - Must end with: OCR pre-merge gate PASSED. Safe to merge.
- [ ] Confirm repo clean:
  - Command: git status --short
  - Expected: no output.

## 2. Runtime Prerequisites (OCR Host)
- [ ] Tesseract binary exists:
  - Expected path (Windows): C:\Program Files\Tesseract-OCR\tesseract.exe
- [ ] Tessdata folder available:
  - Expected path: d:\ProyekTA\ocr-service\tessdata
- [ ] OCR language packs available:
  - Required: ind, eng, osd
- [ ] Environment variables are set:
  - OCR_SECRET_KEY=<secure value>
  - TESSERACT_CMD=C:\Program Files\Tesseract-OCR\tesseract.exe
  - TESSDATA_PREFIX=d:\ProyekTA\ocr-service\tessdata
  - OCR_LANG=ind+eng

## 3. Maintenance Window Prep
- [ ] Inform operators and set deployment window.
- [ ] Pause non-critical OCR ingestion jobs/queues temporarily.
- [ ] Backup logs and current OCR config (.env, service unit/script).
- [ ] Keep rollback command ready (see section 8).

## 4. Deploy Steps
- [ ] Pull latest code on production host.
- [ ] Restart OCR microservice process.
- [ ] Restart Laravel worker/processes if OCR job workers are running.

## 5. Immediate Smoke Tests (Blocking)
- [ ] OCR health endpoint returns ready=true:
  - GET /health
  - Expected: status=ok, missing_langs=[]
- [ ] Process one known KTP sample through /ocr/process.
- [ ] Confirm critical fields extracted:
  - nik is 16 digits
  - tgl_lahir format dd-mm-yyyy
- [ ] Confirm Laravel receives and persists OCR payload correctly.

## 6. Post-Deploy Validation (First 30 Minutes)
- [ ] Monitor OCR error rate (HTTP 4xx/5xx and exceptions).
- [ ] Monitor average OCR processing time.
- [ ] Monitor count of FAILED/PARTIAL/SUCCESS statuses.
- [ ] Verify no spike in empty nik or empty tgl_lahir.
- [ ] Check log channels:
  - storage/logs/ocr/ocr.log

## 7. Acceptance Criteria
Deploy is accepted only if all are true:
- [ ] Health check stable for 30 minutes.
- [ ] No runtime errors related to tesseract_cmd or missing language packs.
- [ ] Sample and live documents produce valid nik/tgl_lahir.
- [ ] OCR quality remains within safe threshold (overall >= 95% on gate dataset).

## 8. Rollback Plan (If Any Critical Failure)
- [ ] Stop OCR microservice.
- [ ] Revert to previous known-good release/commit.
- [ ] Restore previous env config.
- [ ] Restart OCR microservice.
- [ ] Re-run health check and one smoke test.
- [ ] Resume queues only after smoke tests pass.

## 9. Handover Notes
- [ ] Record deployment time, operator, and commit hash.
- [ ] Record gate result and smoke test result.
- [ ] Record any deviation and mitigation taken.

## 10. Quick Command Set
- Gate:
  - powershell -ExecutionPolicy Bypass -File d:\ProyekTA\pre_merge_ocr_gate.ps1
- Git clean check:
  - git status --short
- OCR health check:
  - curl -H "X-OCR-Secret: <secret>" http://127.0.0.1:5001/health

