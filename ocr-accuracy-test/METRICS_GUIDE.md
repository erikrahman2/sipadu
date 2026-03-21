# 📊 METRICS DISPLAY - COMPLETE GUIDE

## ✅ Update: Semua Metrics Sekarang Ditampilkan!

Aplikasi sudah di-update untuk menampilkan **SEMUA METRICS** di 2 tempat:

---

## 🎯 TEMPAT 1: Preview di Dialog (Quick View)

**Kapan:** Langsung setelah klik "Test OCR"

**Lokasi:** Section "📊 Quick Preview Metrics" di bawah extracted fields

**Isi:**
```
Quick Similarity Preview (auto-calculated):
--------------------------------------------------
✅ NIK: 85.2% similarity (CER: 0.148, WER: 1.000, F1: 0.857)
⚠️ NAMA: 72.5% similarity (CER: 0.275, WER: 1.000, F1: 0.654)
✅ TEMPAT_LAHIR: 95.0% similarity (CER: 0.050, WER: 0.000, F1: 0.950)
✅ TANGGAL_LAHIR: 100.0% similarity (CER: 0.000, WER: 0.000, F1: 1.000)
--------------------------------------------------
Average Similarity: 88.2%
```

**Metrics yang ditampilkan:**
- ✅ **Similarity** - Persentase kemiripan (0-100%)
- ✅ **CER** - Character Error Rate (0+, lower is better)
- ✅ **WER** - Word Error Rate (0+, lower is better)
- ✅ **F1** - F1 Score (0-1, higher is better)

**Kegunaan:**
- Quick check sebelum calculate
- Identifikasi field mana yang perlu di-edit
- Estimate overall accuracy

---

## 🎯 TEMPAT 2: Full Report di Panel Results (Complete Details)

**Kapan:** Setelah klik "Calculate Full Metrics" atau "Auto Accept"

**Lokasi:** Panel KANAN (Results) di main window

**Isi:**
```
============================================================
       🔍 OCR ACCURACY TEST RESULTS
============================================================

Test Date: 2026-03-12 14:30:45
Image: ktp_test.jpg
Preprocessing: adaptive

------------------------------------------------------------
FIELD-BY-FIELD COMPARISON
------------------------------------------------------------

✅ NIK
   Ground Truth: 4756039847464598
   OCR Result:   4756039847464598
   Similarity:   100.0%
   CER:          0.0000
   WER:          0.0000
   Precision:    1.0000
   Recall:       1.0000
   F1 Score:     1.0000

⚠️ NAMA
   Ground Truth: DONI
   OCR Result:   PROVE RERTTRAN RAT
   Similarity:   22.2%
   CER:          3.5000
   WER:          1.0000
   Precision:    0.3750
   Recall:       0.7500
   F1 Score:     0.5000

✅ TEMPAT_LAHIR
   Ground Truth: BATAM
   OCR Result:   SRO BATAM
   Similarity:   66.7%
   CER:          0.4000
   WER:          0.5000
   Precision:    0.8333
   Recall:       1.0000
   F1 Score:     0.9091

============================================================
SUMMARY
============================================================
Total Fields:       8
Matched (≥90%):     5
Average Similarity: 87.5%

Status: ⚠️ PARTIAL_MATCH (Good accuracy, minor review needed)
============================================================
```

**Metrics yang ditampilkan PER FIELD:**
- ✅ **Similarity** - Overall match percentage (0-100%)
- ✅ **CER** - Character Error Rate (0+)
- ✅ **WER** - Word Error Rate (0+)
- ✅ **Precision** - TP / (TP + FP) - Akurasi hasil OCR (0-1)
- ✅ **Recall** - TP / (TP + FN) - Coverage ground truth (0-1)
- ✅ **F1 Score** - Harmonic mean of precision & recall (0-1)

**Metrics Summary:**
- Total fields tested
- How many matched (≥90% similarity)
- Average similarity across all fields
- Overall status classification

---

## 📖 Penjelasan Metrics

### 1. **Similarity (Persentase Kemiripan)**
- **Formula**: `(1 - EditDistance / MaxLength) × 100%`
- **Range**: 0% - 100%
- **Interpretasi**:
  - **100%**: Perfect match
  - **90-99%**: Excellent (minor typo)
  - **70-89%**: Good (beberapa error)
  - **<70%**: Poor (banyak error)
- **Example**:
  - GT: `BATAM` vs OCR: `BATAM` → 100%
  - GT: `BATAM` vs OCR: `BATAM` → 100%
  - GT: `BATAM` vs OCR: `BATA` → 80%
  - GT: `BATAM` vs OCR: `JAKARTA` → 28.6%

