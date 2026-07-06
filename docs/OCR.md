# Dokumentasi Sistem OCR PA-Disdukcapil

## Gambaran Umum

Sistem OCR (Optical Character Recognition) digunakan untuk mengekstrak data dari dokumen KTP (Kartu Tanda Penduduk) secara otomatis. Dokumen yang diproses berupa file PDF yang di-upload melalui sistem pengajuan.

## Arsitektur Sistem

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Laravel App    │────▶│  OCR Service     │────▶│   Tesseract     │
│   (Producer)     │     │  (Flask/Python) │     │   OCR Engine    │
└─────────────────┘     └─────────────────┘     └─────────────────┘
        │                       │                       │
        ▼                       ▼                       ▼
   Queue (Redis)          PyMuPDF (PDF)         OpenCV Preprocessing
   OCRJob                + OpenCV              + Field Extraction
```

## Komponen

### 1. OCR Service (`ocr-service/app.py`)

Layanan REST API berbasis Flask yang membungkus Tesseract OCR 5.x dengan preprocessing OpenCV.

**Dependencies:**
```
flask==3.0.0
pytesseract==0.3.10
opencv-python-headless==4.9.0.80
Pillow==10.2.0
pdf2image==1.17.0
numpy==1.26.4
gunicorn==21.2.0
python-dotenv==1.0.0
```

**Endpoints:**
- `POST /ocr/process` - Proses file gambar/PDF
- `GET /health` - Health check
- `GET /version` - Info engine

### 2. Producer (`app/Jobs/OCRJob.php`)

Job Laravel yang mengirim dokumen ke OCR service melalui queue.

### 3. Validation Service (`app/Services/OCRValidationService.php`)

Membandingkan hasil OCR dengan data input manual.

## Alur OCR

```
┌──────────────┐
│ Upload File  │
│   (PDF)      │
└──────┬───────┘
       ▼
┌──────────────┐
│ Queue OCRJob │ (Redis/Queue)
└──────┬───────┘
       ▼
┌──────────────────────────────────┐
│     OCR Service Processing       │
│                                  │
│  1. Load PDF ────────────────────│──▶ PyMuPDF render @ 150 DPI
│                                  │
│  2. Preprocessing ───────────────│──▶ Grayscale + Adaptive Threshold
│     (3-10 variants)              │     + Denoising + Deskew
│                                  │
│  3. OCR Tesseract ──────────────│──▶ 5 PSM modes tested
│     (ind+eng lang)               │
│                                  │
│  4. Field Extraction ───────────│──▶ Regex patterns for NIK, Nama, etc.
│                                  │
│  5. NIK Validation ──────────────│──▶ Indonesian NIK rules validation
│                                  │
│  6. Scoring & Ranking ───────────│──▶ Score-based candidate selection
└──────┬───────────────────────────┘
       ▼
┌──────────────┐
│ Save Result   │
│ (OCRResult)  │
└──────┬───────┘
       ▼
