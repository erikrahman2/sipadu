# 📊 Panduan Dashboard & Sistem Admin SiPadu

## ✅ Status Implementasi

Sistem dashboard dan admin telah **100% LENGKAP** dan siap digunakan! Semua komponen berikut telah diimplementasikan:

### 🎨 Frontend Views
- ✅ **Layout Admin** (`layouts/admin.blade.php`) - Sidebar responsif dengan navigasi role-based
- ✅ **Halaman Login** (`auth/login.blade.php`) - Form autentikasi dengan toggle password
- ✅ **Dashboard Utama** (`dashboard/index.blade.php`) - Statistik, quick actions, dan tabel kasus terbaru
- ✅ **Manajemen Kasus**
  - Daftar Kasus (`cases/index.blade.php`) - Filter status, pagination
  - Buat Kasus (`cases/create.blade.php`) - Form dengan AJAX submission
  - Detail Kasus (`cases/show.blade.php`) - Info lengkap, dokumen, timeline, action buttons
- ✅ **Upload Dokumen** (`upload.blade.php`) - Drag & drop, progress bar, auto-OCR option
- ✅ **Tracking** (`tracking.blade.php`) - Cek status dengan token, timeline visual
- ✅ **Hasil OCR** (`ocr-result.blade.php`) - Data terekstraksi, confidence scores
- ✅ **Panel Admin** (khusus super_admin)
  - Users (`admin/users.blade.php`) - Daftar pengguna dengan role & status
  - Sync Status (`admin/sync.blade.php`) - Neo4j sync logs, manual trigger
  - Audit Trail (`admin/audit.blade.php`) - History aktivitas sistem
  - Access Logs (`admin/logs.blade.php`) - HTTP request logs dengan filter
- ✅ **Kotak Masuk Publik** (PA/Disdukcapil staff)
  - Index (`public-inbox/index.blade.php`) - Daftar pengajuan warga dengan status badge
  - Detail (`public-inbox/show.blade.php`) - Review, approve/reject, resend WA

### 🎯 Backend Controllers
- ✅ **AuthController** - Login, logout, session handling
- ✅ **DashboardController** - Semua method dashboard dan admin
- ✅ **PublicSubmissionStaffController** - Kelola pengajuan publik
- ✅ **API Controllers** - Cases, Documents, OCR, Review, Sync

### 🧩 Components
- ✅ **admin-nav-item** - Menu sidebar dengan active state detection
- ✅ **status-badge** - Badge warna otomatis berdasarkan status
- ✅ **cases-table** - Tabel kasus reusable
- ✅ **lazy-image** - Loading gambar efisien
- ✅ **performance-hints** - Hints untuk optimasi

### 🚀 Interactive Features (Alpine.js)
- ✅ **Form Create Case** - AJAX submission tanpa reload
- ✅ **Upload Dokumen** - Progress bar real-time, auto-OCR trigger
- ✅ **Tracking** - Search instant dengan timeline visual
- ✅ **Case Detail** - Approve/reject dengan modal konfirmasi
- ✅ **Sync Panel** - Manual trigger Neo4j sync
- ✅ **Public Inbox** - Approve/reject pengajuan publik

---

## 🏗️ Struktur Dashboard

### Role & Akses

| Role                | Dashboard Akses                                            |
|---------------------|-----------------------------------------------------------|
| **super_admin**     | Users, Sync Status, Audit Trail, Access Logs             |
| **pa_assistant**    | Dashboard, Kasus (buat/view), Upload Dokumen, Tracking    |
| **pa_management**   | Dashboard, Kasus (view/approve), Tracking, Public Inbox   |
| **pa_staff**        | Dashboard, Kasus (view), Upload, Tracking, Public Inbox   |
| **disdukcapil_staff** | Dashboard, Kasus (validasi), Tracking, Public Inbox    |

### Routes Web

```php
// Public
/ → Landing page (public.blade.php)
/login → auth.login
POST /login → auth.login.post
POST /logout → auth.logout

// Dashboard (auth + role middleware)
/dashboard → dashboard.index (redirect super_admin ke /admin/users)
/dashboard/cases → cases.index
/dashboard/cases/create → cases.create (pa_assistant)
/dashboard/cases/{id} → cases.show
/dashboard/upload → upload (pa_assistant, pa_staff)
/dashboard/tracking → tracking
/dashboard/ocr/{id} → ocr.result

// Admin Panel (super_admin only)
/dashboard/admin/users → admin.users
/dashboard/admin/sync → admin.sync
/dashboard/admin/audit → admin.audit
/dashboard/admin/logs → admin.logs

// Public Inbox (pa_assistant|pa_management|disdukcapil_staff|pa_staff)
/dashboard/public-inbox → public-inbox.index
/dashboard/public-inbox/{id} → public-inbox.show
POST /dashboard/public-inbox/{id}/review → startReview (PENDING → REVIEWING)
POST /dashboard/public-inbox/{id}/approve → approve (buat kasus resmi)
POST /dashboard/public-inbox/{id}/reject → reject
POST /dashboard/public-inbox/{id}/resend-wa → resendWa
```