### 2. **CER (Character Error Rate)**
- **Formula**: `EditDistance / Length(GroundTruth)`
- **Range**: 0+ (lower is better)
- **Interpretasi**:
  - **0.0**: No errors
  - **0.1**: 10% character errors
  - **0.5**: 50% errors
  - **1.0+**: More errors than original length
- **Example**:
  - GT: `BATAM` (5 chars) vs OCR: `BATAM` → CER = 0/5 = 0.0
  - GT: `BATAM` (5 chars) vs OCR: `BATA` → CER = 1/5 = 0.2
  - GT: `BATAM` (5 chars) vs OCR: `JAKARTA` → CER = 5/5 = 1.0

### 3. **WER (Word Error Rate)**
- **Formula**: `WordEditDistance / WordCount(GroundTruth)`
- **Range**: 0+ (lower is better)
- **Interpretasi**:
  - **0.0**: All words correct
  - **0.5**: Half of words wrong
  - **1.0**: All words wrong
- **Example**:
  - GT: `JALAN MERDEKA` vs OCR: `JALAN MERDEKA` → WER = 0/2 = 0.0
  - GT: `JALAN MERDEKA` vs OCR: `JALAN RAYA` → WER = 1/2 = 0.5
  - GT: `JALAN MERDEKA` vs OCR: `GANG MAWAR` → WER = 2/2 = 1.0

### 4. **Precision (Ketepatan)**
- **Formula**: `TP / (TP + FP)`
- **Range**: 0.0 - 1.0 (higher is better)
- **Interpretasi**:
  - **1.0**: Semua karakter OCR benar
  - **0.8**: 80% karakter OCR valid
  - **0.5**: 50% karakter OCR valid
- **Meaning**: Dari semua karakter yang di-detect OCR, berapa persen yang benar?
- **Example**:
  - GT: `ABC` vs OCR: `ABC` → Precision = 3/3 = 1.0 (all correct)
  - GT: `ABC` vs OCR: `ABCD` → Precision = 3/4 = 0.75 (extra D)
  - GT: `ABC` vs OCR: `ABX` → Precision = 2/3 = 0.67 (wrong X)

### 5. **Recall (Kelengkapan)**
- **Formula**: `TP / (TP + FN)`
- **Range**: 0.0 - 1.0 (higher is better)
- **Interpretasi**:
  - **1.0**: Semua karakter ground truth ter-detect
  - **0.8**: 80% ground truth ter-detect
  - **0.5**: 50% ground truth ter-detect
- **Meaning**: Dari semua karakter di ground truth, berapa persen yang berhasil di-detect?
- **Example**:
  - GT: `ABC` vs OCR: `ABC` → Recall = 3/3 = 1.0 (all found)
  - GT: `ABC` vs OCR: `AB` → Recall = 2/3 = 0.67 (missing C)
  - GT: `ABC` vs OCR: `A` → Recall = 1/3 = 0.33 (missing B,C)

### 6. **F1 Score (Balanced Score)**
- **Formula**: `2 × (Precision × Recall) / (Precision + Recall)`
- **Range**: 0.0 - 1.0 (higher is better)
- **Interpretasi**:
  - **1.0**: Perfect balance (precision = recall = 1.0)
  - **0.8-0.9**: Excellent balance
  - **0.6-0.7**: Good balance
  - **<0.5**: Poor performance
- **Meaning**: Harmonic mean dari precision dan recall (balanced metric)
- **Example**:
  - Precision=1.0, Recall=1.0 → F1 = 1.0
  - Precision=0.8, Recall=0.8 → F1 = 0.8
  - Precision=1.0, Recall=0.5 → F1 = 0.67 (unbalanced!)
  - Precision=0.5, Recall=1.0 → F1 = 0.67 (unbalanced!)

---

## 🎯 Status Classification

Berdasarkan **Average Similarity**:

| Status | Similarity | Icon | Meaning |
|--------|-----------|------|---------|
| **MATCH** | ≥95% | ✅ | Excellent accuracy - auto-approve |
| **PARTIAL_MATCH** | 80-94% | ⚠️ | Good accuracy - minor review needed |
| **MANUAL_REVIEW** | 70-79% | 🔍 | Moderate accuracy - careful check |
| **MISMATCH** | <70% | ❌ | Poor accuracy - reject/manual fix |

---

## 🚀 Workflow untuk Lihat Semua Metrics

### Step 1: Upload & Test
```
1. Load ground truth (atau isi manual)
2. Upload gambar KTP
3. Pilih preprocessing method (Adaptive recommended)
4. Klik "🔍 Test OCR"
```

### Step 2: Check Preview (di Dialog)
```
5. Scroll down di dialog ke section "📊 Quick Preview Metrics"
6. Review similarity, CER, WER, F1 per field
7. Check average similarity
8. Identifikasi field mana yang perlu di-edit
```