┌──────────────┐
│ Validation   │ (Compare with manual input)
│ Compare OCR  │
│ vs Input    │
└──────────────┘
```

## Detail Processing

### PDF Conversion
- Library: **PyMuPDF (fitz)**
- DPI: **150** (konfigurasi via `PDF_DPI` env)
- Page: Hanya halaman pertama (KTP single-page document)

### Preprocessing Pipeline

3-10 variant preprocessing dihasilkan untuk setiap gambar:

| Variant | Deskripsi |
|---------|-----------|
| `adaptive` | Grayscale + Adaptive Threshold |
| `contrast_binary` | High-contrast + Binary threshold |
| `sharp_otsu` | Sharpened + OTSU threshold |
| `clahe_morph` | CLAHE + Morphological operations |
| `equalized_clahe` | Histogram equalization + CLAHE |
| `ultra_denoise_clahe` | Strong denoise + heavy CLAHE |
| `ultra_contrast_otsu` | Super contrast + OTSU |
| `upscaled_otsu` | 2x upscale + OTSU |
| `bilateral_otsu` | Bilateral filter + OTSU |
| `blackhat_otsu` | Blackhat morphology |

### PSM Modes (Page Segmentation Modes)

5 mode PSM diuji:
- **PSM 6**: Fully automatic page segmentation, but no OSD (Default)
- **PSM 4**: Assume a single column of text
- **PSM 11**: Sparse text
- **PSM 8**: Sparse text, OSD only
- **PSM 13**: Raw line

### Field Extraction

Field yang diekstrak dari KTP:

| Field | Pattern | Label Headers |
|-------|---------|---------------|
| NIK | 16 digit | NIK, Nomor Induk Kependudukan |
| Nama | Text | Nama, Nama Lengkap |
| Tanggal Lahir | DD-MM-YYYY | Tanggal Lahir, TTL |
| Tempat Lahir | Text | Tempat Lahir |
| Jenis Kelamin | Text | Jenis Kelamin, JK |
| Alamat | Text | Alamat, Jalan |
| RT/RW | DD/DD | RT/RW |
| Kelurahan | Text | Kel/Desa, Kelurahan |
| Kecamatan | Text | Kecamatan |
| Kabupaten | Text | Kabupaten, Kota |
| Provinsi | Text | Provinsi |

### NIK Validation

Validasi struktur NIK Indonesia:

1. **Kode Provinsi** (digit 1-2): 01-34
2. **Tanggal Lahir** (digit 6-11): YYMMDD, dengan penyesuaian:
   - Tanggal perempuan: +40
   - Tanggal perempuan (alternatif): +60
3. **Serial Number** (digit 10-16): 000001-999999

### Scoring System

```
Score = (NIK valid × 5.0)
      + (Tanggal Lahir × 4.0)
      + (Nama × 3.0)
      + (Optional fields × 0.6 each)
      + (Confidence average)
      + (NIK structure validation boost)
      + (Text volume bonus)
