# OCR System - Status & Setup Guide

## ✅ Status Komponen

### 1️⃣ ReBAC & Neo4j ✅ **COMPLETE**
- Neo4j Desktop running at localhost:7687
- GraphService bugs fixed (property persistence + path traversal)
- ReBACService tested and working
- All relationships created correctly
- Cache system working (4.8x faster)
- **Status**: Production-ready

### 2️⃣ OCR System 🔄 **IN PROGRESS**

#### ✅ Completed:
- [x] Python microservice code (`ocr-service/app.py`)
- [x] Laravel OCRService (`app/Services/OCRService.php`)
- [x] OCRJob queue handler (`app/Jobs/OCRJob.php`)
- [x] Database models (OcrJob, OcrResult)
- [x] Configuration files (`config/ocr.php`)
- [x] Python dependencies installed
- [x] Documentation created (`OCR_DESIGN.md`)
- [x] Tesseract OCR engine installed (Windows, v5.4.0)
- [x] Indonesian language data available via local tessdata (`ocr-service/tessdata`)
- [x] OCR service readiness script created (`ocr-service/check-readiness.ps1`)
- [x] Health endpoint now validates required OCR languages (`/health`)

#### ⚠️ Pending:
- [ ] End-to-end accuracy test with real KTP dataset (20+ image minimum)
- [ ] Field-level threshold calibration from measured metrics (F1/CER/WER)
- [ ] Queue worker supervision in production (Supervisor/PM2/NSSM)
- [ ] Monitoring and alerting for OCR failures/confidence degradation

---

## 📥 Instalasi Tesseract OCR (Windows)

### Step 1: Download Tesseract

Download installer dari:
**https://github.com/UB-Mannheim/tesseract/wiki**

Direct link (recommended):
https://digi.bib.uni-mannheim.de/tesseract/tesseract-ocr-w64-setup-5.3.3.20231005.exe

### Step 2: Install Tesseract

1. Run installer `tesseract-ocr-w64-setup-5.3.3.20231005.exe`
2. **PENTING**: Saat instalasi, centang **"Additional Language Data"**
3. Pilih bahasa: **Indonesian (ind)** + English (eng)
4. Install path default: `C:\Program Files\Tesseract-OCR`

### Step 3: Set Environment Variable

```powershell
# Tambahkan ke PATH
$tesseractPath = "C:\Program Files\Tesseract-OCR"
[Environment]::SetEnvironmentVariable(
    "Path",
    [Environment]::GetEnvironmentVariable("Path", "Machine") + ";$tesseractPath",
    "Machine"
)

# Set TESSERACT_CMD
[Environment]::SetEnvironmentVariable(
    "TESSERACT_CMD",
    "$tesseractPath\tesseract.exe",
    "Machine"
)

# Restart PowerShell setelah set environment variable
```

### Step 4: Verify Installation

```powershell
# Test Tesseract
tesseract --version
# Expected output:
# tesseract 5.3.3
#  leptonica-1.83.0
#  ...

# Check installed languages
tesseract --list-langs
# Expected output:
# List of available languages in "C:/Program Files/Tesseract-OCR/tessdata/" (3):
# eng
# ind  <-- Indonesian (WAJIB ada)
# osd
```

### Step 5: Update .env

```env
OCR_SERVICE_URL=http://localhost:5001
OCR_SECRET_KEY=ocr_rahasia_sipadu_2026
TESSERACT_CMD=C:\Program Files\Tesseract-OCR\tesseract.exe
```

---

## 🚀 Menjalankan OCR Service

### Start OCR Microservice

```powershell
cd ocr-service
python app.py
```

Expected output:
```
 * Serving Flask app 'app'
 * Running on http://127.0.0.1:5001
 * Press CTRL+C to quit
```

### Health Check

Buka browser atau curl:
```powershell
curl http://localhost:5001/health
```

Expected response:
```json
{
  "status": "ok",
  "tesseract_version": "5.3.3",
  "opencv_version": "4.9.0"
}
```

---

## 🧪 Testing OCR

### Test 1: Health Check
```powershell
curl http://localhost:5001/health
```

### Test 2: Version Info
```powershell
curl http://localhost:5001/version
```

### Test 3: Process Sample Image

**Buat sample image atau gunakan KTP test:**
```powershell
# Jika punya sample KTP image:
curl -X POST http://localhost:5001/ocr/process `
  -H "X-OCR-Secret: ocr_rahasia_sipadu_2026" `
  -F "file=@path\to\ktp_sample.jpg"
```

Expected response:
```json
{
  "nik": "3174010101900001",
  "nama": "AHMAD WARGA",
  "tgl_lahir": "01-01-1990",
  "confidence": {
    "nik": 0.95,
    "nama": 0.88
  },
  "engine_version": "tesseract-5.3.3"
}
```

---

## 📊 Architecture Summary

```
┌────────────────────────────────────────────────────────────┐
│ SIPADU SYSTEM ARCHITECTURE                                 │
├────────────────────────────────────────────────────────────┤
│                                                            │
│  ┌─────────────┐     ┌──────────────┐     ┌────────────┐ │
│  │   Laravel    │────>│   MySQL      │     │   Redis    │ │
│  │   (Web App)  │     │   Database   │     │   Cache    │ │
│  └──────┬───────┘     └──────────────┘     └────────────┘ │
│         │                                                  │
│         ├──> Neo4j (ReBAC Graph) ✅ WORKING                │
│         │                                                  │
│         └──> OCR Service (Python) ⚠️ NEEDS TESSERACT      │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

## ✅ Component Status

| Component | Status | Notes |
|-----------|--------|-------|
| **Laravel App** | ✅ Working | Running on Laragon |
| **MySQL** | ✅ Working | Database seeded |
| **Redis** | ✅ Working | Cache configured |
| **Neo4j** | ✅ Working | ReBAC tested & working |
| **OCR Service** | ⚠️ Needs Setup | Waiting for Tesseract |

---

## 🎯 Next Steps

1. **Install Tesseract OCR** (see instructions above)
2. **Start OCR service** (`python ocr-service/app.py`)
3. **Test health check** (`curl http://localhost:5001/health`)
4. **Create sample test** with real KTP image
5. **Integration test** from Laravel to OCR service

---

## 📚 Documentation Files

| File | Purpose |
|------|---------|
| `PANDUAN.md` | Complete system guide |
| `OCR_DESIGN.md` | **NEW** - OCR architecture & design |
| `MIGRASI_LARAGON.md` | Laragon migration guide |
| `DASHBOARD_GUIDE.md` | Dashboard user guide |
| `SKEMA_VALIDASI_NIK.md` | NIK validation schema |

---

**Status Update: 10 Maret 2026**
- ReBAC System: ✅ **100% Complete**
- OCR System: 📝 **Documented, awaiting Tesseract installation**
