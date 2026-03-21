# OCR Accuracy Testing - Quick Start Guide

## Workflow Step-by-Step

### 1. Setup & Import (Cell 1-2)
- Jalankan cell pertama: Import semua libraries
- Output: "✅ Libraries loaded successfully"
- Check Tesseract version

### 2. Load Data (Cell 3)
- Auto-load ground truth dari `sample_data/ground_truth.json`
- Jika file belum ada, akan di-create otomatis dengan sample data
- Review struktur data yang dimuat

### 3. Test Preprocessing (Cell 6-7)
⚠️ PENTING: Jika Anda punya gambar KTP real:
- Cell 6: List available images
- Cell 7: Visualisasi 4 metode preprocessing (Original, Grayscale, Adaptive, Otsu)
- Pilih metode terbaik berdasarkan visual

### 4. Extract Text (Cell 8)
- OCR extraction dengan 3 metode preprocessing
- Bandingkan hasil untuk pilih yang terbaik
- Output: Full text dari setiap metode

### 5. Manual Testing Metrics (Cell 9-11)
**CARA TESTING:**

#### a. Edit Cell 9 dengan hasil OCR manual:
```python
manual_test = {
    'nik': {
        'reference': '1301051905760001',  # Dari ground truth
        'ocr': '1301051905760001'         # ← GANTI dengan hasil OCR
    },
    'nama': {
        'reference': 'LINDO',
        'ocr': 'LINDO'  # ← GANTI dengan hasil OCR
    },
    # ... dst untuk semua field
}
```

#### b. Cara mendapatkan hasil OCR:
1. Lihat output Cell 8 (full text extraction)
2. Copy text yang sesuai untuk setiap field
3. Paste ke 'ocr' value di Cell 9
4. Atau ketik manual jika OCR salah/tidak detect

#### c. Run Cell 10:
- Akan hitung semua metrics per field
- Output: Table dengan F1, CER, WER, Similarity
- Icon ✅ = Match (≥90%), ❌ = Mismatch (<90%)

### 6. Visualisasi (Cell 11)
- Generate 4 charts:
  1. F1 Score per field (horizontal bar)
  2. CER vs WER comparison
  3. Precision vs Recall scatter plot
  4. Overall Similarity scores
- Auto-save ke `results/metrics_visualization.png`

### 7. Summary Report (Cell 12)
- Overall metrics (average dari semua field)
- Classification: MATCH / PARTIAL_MATCH / MANUAL_REVIEW / MISMATCH
- Recommendations untuk improvement
- Export ke `results/metrics_report.csv`

## Contoh Output Yang Diharapkan

### Good Result (MATCH):
```
✅ nik          | F1: 1.000 | CER: 0.000 | WER: 0.000 | Sim: 100.0%
✅ nama         | F1: 1.000 | CER: 0.000 | WER: 0.000 | Sim: 100.0%
✅ tempat_lahir | F1: 1.000 | CER: 0.000 | WER: 0.000 | Sim: 100.0%
...
Overall Similarity: 98.5% → MATCH ✅
```

### Partial Result (PARTIAL_MATCH):
```
✅ nik          | F1: 1.000 | CER: 0.000 | WER: 0.000 | Sim: 100.0%
⚠️ nama         | F1: 0.889 | CER: 0.125 | WER: 0.200 | Sim: 87.5%
✅ tempat_lahir | F1: 1.000 | CER: 0.000 | WER: 0.000 | Sim: 100.0%
...
Overall Similarity: 86.2% → PARTIAL_MATCH ⚠️
```

### Poor Result (MISMATCH):
```
❌ nik          | F1: 0.200 | CER: 0.875 | WER: 1.000 | Sim: 12.5%
❌ nama         | F1: 0.333 | CER: 0.750 | WER: 1.000 | Sim: 25.0%
...
Overall Similarity: 35.7% → MISMATCH ❌
```

## Tips Testing

### 1. Quality Image Testing:
- Test dengan gambar berkualitas baik (clear, good lighting)
- Test dengan gambar blur/low quality
- Test dengan gambar berbagai angle
- Bandingkan hasil untuk setiap kondisi

### 2. Preprocessing Comparison:
- Jika gambar gelap → Try 'adaptive' method
- Jika gambar terang merata → Try 'otsu' method
- Jika gambar sudah bagus → Try 'grayscale' method

### 3. Field-Specific Analysis:
- NIK (16 digit) → Harusnya paling akurat (numeric)
- Nama → Check capitalization issues
- Alamat → Multi-line, bisa lebih error-prone
- Tanggal → Check format DD-MM-YYYY vs DD/MM/YYYY

## Testing Checklist

- [ ] Dependencies installed (`pip install -r requirements.txt`)
- [ ] Tesseract OCR working (`tesseract --version`)
- [ ] Sample images prepared in `sample_data/images/`
- [ ] Ground truth JSON created with correct data
- [ ] Jupyter notebook opened (`jupyter notebook`)
- [ ] All cells run successfully without errors
- [ ] Metrics calculated and visualizations generated
- [ ] Results saved to `results/` folder
- [ ] CSV report reviewed
- [ ] Best preprocessing method identified
- [ ] Threshold values noted for integration

## Next: Integration to Main System

Setelah testing selesai dan puas dengan hasil:

1. **Catat best practices:**
   - Preprocessing method terbaik: ____________
   - Optimal Tesseract config: ____________
   - Threshold MATCH (similarity): ≥ _____% 
   - Threshold PARTIAL_MATCH: ≥ _____% to < _____% 
   - Threshold MISMATCH: < _____%

2. **Update main OCR service:**
   - Apply preprocessing ke `ocr-service/app.py`
   - Update validation thresholds di `OCRValidationService.php`

3. **Create monitoring:**
   - Track metrics di production
   - Alert untuk CER/WER tinggi
   - Quality control dashboard

## Batch Compare (CLI)

Gunakan script CLI jika ingin compare banyak file sekaligus tanpa edit notebook manual.

```powershell
cd ocr-accuracy-test
python batch_compare.py `
    --image-dir sample_data/images `
    --ground-truth sample_data/ground_truth.json `
    --endpoint http://127.0.0.1:5001/ocr/process `
    --secret ocr_rahasia_sipadu_2026 `
    --output-csv results/batch_metrics.csv
```

Output:
- Ringkasan average similarity di terminal
- File CSV detail per image x field di `results/batch_metrics.csv`

### Generate Ground Truth Template (Optional)

Kalau Anda punya banyak file gambar baru, buat template JSON otomatis dulu:

```powershell
cd ocr-accuracy-test
python generate_ground_truth_template.py `
    --image-dir sample_data/images `
    --output sample_data/ground_truth_template.json
```

Lalu isi nilai referensi pada `sample_data/ground_truth_template.json`,
rename/copy jadi `sample_data/ground_truth.json`, dan jalankan `batch_compare.py`.