```

Early stopping: Jika score ≥ 10.0 dengan NIK valid, hentikan testing.

## Field Extractor Patterns

```python
FIELD_HEADERS = {
    "nik": ["nik", "nomor induk kependudukan", "no nik", "nik."],
    "nama": ["nama", "nama lengkap", "name"],
    "tgl_lahir": ["tanggal lahir", "tgl lahir", "ttl"],
    "tempat_lahir": ["tempat lahir", "tempat/tgl"],
    "alamat": ["alamat", "alamat:", "jalan"],
    "rt_rw": ["rt/rw", "rt / rw"],
    "kelurahan": ["kel/desa", "kelurahan", "desa"],
    "kecamatan": ["kecamatan", "kec."],
    "kabupaten": ["kabupaten", "kota"],
    "provinsi": ["provinsi"],
}
```

## Validasi OCR

### Comparison Service

Setelah OCR selesai, hasil dibandingkan dengan data input manual dari:

1. **Case Model** - Data pemohon dari form case
2. **Public Submission** - Data dari form pengajuan publik

### Field Comparison

| Field | Threshold | Method |
|-------|----------|--------|
| NIK | 65% | Character-level similarity |
| Nama | 60% | Normalized string comparison |
| Alamat | 50% | Normalized string comparison |
| RT/RW | 80% | Exact match |
| Kelurahan | 40% | Normalized string comparison |
| Kecamatan | 50% | Normalized string comparison |

### Validation Status

| Status | Kriteria |
|--------|----------|
| **MATCH** | Semua field kritis match |
| **PARTIAL_MATCH** | Beberapa field match, sisanya partial |
| **MISMATCH** | Field kritis tidak match |

## Hasil OCR (Statistik)

## Hasil OCR (Data Lengkap)

### Statistik OCR

| Metric | Value |
|--------|-------|
| Total Dokumen | 126 |
| Success | 67 (53.2%) |
| Partial | 59 (46.8%) |
| Failed | 0 (0%) |
| Avg Confidence | 77.3% |
| Avg Processing Time | 26.4 detik |

### Statistik Validasi

| Status | Count | Percentage |
|--------|-------|------------|
| Match | 67 | 53.2% |
| Partial Match | 49 | 38.9% |
| Mismatch | 10 | 7.9% |

### Statistik per Format

| Format | Total | Success | Partial | Avg Confidence |
|--------|-------|---------|---------|----------------|
| PDF | 42 | 12 (28.6%) | 30 (71.4%) | 70.2% |
| PNG | 42 | 28 (66.7%) | 14 (33.3%) | 82.1% |
| JPEG | 42 | 27 (64.3%) | 15 (35.7%) | 79.6% |

### Processing Time

| Metric | Time |
|--------|------|
| Average | 26.4 detik |
| Minimum | 5.6 detik |
| Maximum | 132.9 detik |

## Contoh Hasil OCR (Top 30 by Confidence)

| ID | Type | Format | NIK | Nama | Status | Conf |
|----|------|--------|------|------|--------|------|
| 220 | KTP_SUAMI | PDF | 510203150595000 | DEWI ANGGRAINI | PARTIAL | 96.4% |
| 207 | KTP_ISTRI | PDF | 3172055109820005 | YUSTI EFRA | SUCCESS | 96.0% |
| 144 | KTP_ISTRI | PNG | 317205610982000 | YUSTI EFRA | PARTIAL | 95.4% |
| 110 | KTP_ISTRI | JPEG | 350715112900001 | EVELYNE SANDJOJO | PARTIAL | 94.8% |
| 97 | KTP_SUAMI | JPEG | 3173040508000012 | BUDI SANTOSO | SUCCESS | 93.8% |
| 143 | KTP_SUAMI | PNG | 3173040508000012 | BUDI SANTOSO | SUCCESS | 93.8% |
| 130 | KTP_SUAMI | JPEG | 1277054899990001 | PUSPITA SARI | SUCCESS | 93.8% |
| 160 | KTP_ISTRI | PNG | 347501318900005 | DESI LAKSMI SARI | PARTIAL | 93.7% |
| 108 | KTP_SUAMI | JPEG | 150012502950002 | FAJAR ARIANTO | PARTIAL | 93.5% |
| 120 | KTP_ISTRI | JPEG | 317501318900005 | DESI LAKSMI SARI | PARTIAL | 93.4% |
| 99 | KTP_SUAMI | JPEG | 0947120849038697 | MEISYA SAPUTRI | SUCCESS | 92.2% |
| 172 | KTP_ISTRI | PNG | 3315104109850002 | SM. WAYYURLA MD | SUCCESS | 91.9% |
| 147 | KTP_SUAMI | PNG | 15001502950002 | FAJAR ARIANTO | PARTIAL | 91.6% |
| 167 | KTP_SUAMI | PNG | 3501120101987654 | ANDI PRATAMA | SUCCESS | 91.2% |
| 123 | KTP_SUAMI | JPEG | 3501120101987654 | ANDI PRATAMA | SUCCESS | 91.2% |
| 148 | KTP_ISTRI | PNG | 3507155112900001 | - | SUCCESS | 90.2% |
| 235 | KTP_ISTRI | PDF | 331510410940002 | SM. WAYYURLA MD | PARTIAL | 90.0% |
| 102 | KTP_ISTRI | JPEG | 3509014503958000 | SITI AMALIA DEWI | SUCCESS | 89.9% |
| 142 | KTP_ISTRI | PNG | 3509014503958000 | SITI AMALIA DEWI | SUCCESS | 89.9% |
| 170 | KTP_ISTRI | PNG | 3201126112300006 | DESI RAHMAWATI | SUCCESS | 89.8% |
| 127 | KTP_ISTRI | JPEG | 3201126112300006 | DESI RAHMAWATI | SUCCESS | 89.8% |
| 145 | KTP_SUAMI | PNG | 0947120849038697 | MEISYA SAPUTRI | SUCCESS | 89.8% |
| 114 | KTP_ISTRI | JPEG | 1219014705698003 | MASITOH DARWINA SIREGAR | SUCCESS | 89.5% |
| 154 | KTP_ISTRI | PNG | 1219014705698003 | MASITOH DARWINA SIREGAR | SUCCESS | 89.5% |
| 230 | KTP_SUAMI | PDF | 3501120101987654 | ANDI PRATAMA | SUCCESS | 89.5% |
| 138 | KTP_ISTRI | PNG | 6105101234567890 | SIT NURHAINY | SUCCESS | 89.4% |
| 209 | KTP_ISTRI | PDF | 3514515640994000 | MARIA GERALCINE ANGGRAENI | SUCCESS | 89.3% |
| 128 | KTP_SUAMI | JPEG | 102045801820003 | AISYAH PUTRI SARI | PARTIAL | 89.2% |
| 208 | KTP_SUAMI | PDF | 0947120849038697 | MEISYA SAPUTRI | SUCCESS | 89.2% |
| 168 | KTP_ISTRI | PNG | 102045801820003 | AISYAH PUTRI SARI | PARTIAL | 89.2% |

## Semua Hasil OCR (126 Dokumen)

### Success (67 Dokumen) - Confidence > 75%

| ID | Type | Format | NIK | Nama | Conf | Time |
|----|------|--------|-----|------|------|------|
| 207 | KTP_ISTRI | PDF | 3172055109820005 | YUSTI EFRA | 96.0% | 95.5s |
| 97 | KTP_SUAMI | JPEG | 3173040508000012 | BUDI SANTOSO | 93.8% | 9.4s |
| 143 | KTP_SUAMI | PNG | 3173040508000012 | BUDI SANTOSO | 93.8% | 10.5s |
| 130 | KTP_SUAMI | JPEG | 1277054899990001 | PUSPITA SARI | 93.8% | 12.3s |
| 99 | KTP_SUAMI | JPEG | 0947120849038697 | MEISYA SAPUTRI | 92.2% | 7.3s |
| 172 | KTP_ISTRI | PNG | 3315104109850002 | SM. WAYYURLA MD | 91.9% | 10.6s |
| 167 | KTP_SUAMI | PNG | 3501120101987654 | ANDI PRATAMA | 91.2% | 8.9s |
| 123 | KTP_SUAMI | JPEG | 3501120101987654 | ANDI PRATAMA | 91.2% | 8.9s |
| 148 | KTP_ISTRI | PNG | 3507155112900001 | - | 90.2% | 9.4s |
| 102 | KTP_ISTRI | JPEG | 3509014503958000 | SITI AMALIA DEWI | 89.9% | 8.9s |
| 142 | KTP_ISTRI | PNG | 3509014503958000 | SITI AMALIA DEWI | 89.9% | 9.9s |
| 170 | KTP_ISTRI | PNG | 3201126112300006 | DESI RAHMAWATI | 89.8% | 7.9s |
| 127 | KTP_ISTRI | JPEG | 3201126112300006 | DESI RAHMAWATI | 89.8% | 10.3s |
| 145 | KTP_SUAMI | PNG | 0947120849038697 | MEISYA SAPUTRI | 89.8% | 8.5s |
| 114 | KTP_ISTRI | JPEG | 1219014705698003 | MASITOH DARWINA SIREGAR | 89.5% | 17.5s |
| 154 | KTP_ISTRI | PNG | 1219014705698003 | MASITOH DARWINA SIREGAR | 89.5% | 14.7s |
| 230 | KTP_SUAMI | PDF | 3501120101987654 | ANDI PRATAMA | 89.5% | 21.6s |
| 138 | KTP_ISTRI | PNG | 6105101234567890 | SIT NURHAINY | 89.4% | 8.2s |
| 209 | KTP_ISTRI | PDF | 3514515640994000 | MARIA GERALCINE ANGGRAENI | 89.3% | 22.0s |
| 208 | KTP_SUAMI | PDF | 0947120849038697 | MEISYA SAPUTRI | 89.2% | 13.7s |
| 168 | KTP_ISTRI | PNG | 102045801820003 | AISYAH PUTRI SARI | 89.2% | 24.2s |
| 128 | KTP_SUAMI | JPEG | 102045801820003 | AISYAH PUTRI SARI | 89.2% | 27.8s |
| 135 | KTP_ISTRI | PNG | 3325075811930001 | ANDI PRASETYO | 88.3% | 10.2s |
| 100 | KTP_ISTRI | JPEG | 3325075811930001 | ANDI PRASETYO | 88.2% | 6.9s |
| 225 | KTP_ISTRI | PDF | 3175715423990855 | INDAH LESTARI PUSPA | 88.7% | 20.7s |
| 221 | KTP_ISTRI | PDF | 6041044094800032 | SITI AISYAH NUR | 84.0% | 46.9s |
| 223 | KTP_ISTRI | PDF | 3275025318900005 | DESI LAKSMI SARI | 86.8% | 23.2s |
| 222 | KTP_SUAMI | PDF | 3147406051197000 | AGUNG ADE MULYONO | 71.9% | 16.2s |
| 228 | KTP_SUAMI | PDF | 3175031008930005 | BUDI SANTOSO | 81.6% | 23.3s |
| 224 | KTP_SUAMI | PDF | 2091525049600003 | BA FAJAR RAHMAN | 74.3% | 13.5s |

### Partial (59 Dokumen) - Confidence 50-75%

| ID | Type | Format | NIK | Nama | Conf | Issue |
|----|------|--------|-----|------|------|-------|
| 220 | KTP_SUAMI | PDF | 510203150595000 | DEWI ANGGRAINI | 96.4% | Name partial |
| 233 | KTP_SUAMI | PDF | 3147304070790000 | RINUSYAHRIL | 50.3% | NIK error, name corrupted |
| 232 | KTP_ISTRI | PDF | 3201126112300006 | DESI RAHMAWATI | 73.5% | Name has noise |
| 231 | KTP_ISTRI | PDF | 10204801820003 | AISYAH PUTRI SARI | 69.6% | NIK incomplete |
| 229 | KTP_ISTRI | PDF | 180218521279000 | - | 82.4% | NIK/name missing |
| 227 | KTP_ISTRI | PDF | - | CINTYA KUSUMA | 86.7% | NIK missing |
| 226 | KTP_SUAMI | PDF | 3374072105990002 | ANGGA PRATAMA | 60.1% | Name corrupted |
| 214 | KTP_ISTRI | PDF | 3578105407950003 | SITENUR AINI | 47.5% | Heavy corruption |
| 211 | KTP_SUAMI | PDF | 15001202950002 | FAJAR ARIANTO | 89.0% | NIK incomplete |
| 200 | KTP_SUAMI | PNG | 1673011505920001 | MUHAMMAD RIZKY PRATAMA | 61.8% | Address partial |
| 199 | KTP_SUAMI | JPEG | 172031505890005 | BUDI SANTOSO | 51.5% | Heavy corruption |
| 196 | KTP_SUAMI | JPEG | 1604015203940003 | SUTRISNO HAD | 53.9% | Name corrupted |
| 206 | KTP_SUAMI | JPEG | 3173040508000012 | BUDI SANTOSO | 79.1% | Address partial |
| 205 | KTP_SUAMI | JPEG | - | - | 67.1% | NIK/name missing |
| 204 | KTP_ISTRI | JPEG | 35090450390002 | SITI AMALIA DEWI | 65.6% | NIK incomplete |
| 203 | KTP_SUAMI | JPEG | 550402203910003 | MARDIANTO | 54.7% | Name corrupted |
| 197 | KTP_ISTRI | JPEG | 6720351089200035 | SITI NURHALIZA | 62.3% | Heavy corruption |
| 169 | KTP_SUAMI | PNG | 7317304070790000 | RINUSYAHRIL | 50.1% | Heavy corruption |
| 166 | KTP_ISTRI | JPEG | 140214512790006 | - | 73.7% | NIK/name partial |
| 164 | KTP_ISTRI | JPEG | - | CINTYA KUSUMA | 85.3% | NIK missing |
| 163 | KTP_SUAMI | JPEG | 337407210599000 | - | 87.8% | Name missing |
| 158 | KTP_ISTRI | JPEG | - | SITI AISYAH NUR | 73.3% | NIK missing |
| 156 | KTP_ISTRI | PNG | 5331811611201000 | NABILA MEGA AYU | 62.6% | Name partial |
| 152 | KTP_ISTRI | JPEG | 5357810540795000 | SITI NUR AINI | 55.8% | Heavy corruption |
| 146 | KTP_ISTRI | PNG | 3515155409980000 | MARIA GERALDINE | 64.0% | Name partial |
| 140 | KTP_ISTRI | JPEG | 2351512300490005 | DIANA PUSPITA | 56.4% | Heavy corruption |
| 139 | KTP_SUAMI | JPEG | 1673011505920001 | MUHAMMAD RIZKY PRATAMA | 62.1% | Name partial |
| 137 | KTP_SUAMI | JPEG | 3209011503950001 | BUDI SANTOSO | 61.4% | Name partial |
| 136 | KTP_SUAMI | PNG | 50402203920003 | MARDIANTO | 82.4% | NIK incomplete |
| 134 | KTP_SUAMI | JPEG | 1604015203940003 | SUTRISNO HAD | 52.5% | Heavy corruption |
| 133 | KTP_ISTRI | JPEG | 1672035108920003 | SITINURHALIZA | 58.0% | Heavy corruption |
| 131 | KTP_SUAMI | JPEG | 1672031505890005 | BUDI SANTOSO | 53.1% | Heavy corruption |
| 125 | KTP_SUAMI | JPEG | 7317304070790000 | RINUSYAHRIL | 50.1% | Heavy corruption |
| 119 | KTP_SUAMI | PNG | 337407210599000 | - | 87.8% | Name missing |
| 117 | KTP_SUAMI | PNG | 3173056402120008 | ALDI PRAYOGA | 62.7% | Name partial |
| 112 | KTP_ISTRI | PNG | 5357810540795000 | SITI NUR AINI | 55.8% | Heavy corruption |

## Case Validation Examples

### Recent Cases with OCR Results

| Case Number | KTP SUAMI | KTP ISTRI | Status |
|-------------|-----------|-----------|--------|
| CASE-20260705-RHAGRKB3 | RINUSYAHRIL (64.1%) | DESI RAHMAWATI (100%) | Partial |
| CASE-20260705-W5CAA6BJ | ANDI PRATAMA (98.4%) | AISYAH PUTRI SARI (80.3%) | Partial |
| CASE-20260705-YKDMGVH9 | BUDI SANTOSO (100%) | - (70%) | Partial |
| CASE-20260705-URS38CIX | ANGGA PRATAMA (100%) | CINTYA KUSUMA (81.7%) | Success |
| CASE-20260705-2CANZJ9F | BA FAJAR RAHMAN (75%) | INDAH LESTARI (95.8%) | Success |
| CASE-20260705-MNCZPEJK | DEWI ANGGRAINI (99.2%) | SITI AISYAH NUR (75%) | Partial |
| CASE-20260705-HVR4RVUJ | SANTOSO PRABOWO (100%) | NABILA MEGA AYU (97.6%) | Success |
| CASE-20260705-SI3W14OB | RIZKY FAUZAN (96.9%) | MASITOH DARWINA (93.2%) | Success |

## Known Issues

### 1. Document Quality Issues

Kualitas dokumen sangat mempengaruhi hasil OCR:

| Issue | Cause | Solution |
|-------|-------|----------|
| NIK salah 1-2 digit | Kualitas scan rendah | Request upload ulang |
| Nama corrupted (`:RINUSYAHRIL`) | Noise/grafiti di KTP | Request upload ulang |
| Field kosong | Background gelap | Request scan lebih terang |

### 2. Common OCR Errors

- **6 ↔ 8** - Digit confusion
- **0 ↔ O** - Character confusion
- **1 ↔ I ↔ l** - Character confusion
- **2 ↔ Z** - Character confusion
- **Noise characters** - `:`, `|`, `"`, `'`, `~`混入 teks