### Step 3: Edit jika Perlu
```
9. Scroll up ke "Extracted Fields"
10. Edit OCR results yang salah
11. Ensure semua field sudah benar sebelum calculate
```

### Step 4: Calculate Full Metrics
```
12. Scroll down ke buttons
13. Klik "📊 Calculate Full Metrics (with Precision/Recall)"
    ATAU
    Klik "⚡ Auto Accept & Calculate" (jika yakin hasil sudah OK)
```

### Step 5: Review Full Report (di Panel Results)
```
14. Check panel KANAN (Results) di main window
15. Review field-by-field comparison dengan SEMUA metrics:
    - Similarity, CER, WER
    - Precision, Recall, F1
16. Check SUMMARY section:
    - Total fields
    - Matched count
    - Average similarity
    - Overall status
```

### Step 6: Export (Optional)
```
17. Klik "📄 Export Report (CSV)" untuk Excel
    ATAU
18. Klik "📊 Export Report (JSON)" untuk analysis
19. File tersimpan di folder results/
```

---

## 📊 Example Output

### Preview Metrics (Dialog):
```
Quick Similarity Preview (auto-calculated):
--------------------------------------------------
✅ NIK: 100.0% (CER: 0.000, WER: 0.000, F1: 1.000)
✅ NAMA: 95.5% (CER: 0.045, WER: 0.000, F1: 0.955)
⚠️ TEMPAT_LAHIR: 85.7% (CER: 0.143, WER: 0.500, F1: 0.857)
✅ TANGGAL_LAHIR: 100.0% (CER: 0.000, WER: 0.000, F1: 1.000)
⚠️ ALAMAT: 82.4% (CER: 0.176, WER: 0.200, F1: 0.824)
❌ RT_RW: 55.6% (CER: 0.444, WER: 1.000, F1: 0.556)
✅ KELURAHAN: 92.9% (CER: 0.071, WER: 0.000, F1: 0.929)
✅ KECAMATAN: 90.0% (CER: 0.100, WER: 0.000, F1: 0.900)
--------------------------------------------------
Average Similarity: 87.8%
```

**Interpretasi:**
- 5 fields excellent (✅ ≥90%)
- 2 fields good but need review (⚠️ 80-89%)
- 1 field poor (❌ <70%)
- Overall: PARTIAL_MATCH status (good accuracy)

### Full Report (Panel Results):
```
============================================================
       🔍 OCR ACCURACY TEST RESULTS
============================================================

FIELD-BY-FIELD COMPARISON (excerpt):

✅ NIK
   Similarity:   100.0%
   CER:          0.0000
   WER:          0.0000
   Precision:    1.0000    ← Perfect accuracy
   Recall:       1.0000    ← All chars detected
   F1 Score:     1.0000    ← Balanced

⚠️ TEMPAT_LAHIR
   Similarity:   85.7%
   CER:          0.1429
   WER:          0.5000
   Precision:    0.8889    ← Good accuracy
   Recall:       1.0000    ← All chars detected
   F1 Score:     0.9412    ← Good balance

SUMMARY:
Total Fields:       8
Matched (≥90%):     5
Average Similarity: 87.8%
Status: ⚠️ PARTIAL_MATCH (Good accuracy, minor review)
```

---

## 💡 Tips

### Untuk Preview Metrics:
- ✅ Use untuk quick check tanpa harus calculate full
- ✅ Identifikasi problematic fields (low similarity)
- ✅ Edit fields dengan similarity <90%
- ✅ Check average similarity untuk estimate overall quality

### Untuk Full Metrics:
- ✅ Review precision & recall untuk deep analysis
- ✅ Export report untuk documentation
- ✅ Compare CER/WER across different preprocessing methods
- ✅ Track metrics trends over time

### Optimization:
- 🎯 Focus on fields dengan Precision rendah (banyak false positives)
- 🎯 Focus on fields dengan Recall rendah (missing chars)
- 🎯 F1 Score balance kedua hal di atas

---

## ❓ FAQ

**Q: Kenapa ada 2 tempat untuk metrics?**
A: Preview di dialog untuk quick check, full report di panel untuk complete analysis.

**Q: Apakah harus scroll di dialog?**
A: Ya, scroll untuk lihat preview metrics dan buttons di bawah.

**Q: Metrics mana yang paling penting?**
A: Similarity untuk overall assessment, Precision/Recall untuk detail analysis.

**Q: Apa beda Precision dan Recall?**
A: Precision = akurasi OCR, Recall = coverage ground truth. F1 = balance keduanya.

**Q: Bagaimana improve metrics?**
A: 
1. Try different preprocessing methods
2. Improve image quality
3. Edit extracted fields before calculate
4. Use higher resolution images

---

🎉 **Sekarang SEMUA METRICS sudah ditampilkan!** Test ulang dengan gambar KTP! 🚀
