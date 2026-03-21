# 🖥️ OCR Testing GUI Application

Aplikasi GUI untuk testing akurasi OCR Tesseract dengan interface user-friendly.

## 🎯 Fitur

✅ **Input Ground Truth**: Form untuk input data manual (NIK, Nama, Alamat, dll)  
✅ **Upload Gambar**: Upload dan preview KTP  
✅ **Preprocessing Options**: Pilih metode preprocessing (Grayscale, Adaptive, Otsu)  
✅ **OCR Extraction**: Otomatis extract text dari gambar  
✅ **Metrics Calculation**: Hitung Similarity, CER, WER, F1 Score  
✅ **Visual Comparison**: Field-by-field comparison dengan color indicators  
✅ **Export Results**: Export ke CSV atau JSON  
✅ **Save/Load**: Save dan load ground truth dari JSON  

## 📦 Dependencies

```bash
pip install pillow opencv-python pytesseract editdistance
```

## 🚀 Cara Menggunakan

### 1. Jalankan Aplikasi

```bash
cd d:\ProyekTA\ocr-accuracy-test
python ocr_test_gui.py
```

### 2. Workflow Testing

#### Step 1: Input Ground Truth (Data Manual)
- Isi form di panel kiri dengan data KTP yang benar
- Atau klik **"📁 Load dari JSON"** jika sudah punya file JSON

**Contoh data**:
```
NIK: 1301051905760001
Nama Lengkap: LINDO
Tempat Lahir: PADANG
Tanggal Lahir: 19-05-1976
Alamat: JL MERDEKA NO 10
RT/RW: 001/002
Kelurahan: PAINAN
Kecamatan: IV JURAI
```

#### Step 2: Upload Gambar KTP
- Klik **"📤 Upload Gambar KTP"** di panel tengah
- Pilih gambar KTP dari komputer
- Preview akan muncul di bawah tombol

#### Step 3: Pilih Metode Preprocessing
- **Grayscale**: Simple grayscale conversion (cepat)
- **Adaptive**: Adaptive thresholding (recommended untuk lighting tidak merata)
- **Otsu**: Otsu's binarization (bagus untuk background uniform)

#### Step 4: Test OCR
- Klik **"🔍 Test OCR"** (tombol hijau)
- Dialog akan muncul dengan full OCR text
- Copy text yang sesuai dari hasil OCR
- Paste ke field yang sesuai
- Klik **"📊 Calculate Metrics"**

#### Step 5: Review Results
Panel kanan akan menampilkan:
- ✅ Field yang MATCH (similarity ≥90%)
- ⚠️ Field yang PARTIAL (70-89%)
- ❌ Field yang MISMATCH (<70%)
- Metrics detail: Similarity, CER, WER, F1 Score
- Summary dengan status keseluruhan

### 3. Export Hasil

- **📄 Export Report (CSV)**: Export ke CSV untuk Excel
- **📊 Export Report (JSON)**: Export ke JSON untuk analysis

### 4. Save/Load Ground Truth

- **💾 Save ke JSON**: Save ground truth untuk reuse
- **📁 Load dari JSON**: Load ground truth dari file sebelumnya

## 📊 Interpretasi Hasil

### Status Classification:

| Icon | Status | Similarity | Action |
|------|--------|------------|--------|
| ✅ | MATCH | ≥95% | Auto-approve |
| ⚠️ | PARTIAL_MATCH | 80-94% | Minor review |
| 🔍 | MANUAL_REVIEW | 70-79% | Careful check |
| ❌ | MISMATCH | <70% | Reject/fix |

### Metrics Explanation:

- **Similarity**: Overall match percentage (0-100%)
- **CER (Character Error Rate)**: Character error level (lower is better)
- **WER (Word Error Rate)**: Word error level (lower is better)
- **F1 Score**: Balance of precision & recall (0-1, higher is better)

## 🎨 Screenshot Layout

