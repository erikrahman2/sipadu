# Panduan Pengajuan Publik SiPadu

> Form pengajuan untuk masyarakat umum (tanpa perlu login/registrasi)

---

## ✅ Status Implementasi

**Semua fitur pengajuan publik sudah lengkap dan terintegrasi:**

### 🎯 Fitur yang Tersedia

1. **Form Pengajuan Publik** - `/pengajuan`
   - ✅ Validasi NIK 16 digit
   - ✅ Rate limiting (max 3 pengajuan per NIK dalam 15 hari)
   - ✅ Upload dokumen (KTP wajib, lainnya opsional)
   - ✅ Preview file sebelum upload
   - ✅ Validasi ukuran file (max 5MB per file)
   - ✅ Progress indicator saat submit

2. **Notifikasi WhatsApp**
   - ✅ Token tracking otomatis dikirim ke WA pemohon
   - ✅ Tidak perlu password/akun
   - ✅ Retry mechanism via queue system

3. **Inbox Staff** - `/dashboard/public-inbox`
   - ✅ PA Assistant, PA Management, PA Staff, Disdukcapil Staff bisa akses
   - ✅ Filter berdasarkan status (PENDING, REVIEWING, APPROVED, REJECTED)
   - ✅ Search by NIK/nama/token
   - ✅ Badge counter untuk pending submissions

4. **Review & Approval**
   - ✅ Start review (PENDING → REVIEWING)
   - ✅ Approve submission (buat kasus resmi)
   - ✅ Reject submission dengan alasan
   - ✅ Resend WhatsApp notification

---

## 🚀 Cara Menggunakan

### A. Pengajuan dari Masyarakat

**URL:** http://127.0.0.1:8000/pengajuan

**Langkah-langkah:**

1. **Isi Data Pemohon:**
   - NIK 16 digit (akan dicek kuota otomatis)
   - Nama lengkap sesuai KTP
   - Nomor WhatsApp aktif (format: 81234567890 tanpa +62)

2. **Isi Data Perceraian (Opsional):**
   - Nama & NIK mantan pasangan
   - Tanggal putusan cerai
   - Nomor putusan PA
   - Catatan tambahan

3. **Upload Dokumen:**
   - **KTP** (WAJIB) - JPG, PNG, atau PDF max 5MB
   - KK (opsional)
   - Akta Cerai (opsional)
   - Putusan PA (opsional)
   - Surat Nikah (opsional)
   - Foto Diri (opsional)
   - Lainnya (opsional)

4. **Setujui Pernyataan** dan klik **"Kirim Pengajuan"**

5. **Terima Token:**
   - Token tracking ditampilkan di halaman sukses
   - Token dikirim ke WhatsApp (jika berhasil)
   - Simpan token untuk tracking

---

### B. Tracking Pengajuan

**URL:** http://127.0.0.1:8000/tracking

**Masukkan token tracking** untuk melihat status real-time:
- PENDING - Menunggu review
- REVIEWING - Sedang ditinjau oleh petugas
- APPROVED - Disetujui, kasus resmi dibuat
- REJECTED - Ditolak dengan alasan

---

### C. Review oleh Staff (Dashboard)

**Login sebagai:**
- PA Assistant: `asisten@pa-painan.go.id` / `Pass@12345`
- PA Management: `ketua@pa-painan.go.id` / `Pass@12345`
- PA Staff: `staf@pa-painan.go.id` / `Pass@12345`
- Disdukcapil: `petugas@disdukcapil-pessel.go.id` / `Pass@12345`

**Navigasi:** Dashboard → **Pengajuan Publik** (sidebar)

**Fitur dalam Inbox:**

1. **Lihat Daftar Pengajuan:**
   - Filter by status: ALL, PENDING, REVIEWING, APPROVED, REJECTED
   - Search by NIK, nama, atau tracking token
   - Badge merah menunjukkan jumlah PENDING

2. **Review Detail Pengajuan:**
   - Klik row untuk melihat detail
   - Lihat semua dokumen yang diupload
   - Download dokumen jika perlu

3. **Aksi yang Tersedia:**
   
   **a. Start Review** (PENDING → REVIEWING)
   ```
   - Klik tombol "Mulai Reviewing"
   - Status berubah jadi REVIEWING
   - Processor name tercatat
   ```

   **b. Approve Submission** (buat kasus resmi)
   ```
   - Pilih institusi (PA atau Disdukcapil)
   - Klik "Approve & Buat Kasus"
   - Sistem otomatis:
     * Buat CaseModel resmi
     * Copy dokumen ke case
     * Update status → APPROVED
     * Kirim notifikasi WA ke pemohon
   ```

   **c. Reject Submission**
   ```
   - Masukkan alasan penolakan
   - Klik "Reject Pengajuan"
   - Status → REJECTED
   - Pemohon dapat lacak dengan token
   ```

   **d. Resend WhatsApp**
   ```
   - Jika notifikasi WA gagal terkirim
   - Klik "Kirim Ulang WhatsApp"
   - Token dikirim kembali
   ```

---

## 🔧 Konfigurasi

**File:** `config/public_submission.php`

