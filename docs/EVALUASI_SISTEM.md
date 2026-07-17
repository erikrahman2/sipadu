# Laporan Evaluasi Sistem SiPadu

## Informasi Umum

| Item | Detail |
|------|--------|
| **Proyek** | SiPadu - Sistem Informasi Pengadilan Agama |
| **Tanggal Evaluasi** | Juli 2026 |
| **Evaluator** | Automated + Manual Review |

---

# BAGIAN 1: EVALUASI OCR

## 1. Statistik Dasar OCR

### 1.1 Dataset

| Parameter | Nilai |
|-----------|-------|
| Total Dokumen | 126 |
| Ground Truth Fields | 8 (NIK, Nama, Tanggal Lahir, Tempat Lahir, Alamat, RT/RW, Kelurahan, Kecamatan) |
| Total Predictions | 1,008 (126 × 8) |

### 1.2 Distribusi Status Dokumen

| Status | Jumlah | Persentase | 95% CI |
|--------|--------|------------|---------|
| **SUCCESS** | 67 | 53.2% | 44.1% - 62.1% |
| **PARTIAL** | 49 | 38.9% | 30.2% - 48.2% |
| **MISMATCH** | 10 | 7.9% | 3.8% - 14.2% |
| **FAILED** | 0 | 0.0% | - |

**Keterangan Status:**
- **SUCCESS**: Dokumen selesai diproses tanpa intervensi manual
- **PARTIAL**: Dokumen memerlukan review manual untuk validasi
- **MISMATCH**: Dokumen dengan field yang tidak cocok ground truth
- **FAILED**: Dokumen yang gagal diproses

**Catatan**: OCR membaca semua 126 dokumen. Processing Success menunjukkan proporsi yang selesai tanpa intervensi manual.

### 1.3 Performa per Format

| Format | Total | Success | Partial | Mismatch | Confidence |
|--------|-------|---------|---------|---------|-----------|
| PNG | 42 | 66.7% | 33.3% | 0.0% | 82.1% |
| JPEG | 42 | 64.3% | 35.7% | 0.0% | 79.6% |
| PDF | 42 | 28.6% | 71.4% | 0.0% | 70.2% |

Rata-rata Confidence Score: 77.31% (95% CI: 75.12% - 79.50%)

### 1.4 Waktu Pemrosesan

| Parameter | Nilai |
|-----------|-------|
| Total Dokumen | 126 |
| Total Waktu | 3,331,175 ms (~55.5 menit) |
| Rata-rata per Dokumen | 26.44 detik |

---

## 2. Precision, Recall, dan F1-Score

**Catatan**: Angka-angka di bawah ini dihitung secara manual dengan membandingkan hasil OCR terhadap ground truth.

### 2.1 Definisi

| Komponen | Definisi |
|----------|----------|
| **TP** (True Positive) | Field berhasil diekstrak dan MATCH ground truth |
| **FP** (False Positive) | Field diekstrak tapi tidak cocok ground truth |
| **FN** (False Negative) | Field gagal diekstrak (null/empty) |
| **Precision** | TP / (TP + FP) - proporsi prediksi positif yang benar |
| **Recall** | TP / (TP + FN) - proporsi ground truth yang berhasil ditangkap |
| **F1-Score** | 2 × (P × R) / (P + R) - harmonic mean |

### 2.2 Hasil per Entity (n=126 dokumen)

| Entity | TP | FP | FN | Precision | Recall | F1-Score |
|--------|----|----|----|-----------|--------|----------|
| **NIK** | 111 | 7 | 8 | 94.1% | 93.3% | **93.7%** |
| **Nama** | 114 | 10 | 2 | 91.9% | 98.3% | **95.0%** |
| **Tanggal Lahir** | 115 | 10 | 1 | 92.0% | 99.1% | **95.4%** |
| **Tempat Lahir** | 104 | 10 | 12 | 91.2% | 89.7% | **90.4%** |
| **Alamat** | 113 | 10 | 3 | 91.9% | 97.4% | **94.6%** |
| **RT/RW** | 104 | 8 | 14 | 92.9% | 88.1% | **90.4%** |
| **Kelurahan** | 114 | 8 | 4 | 93.4% | 96.6% | **95.0%** |
| **Kecamatan** | 113 | 7 | 6 | 94.2% | 95.0% | **94.6%** |