## Konfigurasi

### Environment Variables

```bash
# OCR Service
OCR_SECRET_KEY=your_secret_key
TESSERACT_CMD=C:\Program Files\Tesseract-OCR\tesseract.exe
TESSDATA_PREFIX=D:\ProyekTA\ocr-service\tessdata\
UPLOAD_DPI=300
PDF_DPI=150
OCR_PSM_CANDIDATES=6,11
OCR_LANG=ind+eng

# Laravel
OCR_SERVICE_URL=http://localhost:5001
OCR_TIMEOUT=180
```

### Document Type Profiles

| Type | DPI | Contrast | Denoise | Profile |
|------|-----|----------|---------|---------|
| KTP_SUAMI | 300 | +30% | +5 | Ultra |
| KTP_ISTRI | 300 | +30% | +5 | Ultra |
| KTP | 300 | +30% | +5 | Ultra |
| KK | 300 | 0 | 0 | Standard |

## Command CLI

### Reprocess OCR

```bash
# Reprocess failed/partial
php artisan ocr:reprocess-failed

# Reprocess all KTP
php artisan ocr:reprocess-failed --ktp

# Reprocess specific documents
php artisan ocr:reprocess-failed --id=123 --id=456

# Reprocess all including success (after engine upgrade)
php artisan ocr:reprocess-failed --full
```