---

## 🚦 Cara Menjalankan Dashboard

### 1️⃣ Setup Awal

```bash
# Masuk ke direktori project
cd d:\ProyekTA

# Pastikan .env sudah dikonfigurasi (lihat .env.example)
# Pastikan semua service Docker sudah running
docker-compose up -d

# Install dependencies Laravel (jika belum)
composer install

# Generate application key (jika belum)
php artisan key:generate

# Generate JWT secret (jika belum)
php artisan jwt:secret

# Jalankan migrasi database
docker exec sipadu_app php artisan migrate --force

# Seed data awal (user, institusi, roles)
docker exec sipadu_app php artisan db:seed
```

### 2️⃣ Akses Dashboard

```
URL Dashboard: http://localhost:8080/dashboard
URL Login: http://localhost:8080/login
```

### 3️⃣ Kredensial Default (setelah seed)

Sesuaikan dengan seeder Anda, contoh:

```
Super Admin:
Email: admin@sipadu.go.id
Password: password

PA Assistant:
Email: assistant@pa-jakarta.go.id
Password: password

PA Management:
Email: management@pa-jakarta.go.id
Password: password

Disdukcapil Staff:
Email: staff@disdukcapil-jakarta.go.id
Password: password
```

> **Note**: Ganti password default di production!

---

## 🎯 Fitur Utama Dashboard

### 📌 Dashboard Utama
- **Statistik Kasus**: Total, Draft, Diproses, Selesai, Ditolak
- **Quick Actions**: Panel aksi cepat sesuai role (buat kasus, upload, review, validasi)
- **Kasus Terbaru**: 5 kasus terbaru dengan link detail
- **Notifikasi**: Badge pengajuan publik pending di sidebar & header

### 📂 Manajemen Kasus
- **Filter**: Status kasus (DRAFT, SUBMITTED, OCR_PROCESSED, dll)
- **Search**: Cari berdasarkan nomor kasus atau pemohon
- **Pagination**: 15 kasus per halaman
- **Detail View**: Info lengkap, dokumen (+ download), timeline transisi, tombol aksi

### ⚡ Actions pada Case Detail
| Status Case                | Role             | Aksi Tersedia                            |
|---------------------------|------------------|------------------------------------------|
| DRAFT                     | submitter        | Submit (DRAFT → SUBMITTED)               |
| PA_REVIEW                 | pa_management    | Approve / Reject (→ DISDUKCAPIL_VALIDATION atau REJECTED) |
| DISDUKCAPIL_VALIDATION    | disdukcapil_staff | Validate / Reject (→ COMPLETED atau REJECTED) |

### 📤 Upload Dokumen
- **Supported Files**: JPG, PNG, PDF, TIFF (max 10MB)
- **Drag & Drop**: UI modern dengan preview
- **Auto-OCR**: Checkbox untuk proses OCR otomatis setelah upload
- **Progress Bar**: Real-time upload progress

### 🔍 Tracking Kasus
- **Public Access**: Bisa diakses tanpa login (tergantung implementasi API)
- **Token Search**: Input token tracking → tampilkan status, info, timeline
- **Step Progress**: Visual progress bar (DRAFT → SUBMITTED → OCR → PA_REVIEW → DISDUKCAPIL → COMPLETED)

### 🤖 Hasil OCR
- **Extracted Fields**: NIK, No. KK, Nama, Tgl Lahir, Alamat, dll
- **Confidence Score**: Visual bar untuk setiap field (hijau ≥85%, kuning ≥70%, merah <70%)
- **Overall Confidence**: Persentase total akurasi OCR
- **Validation Errors**: Alert jika ada field yang tidak valid

### 👥 Manajemen User (Admin)
- **Daftar User**: Nama, email, role, institusi, status, tanggal bergabung
- **Pagination**: 20 user per halaman
- **Filter**: (dapat ditambahkan) berdasarkan role atau status