```
┌─────────────────────────────────────────────────────────────┐
│                 OCR ACCURACY TESTING - SIPADU               │
├──────────────┬──────────────────┬──────────────────────────┤
│ Ground Truth │  Upload & Preview│     Results & Metrics    │
│              │                  │                          │
│ NIK: [____]  │  [Upload Button] │  ✅ NIK                  │
│ Nama: [___]  │                  │     GT: 1301...          │
│ Tempat: [__] │  [Image Preview] │     OCR: 1301...         │
│ Tanggal: [_] │                  │     Similarity: 100%     │
│ Alamat: [__] │  ○ Grayscale     │                          │
│ RT/RW: [___] │  ● Adaptive      │  ⚠️ Nama                 │
│ Kelurahan:   │  ○ Otsu          │     GT: LINDO            │
│ Kecamatan:   │                  │     OCR: LLINDO          │
│              │  [Test OCR Btn]  │     Similarity: 83%      │
│ [Load][Save] │                  │                          │
│ [Clear]      │                  │  Summary: 91.5%          │
│              │                  │  Status: PARTIAL_MATCH   │
├──────────────┴──────────────────┴──────────────────────────┤
│  [Export CSV] [Export JSON]              [Reset All]       │
└─────────────────────────────────────────────────────────────┘
```

## 🔧 Troubleshooting

### Error: "No module named 'PIL'"
```bash
pip install pillow
```

### Error: "Tesseract not found"
Pastikan Tesseract sudah terinstall dan ada di PATH:
```bash
tesseract --version
```

### OCR Result kosong/buruk
- Coba metode preprocessing lain
- Check kualitas gambar (blur, lighting, angle)
- Crop gambar ke area yang lebih spesifik

### Dialog tidak muncul
- Check apakah ada error di console
- Pastikan semua dependencies terinstall

## 💡 Tips

1. **Ground Truth harus akurat** - Data manual harus exact match dengan KTP
2. **Test multiple preprocessing** - Coba 3 metode untuk hasil terbaik
3. **Save ground truth** - Save ke JSON untuk reuse
4. **Export results** - Export untuk dokumentasi dan analysis
5. **Test dengan berbagai gambar** - Test minimal 10+ gambar untuk validasi

## 🔄 Integration Workflow

Setelah testing dan dapat best practices:

1. **Document findings**:
   - Best preprocessing method: ___________
   - Average accuracy: ___________
   - Common errors: ___________

2. **Apply to main system**:
   - Update `ocr-service/app.py` dengan preprocessing terbaik
   - Set threshold di `OCRValidationService.php`

3. **Monitor production**:
   - Track metrics di real usage
   - Alert untuk low accuracy
   - Continuous improvement

## 📝 Sample Ground Truth JSON

File: `sample_ground_truth.json`
```json
{
  "nik": "1301051905760001",
  "nama": "LINDO",
  "tempat_lahir": "PADANG",
  "tanggal_lahir": "19-05-1976",
  "alamat": "JL MERDEKA NO 10",
  "rt_rw": "001/002",
  "kelurahan": "PAINAN",
  "kecamatan": "IV JURAI"
}
```

## 🎯 Quick Start Example

```bash
# 1. Jalankan aplikasi
python ocr_test_gui.py

# 2. Di aplikasi:
#    - Klik "Load dari JSON" → pilih sample_ground_truth.json
#    - Klik "Upload Gambar" → pilih KTP image
#    - Pilih "Adaptive" preprocessing
#    - Klik "Test OCR"
#    - Input hasil OCR di dialog
#    - Klik "Calculate Metrics"
#    - Review results
#    - Export ke CSV/JSON

# 3. Ulangi dengan gambar lain untuk aggregate statistics
```

---

**Happy Testing! 🚀**

Jika ada pertanyaan atau issue, check dokumentasi lengkap di:
- HOW_TO_TEST.md
- TESTING_GUIDE.md
- README.md