### Fix Missing Validations

```bash
php artisan ocr:reprocess-failed --fix-missing
```

## Known Issues & Limitations

### 1. PDF Quality

Kualitas OCR sangat dipengaruhi oleh kualitas PDF asli. PDF yang di-scan dengan resolusi rendah menghasilkan akurasi rendah.

**Solusi:** Pastikan dokumen di-scan pada minimal 150 DPI.

### 2. NIK Confusion

Beberapa digit NIK sering tertukar karena kemiripan visual:
- `6` ↔ `8`
- `0` ↔ `O`
- `1` ↔ `I` ↔ `l`
- `2` ↔ `Z`

**Solusi:** Validasi NIK menggunakan aturan Indonesia untuk filter.

### 3. Nama dengan特殊字符

Nama yang mengandung karakter khusus atau accent sering salah dibaca.

**Solusi:** Post-processing cleaning untuk hapus karakter garbage.

### 4. KTP_ISTRI Quality

KTP_ISTRI sering memiliki kualitas lebih rendah dibanding KTP_SUAMI, kemungkinan karena cara penyimpanan/digitalisasi.

**Solusi:** Profile preprocessing yang lebih agresif untuk KTP_ISTRI.

## Future Improvements

1. **Multi-page processing** - Proses semua halaman PDF
2. **Better preprocessing** - Adaptive preprocessing berdasarkan quality detection
3. **Fuzzy NIK matching** - Toleransi 1-2 digit salah
4. **VLM integration** - Gunakan Vision Language Model untuk OCR yang lebih akurat
5. **Quality score feedback** - Beri tahu user jika dokumen berkualitas rendah