```php
return [
    // Max upload size per file
    'max_file_size_mb' => 5,

    // Max files per document type
    'max_files_per_type' => 3,

    // Max submissions per NIK
    'max_submissions' => 3,

    // Periode pembatasan (hari)
    'limit_days' => 15,
];
```

**Sesuaikan di `.env`:**
```env
# WhatsApp Gateway (untuk notifikasi)
WA_DRIVER=log  # Ubah ke 'api' jika ada gateway asli
WA_SENDER_NAME=SiPadu – PA & Disdukcapil

# Queue worker (untuk kirim WA async)
QUEUE_CONNECTION=database
```

---

## 📊 Database

**Tabel utama:**

1. **`public_submissions`** - Data pengajuan
   - nik, petitioner_name, phone_wa
   - respondent_name, respondent_nik
   - divorce_date, verdict_number
   - tracking_token (unique)
   - status (PENDING, REVIEWING, APPROVED, REJECTED)
   - processor_id, processor_at (siapa & kapan review)
   - wa_status (sent/failed/pending)
   - case_id (jika approved)

2. **`public_submission_documents`** - Dokumen upload
   - public_submission_id
   - document_type (KTP, KK, AKTA_CERAI, dll)
   - original_filename
   - stored_path
   - file_size, mime_type

**Storage:** `storage/app/public_submissions/{submission_id}/`

---

## 🧪 Testing Manual

### Test 1: Pengajuan Baru

```bash
# 1. Buka browser
http://127.0.0.1:8000/pengajuan

# 2. Isi form dengan data test:
NIK: 3201234567890001
Nama: Test User Pengajuan
WhatsApp: 81234567890

# 3. Upload file KTP (minimal)
# 4. Centang agreement
# 5. Klik "Kirim Pengajuan"

# Expected:
✅ Redirect ke /pengajuan/sukses/{token}
✅ Token ditampilkan
✅ Data tersimpan di database
✅ Job queue untuk WA dibuat
```

### Test 2: Rate Limiting

```bash
# Coba submit 4x dengan NIK yang sama dalam 15 hari
# Expected pada submit ke-4:
❌ Error: "NIK ini telah mencapai batas maksimal 3 pengajuan"
✅ Tanggal next allowed date ditampilkan
```

### Test 3: Staff Review

```bash
# 1. Login sebagai PA Assistant
http://127.0.0.1:8000/auth/login

# 2. Navigasi ke Pengajuan Publik (sidebar)
# 3. Cari submission yang baru dibuat
# 4. Klik row untuk detail
# 5. Klik "Mulai Reviewing"
# Expected: Status → REVIEWING

# 6. Pilih institusi: PA Painan
# 7. Klik "Approve & Buat Kasus"
# Expected:
✅ Kasus baru dibuat dengan tracking token
✅ Status → APPROVED
✅ Link ke case detail muncul
```

### Test 4: Tracking Publik

```bash
# 1. Buka (tanpa login)
http://127.0.0.1:8000/tracking

# 2. Masukkan token dari Test 1
# Expected:
✅ Status ditampilkan
✅ Timeline progress muncul
✅ Data pengajuan visible
```

---

## 🐛 Troubleshooting

### Error: "documents.KTP required"

**Penyebab:** File KTP tidak diupload atau format salah

**Solusi:**
- Upload file KTP (wajib)
- Format: JPG, PNG, atau PDF
- Ukuran max 5MB

### Error: "NIK telah mencapai batas"

**Penyebab:** NIK sudah submit 3x dalam 15 hari terakhir

**Solusi:**
- Gunakan NIK berbeda untuk testing
- Atau tunggu sesuai tanggal yang ditampilkan
- Atau hapus record lama di database (dev only)

### WhatsApp tidak terkirim

**Penyebab:** `WA_DRIVER=log` (mode development)

**Solusi:**
- Cek log: `storage/logs/laravel.log`
- Atau setup WA Gateway API dan ubah `.env`:
  ```env
  WA_DRIVER=api
  WA_API_URL=https://your-wa-gateway.com/api
  WA_API_KEY=your-secret-key
  ```

### File upload gagal

**Penyebab:** Permission folder storage

**Solusi:**
```powershell
# Fix permissions
icacls D:\ProyekTA\storage\app /grant Everyone:(OI)(CI)F /T

# Cek folder exists
Test-Path D:\ProyekTA\storage\app\public_submissions
```

---

## 📝 Catatan

1. **Tidak Perlu Akun:**
   - Masyarakat tidak perlu registrasi
   - Tracking hanya dengan token
   - Password-less system

2. **Rate Limiting:**
   - Per NIK: max 3x dalam 15 hari
   - Mencegah spam/abuse
   - Configurable via config file

3. **Queue Worker:**
   - WhatsApp dikirim via job queue (async)
   - Jalankan worker: `php artisan queue:work`
   - Atau gunakan Laravel Scheduler

4. **Security:**
   - CSRF protection ✅
   - File validation ✅
   - Rate limiting ✅
   - Input sanitization ✅

---

**Dibuat:** 9 Maret 2026  
**Status:** ✅ Production Ready