### 🔄 Graph Sync Status (Admin)
- **Statistik**: Pending, Processing, Success, Failed queue
- **Manual Trigger**: Tombol untuk trigger sync Neo4j secara manual
- **Recent Logs**: 50 log sinkronisasi terbaru dengan status & durasi

### 🛡️ Audit Trail (Admin)
- **Log Aktivitas**: Semua aksi user (create case, approve, reject, dll)
- **Info Detail**: Timestamp, user, aksi, subjek (Case/Document ID), IP address
- **Pagination**: 50 log per halaman

### 📝 Access Logs (Admin)
- **HTTP Request Logs**: Method, path, status code, response time, IP, user
- **Filter**: Path, status (2xx OK, 4xx/5xx Error, slow >1s)
- **Statistik**: Total request, success count, error count, avg response time
- **Performance Alert**: Highlight request lambat (>1000ms)

### 📬 Kotak Masuk Pengajuan Publik
- **Daftar Pengajuan**: NIK, nama, nomor WA, status, jumlah dokumen
- **Status Badge**: PENDING (kuning), REVIEWING (biru), APPROVED (hijau), REJECTED (merah), dll
- **Filter**: Status, search (NIK/nama/token)
- **Pagination**: 20 pengajuan per halaman

### 📋 Detail Pengajuan Publik
- **Data Pemohon**: NIK, nama, nomor WA, IP address
- **Data Perceraian**: Nama/NIK pasangan, tanggal cerai, nomor putusan
- **Dokumen**: Daftar dokumen yang diupload (KTP, KK, Akta, dll)
- **Notifikasi WA**: Status pengiriman, waktu kirim, error message (jika gagal)
- **Aksi**:
  - **Tinjau** (PENDING → REVIEWING)
  - **Setujui & Buat Kasus** (modal: pilih institusi, notes)
  - **Tolak** (modal: wajib isi alasan)
  - **Kirim Ulang WA**

---

## 🎨 Design System

### Color Palette
```javascript
primary: #1d4ed8 (blue-700)
primary-light: #3b82f6 (blue-500)
primary-dark: #1e3a8a (blue-900)
sidebar: #0f172a (slate-900)
sidebar-hover: #1e293b (slate-800)
sidebar-active: #1d4ed8 (blue-700)
```

### Status Colors
```php
DRAFT                    → gray
SUBMITTED                → blue
OCR_PROCESSED            → indigo
PA_REVIEW                → yellow
DISDUKCAPIL_VALIDATION   → orange
COMPLETED                → green
ARCHIVED                 → purple
REJECTED                 → red
```

### Typography
- **Heading**: Font-bold, text-gray-800
- **Body**: Text-sm/base, text-gray-600
- **Code/Mono**: font-mono, tracking-tight

### Spacing
- **Card Padding**: p-5 atau p-6
- **Section Gap**: space-y-6
- **Grid Gap**: gap-4

---

## 🔧 Troubleshooting

### Error: "Unauthenticated" di Dashboard
**Solusi**: 
1. Pastikan sudah login (`/login`)
2. Cek session driver di `.env` → `SESSION_DRIVER=redis`
3. Pastikan Redis running: `docker ps | grep redis`

### Error: "This action is unauthorized" (ReBAC)
**Solusi**: 
1. Pastikan Neo4j running & sudah di-seed
2. Check GraphSyncJob sudah jalan (user tersync ke Neo4j)
3. Trigger manual sync: Dashboard → Admin → Sync Status → "Sync Sekarang"

### Upload Dokumen Gagal
**Solusi**: 
1. Cek file size max di `php.ini`: `upload_max_filesize=10M`, `post_max_size=12M`
2. Pastikan folder `storage/app/documents` writable
3. Check OCR service running: `docker ps | grep ocr-service`

### Dashboard Lambat
**Solusi**: 
1. Enable query caching di `.env`: `CACHE_DRIVER=redis`
2. Check `QueryOptimizationService` sudah digunakan di controller
3. Monitor dengan Access Logs (slow requests >1s)
4. Optimize eager loading: `with(['submitter:id,name', 'institution:id,name'])`

### Sync Neo4j Gagal
**Solusi**: 
1. Check koneksi Neo4j: `docker exec sipadu_app php artisan neo4j:test`
2. Lihat error di `storage/logs/laravel.log`
3. Cek credential di `.env`: `NEO4J_URI`, `NEO4J_USERNAME`, `NEO4J_PASSWORD`
4. Pastikan password minimal 8 karakter (requirement Neo4j 5.x)

---

## 🚀 Performance Tips