## Struktur File

```
ProyekTA/
├── app/
│   ├── Jobs/
│   │   └── OCRJob.php              # Queue job for OCR processing
│   ├── Models/
│   │   ├── OcrResult.php           # OCR result storage
│   │   ├── OcrValidation.php        # Validation results
│   │   └── OcrJob.php             # Job tracking
│   └── Services/
│       ├── OCRService.php          # Main OCR service
│       └── OCRValidationService.php # Comparison service
├── config/
│   └── ocr.php                    # Laravel OCR config
├── ocr-service/
│   ├── app.py                     # Flask OCR microservice
│   ├── app.py.bak                 # Backup
│   ├── requirements.txt           # Python dependencies
│   ├── Dockerfile                 # Docker image
│   ├── .env                       # Environment config
│   └── tessdata/                  # Tesseract language files
│       ├── ind.traineddata         # Indonesian
│       └── eng.traineddata         # English
└── resources/views/
    └── dashboard/
        ├── review/
        │   └── show.blade.php      # Validation UI
        └── ocr-result.blade.php    # OCR result display
```

## Backup OCR Data

Data OCR sangat penting! Gunakan command berikut untuk backup secara rutin:

### Manual Backup

```bash
# Backup semua hasil OCR
php artisan ocr:backup

# Backup ke custom path
php artisan ocr:backup --path=/external/backup/ocr

# Backup dengan compression
php artisan ocr:backup --compress
```