### 2.3 Macro-Averaged Metrics

| Metric | Nilai |
|--------|-------|
| **Macro Precision** | 92.7% |
| **Macro Recall** | 94.7% |
| **Macro F1-Score** | 93.6% |

### 2.4 Micro-Averaged Metrics (Agregat)

| Metric | Nilai |
|--------|-------|
| **Micro TP Total** | 888 |
| **Micro FP Total** | 70 |
| **Micro FN Total** | 50 |
| **Micro Precision** | 92.7% |
| **Micro Recall** | 94.7% |
| **Micro F1-Score** | 93.7% |

---

## 3. Confusion Matrix

### 3.1 Confusion Matrix Agregat (1,008 total predictions)

| | Predicted Match | Predicted No Match | Total |
|---|-----------------|-------------------|-------|
| **Actual Match** | **888** (TP) | **50** (FN) | 938 |
| **Actual No Match** | **70** (FP) | N/A | 70 |
| **Total** | 958 | 50 | 1,008 |

### 3.2 Confusion Matrix Ringkasan

**Catatan**: Nilai di bawah dihitung dari TP/FP/FN per entity pada Section 2.2.

| Metric | Total | % dari Total |
|--------|-------|--------------|
| **TP (True Positive)** | 888 | 88.1% |
| **FP (False Positive)** | 70 | 6.9% |
| **FN (False Negative)** | 50 | 5.0% |
| **Total** | 1,008 | 100% |

---

## 4. CER dan WER

**CER** (Character Error Rate): Proporsi karakter yang salah terhadap ground truth
**WER** (Word Error Rate): Proporsi kata yang salah terhadap ground truth

**Catatan**: CER dan WER dihitung secara manual dengan membandingkan hasil OCR terhadap ground truth menggunakan Levenshtein distance.

| Metric | Nilai |
|--------|-------|
| **Overall CER** | 21.5% |
| **Overall WER** | 25.2% |

---

## 5. Interval Kepercayaan (95% CI)

### 5.1 Metodologi

- **Proporsi**: Clopper-Pearson Exact Method untuk rate (Success Rate, Mismatch Rate)
- **Confidence Score**: t-distribution (df=125)

### 5.2 CI untuk Rate/Proporsi

| Parameter | Nilai |
|-----------|-------|
| Success Rate | 53.2% (95% CI: 44.1% - 62.1%) |
| Mismatch Rate | 7.9% (95% CI: 3.8% - 14.2%) |
| Confidence Score | 77.31% (95% CI: 75.12% - 79.50%) |

---

# BAGIAN 2: EVALUASI ReBAC

## 6. Statistik Dasar ReBAC

### 6.1 Arsitektur ReBAC

Sistem ReBAC menggunakan **Neo4j Graph Database** dengan relasi:

| Relasi | Arah | Fungsi |
|--------|------|--------|
| `WORKS_AT` | User → Institution | User bekerja di institusi |
| `SUPER_ADMIN` | Admin → Institution | Admin akses semua institusi |
| `SUBMITTED` | User → Case | User submit case |
| `HAS` | User → Case | User terkait dengan case |
| `HAS_DOCUMENT` | Case → Document | Document milik case |

### 6.2 Role dan Akses Scope

| Role | Institusi | Akses Scope |
|------|-----------|-------------|
| **PA Assistant** | PA | Own cases + PA_REVIEW+ |
| **PA Management** | PA | PA_REVIEW+ |
| **PA Staff** | PA | COMPLETED+ |
| **Disdukcapil Staff** | Disdukcapil | DISDUK_VALIDATION+ |
| **Administrator** | PA + Disdukcapil | Full access |

---

## 7. Hasil Pengujian ReBAC

### 7.1 Test Results (15 Test Case)

| Kategori | Total | Berhasil | Gagal | Success Rate |
|----------|-------|---------|-------|-------------|
| **Positive Access** | 5 | 5 | 0 | 100% |
| **Negative Access** | 4 | 4 | 0 | 100% |
| **Cross Institution Isolation** | 4 | 4 | 0 | 100% |
| **Workflow Validation** | 2 | 2 | 0 | 100% |
| **Total** | **15** | **15** | **0** | **100%** |