1. **Caching**: Semua ReBACService policies di-cache (TTL: 300s)
2. **Eager Loading**: Sudah optimal di semua controller
3. **Pagination**: Aktif di semua list view
4. **Lazy Loading Images**: Gunakan component `<x-lazy-image>`
5. **Query Optimization**: `QueryOptimizationService::optimizeSelect()` aktif

---

## 📚 API Endpoints (untuk AJAX)

```
POST   /api/v1/cases                    # Buat kasus baru
POST   /api/v1/documents/upload         # Upload dokumen
POST   /api/v1/ocr/process               # Trigger OCR
POST   /api/v1/review/pa                 # PA approve/reject
POST   /api/v1/review/disdukcapil        # Disdukcapil validate/reject
POST   /api/v1/review/submit/{id}        # Submit case (DRAFT → SUBMITTED)
GET    /api/v1/tracking/{token}          # Track by token
POST   /api/v1/sync/graph                # Trigger Neo4j sync (admin)
```

> **Auth**: Semua endpoint butuh JWT token di header (kecuali tracking & public submission)
> 
> Header: `Authorization: Bearer {token}`

---

## 📖 Workflow Contoh

### Skenario 1: PA Assistant Buat Kasus Baru
1. Login → Dashboard
2. Klik "Buat Pengajuan" (quick action card)
3. Isi form: institusi, NIK pasangan, nama, tanggal cerai, no. putusan
4. Submit → kasus dibuat dengan status DRAFT
5. Redirect ke `/dashboard/cases/{id}`
6. Upload dokumen (KTP, KK, Akta Cerai)
7. Klik "Submit" di detail case → status jadi SUBMITTED
8. Job OCRJob dijalankan otomatis

### Skenario 2: PA Management Review Kasus
1. Login → Dashboard
2. Klik "Review Hasil OCR" (quick action)
3. Pilih kasus dengan status OCR_PROCESSED
4. Cek dokumen & hasil OCR
5. Klik "Setujui" → status jadi DISDUKCAPIL_VALIDATION
6. atau "Tolak" (isi alasan) → status jadi REJECTED

### Skenario 3: Disdukcapil Validasi
1. Login → Dashboard
2. Klik "Validasi Masuk" (quick action)
3. Pilih kasus DISDUKCAPIL_VALIDATION
4. Cek data final
5. Klik "Validasi" → status jadi COMPLETED
6. Notifikasi WA otomatis dikirim ke pemohon

### Skenario 4: Warga Submit Pengajuan Publik
1. Warga isi form di landing page `/public/submission`
2. Upload dokumen (KTP, KK)
3. Sistem generate tracking token
4. WA otomatis dikirim ke nomor warga
5. Petugas cek di Dashboard → Public Inbox
6. Approve → buat kasus resmi
7. atau Reject (isi alasan)

---

## ✅ Checklist Testing

### Functional Testing
- [ ] Login/logout berhasil
- [ ] Dashboard load dengan statistik benar
- [ ] Filter status kasus bekerja
- [ ] Create case via form berhasil (AJAX)
- [ ] Upload dokumen dengan progress bar
- [ ] OCR auto-trigger setelah upload
- [ ] Approve/reject case (PA & Disdukcapil)
- [ ] Timeline case update setelah transisi
- [ ] Tracking by token tampil hasil
- [ ] Public inbox filter & search
- [ ] Approve pengajuan publik → case dibuat
- [ ] Reject pengajuan publik → WA terkirim
- [ ] Admin: Manual sync trigger
- [ ] Admin: Access logs filter bekerja

### Security Testing
- [ ] Middleware role aktif (super_admin tidak bisa akses PA dashboard)
- [ ] ReBACService enforce dijalankan di semua protected action
- [ ] CSRF token aktif di semua form POST
- [ ] JWT token expired → auto logout
- [ ] Session regenerate setelah login

### Performance Testing
- [ ] Dashboard load <500ms (tanpa OCR processing)
- [ ] Cases list pagination smooth
- [ ] Upload dokumen 5MB selesai <10s
- [ ] Neo4j sync manual <5s untuk 100 nodes
- [ ] Access logs pagination tidak timeout

---

## 📞 Support

Jika ada masalah:
1. Cek `storage/logs/laravel.log`
2. Cek error browser console (F12)
3. Pastikan semua service Docker running: `docker-compose ps`
4. Re-seed database jika perlu: `php artisan migrate:fresh --seed`

---

**Dashboard SiPadu v1.0.0** — Sistem Integrasi PA & Disdukcapil