### Scheduled Backup (Otomatis)

Backup otomatis sudah dikonfigurasi di `routes/console.php`:

| Schedule | Waktu | Keterangan |
|----------|-------|------------|
| Daily | 00:00 (midnight) | Backup setiap hari |
| Weekly | Minggu 02:00 | Backup mingguan |

### Start Scheduler

```bash
# Jalankan scheduler (untuk production, gunakan supervisor/cron)
php artisan schedule:work
```

### Backup Location

```
storage/backups/ocr/
├── 2026-07-05_213653/
│   ├── ocr_results_2026-07-05_213653.json   (274.71 KB)
│   ├── ocr_results_2026-07-05_213653.csv    (32.17 KB)
│   ├── ocr_results_latest.json              (symlink)
│   └── ocr_results_latest.csv               (symlink)
```

### Backup Statistics

| Metric | Value |
|--------|-------|
| Total Records | 126 |
| JSON Size | 274.71 KB |
| CSV Size | 32.17 KB |

## Troubleshooting

### OCR Service tidak running

```bash
# Check if service is running
netstat -ano | grep ":5001"

# Restart service
cd ocr-service
python app.py
```

### Tesseract not found

Pastikan Tesseract terinstall dan path benar di `.env`:
```
TESSERACT_CMD=C:\Program Files\Tesseract-OCR\tesseract.exe
```

### PDF processing error

Pastikan PyMuPDF terinstall:
```bash
pip install PyMuPDF
```

### Queue not processing

```bash
# Start queue worker
php artisan queue:work --queue=ocr --tries=4

# Check failed jobs
php artisan queue:failed
```

## Referensi

- [Tesseract OCR Documentation](https://github.com/tesseract-ocr/tessdoc)
- [PyMuPDF Documentation](https://pymupdf.readthedocs.io/)
- [OpenCV Documentation](https://docs.opencv.org/)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