**Keterangan Kategori:**
- **Positive Access**: User dengan role yang benar dapat mengakses resource yang seharusnya boleh diakses
- **Negative Access**: User tidak dapat mengakses resource yang seharusnya tidak boleh diakses
- **Cross Institution Isolation**: User suatu institusi tidak dapat mengakses data institusi lain
- **Workflow Validation**: Akses dibatasi berdasarkan status case (PA_REVIEW, DISDUK_VALIDATION, COMPLETED)

### 7.2 Detail Test Case

| Test Case | Kategori | Expected | Actual | Result |
|-----------|----------|----------|--------|--------|
| PA Staff akses PA case (COMPLETED) | Positive Access | Allowed | Allowed | **PASS** |
| Disdukcapil akses Disdukcapil case | Positive Access | Allowed | Allowed | **PASS** |
| Admin akses kedua institusi | Positive Access | Allowed | Allowed | **PASS** |
| PA Management akses PA_REVIEW case | Positive Access | Allowed | Allowed | **PASS** |
| PA Assistant akses own case | Positive Access | Allowed | Allowed | **PASS** |
| PA Staff akses Disdukcapil case | Negative Access | Denied | Denied | **PASS** |
| Disdukcapil akses PA case | Negative Access | Denied | Denied | **PASS** |
| User tanpa role akses case | Negative Access | Denied | Denied | **PASS** |
| Guest akses resource | Negative Access | Denied | Denied | **PASS** |
| PA Staff melihat Disdukcapil data | Cross Institution | Blocked | Blocked | **PASS** |
| Disdukcapil melihat PA data | Cross Institution | Blocked | Blocked | **PASS** |
| PA case terlihat di Disdukcapil | Cross Institution | Blocked | Blocked | **PASS** |
| Admin cross-institution access | Cross Institution | Allowed | Allowed | **PASS** |
| Akses berdasarkan workflow status | Workflow Validation | Correct | Correct | **PASS** |
| Document access via case ownership | Workflow Validation | Correct | Correct | **PASS** |

### 7.3 Interpretasi

- **Access Control Accuracy**: 100% (15/15)
- **Isolation Effectiveness**: 100% (cross-institution access correctly blocked)
- **False Positive Rate**: 0% (tidak ada akses salah diberikan)
- **False Negative Rate**: 0% (tidak ada akses yang seharusnya diizinkan ditolak)

---

# BAGIAN 3: KESIMPULAN

## 8. Ringkasan Hasil

### OCR

| Metric | Target | Actual | Status |
|--------|--------|--------|--------|
| Macro Precision | ≥70% | 92.7% | **✓ Memenuhi** |
| Macro Recall | ≥70% | 94.7% | **✓ Memenuhi** |
| Macro F1-Score | ≥70% | 93.6% | **✓ Memenuhi** |
| Overall CER | ≤20% | 21.5% | ✗ Tidak Memenuhi |
| Overall WER | ≤25% | 25.2% | ✗ Tidak Memenuhi |
| Processing Success | ≥90% | 53.2% | ✗ Tidak Memenuhi |

### ReBAC

| Metric | Target | Actual | Status |
|--------|--------|--------|--------|
| Access Control Accuracy | ≥95% | 100% | **✓ Melebihi** |
| Isolation Effectiveness | ≥95% | 100% | **✓ Melebihi** |
| False Positive Rate | ≤1% | 0% | **✓ Memenuhi** |

---

## 9. Catatan Metodologis

1. **Precision/Recall/F1**: Dihitung secara manual dengan membandingkan hasil OCR terhadap ground truth per field
2. **CER/WER**: Dihitung secara manual menggunakan Levenshtein distance antara ground truth dan prediksi OCR
3. **ReBAC**: Black-box testing terhadap Neo4j graph untuk memverifikasi relasi dan akses
4. **Interval Kepercayaan**: Clopper-Pearson Exact Method untuk proporsi

---

_Document Version: 1.0_
_Last Updated: Juli 2026_
