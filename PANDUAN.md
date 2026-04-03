# SiPadu – Panduan Menjalankan & Pengujian

> **Sistem Pembaruan Dokumen Pasca Perceraian**  
> Laravel 11 · MySQL 8 · Neo4j 5 · Python OCR Microservice · Docker Compose

> **Update terakhir: 11 Maret 2026** – Sistem Validasi Otomatis OCR vs Input Manual.

---

## ⚠️ Catatan Penting: Frontend Architecture

Sistem SiPadu sepenuhnya menggunakan **Laravel 11 dengan Blade templating**. File HTML statis di root folder (superadmin.html, admin-\*.html, dashboard.html, dll) telah dihapus pada 28 Februari 2026 karena hanya merupakan mockup prototyping yang tidak terhubung ke backend.

**Sistem yang aktif:**

- ✅ Backend: Laravel 11 API (`app/Http/Controllers/`)
- ✅ Frontend Dashboard: Laravel Blade (`resources/views/dashboard/`)
- ✅ Public Pages: Laravel Blade (`resources/views/pengajuan/`, `resources/views/tracking/`)
- ✅ Authentication: Laravel JWT + Middleware
- ✅ Database: Laravel Eloquent ORM

Saat mengakses `http://localhost:8000/dashboard`, yang ter-render adalah `resources/views/dashboard/index.blade.php`, **bukan** file HTML statis.

---

## Daftar Isi

1. [Prasyarat](#1-prasyarat)
2. [Cara Menjalankan (Docker – Direkomendasikan)](#2-cara-menjalankan-docker--direkomendasikan)
3. [Cara Menjalankan (Lokal / Manual)](#3-cara-menjalankan-lokal--manual)
4. [Kredensial Default](#4-kredensial-default)
5. [Cara Pengujian API](#5-cara-pengujian-api)
6. [Cara Pengujian Unit & Fitur (PHPUnit)](#6-cara-pengujian-unit--fitur-phpunit)
7. [Pengujian OCR Microservice](#7-pengujian-ocr-microservice)
8. [Daftar Endpoint API](#8-daftar-endpoint-api)
9. [Pemecahan Masalah](#9-pemecahan-masalah)
10. [Checklist Kelengkapan Konfigurasi](#10-checklist-kelengkapan-konfigurasi)
11. [Konfigurasi Neo4j Lengkap](#11-konfigurasi-neo4j-lengkap)
12. [Riwayat Perbaikan Konfigurasi](#12-riwayat-perbaikan-konfigurasi)
13. [Fitur Pengajuan Publik (Tanpa Akun)](#13-fitur-pengajuan-publik-tanpa-akun)
14. [Perancangan Sistem OCR](#14-perancangan-sistem-ocr)

---

## 1. Prasyarat

### Docker (cara yang direkomendasikan)

| Perangkat lunak | Versi minimum |
| --------------- | ------------- |
| Docker Desktop  | 24.x          |
| Docker Compose  | v2.20.x       |

### Lokal / Manual

| Perangkat lunak                      | Versi minimum |
| ------------------------------------ | ------------- |
| PHP                                  | 8.1           |
| Composer                             | 2.x           |
| MySQL                                | 8.0           |
| Redis                                | 7.0           |
| Neo4j                                | 5.x           |
| Python                               | 3.11          |
| Tesseract OCR                        | 5.x           |
| Node.js (opsional, untuk build aset) | 18.x          |

---

## 2. Cara Menjalankan (Docker – Direkomendasikan)

### Langkah 1 – Clone & salin konfigurasi

```bash
# Sudah di folder proyek
cd d:\ProyekTA

# Salin file environment
copy .env.example .env
```

### Langkah 2 – Isi variabel wajib di `.env`

Buka file `.env` dan sesuaikan nilai berikut:

```dotenv
APP_KEY=           # diisi otomatis pada langkah 4
APP_URL=http://localhost:8000

DB_HOST=mysql          # wajib: nama service Docker
DB_PASSWORD=secret

NEO4J_HOST=neo4j       # wajib: nama service Docker
NEO4J_PASSWORD=neo4jSecret1   # WAJIB minimal 8 karakter!

REDIS_HOST=redis       # wajib: nama service Docker

OCR_SERVICE_URL=http://ocr-service:5001   # wajib: URL internal Docker
OCR_SECRET_KEY=ocr_rahasia_ganti_ini      # bebas, tapi harus sama di semua service
JWT_SECRET=                               # diisi otomatis pada langkah 4
QUEUE_CONNECTION=database
```

> ⚠️ **Password Neo4j minimal 8 karakter.** Password pendek seperti `secret` (6 karakter) akan menyebabkan Neo4j gagal start.

### Langkah 3 – Build & jalankan semua container

```bash
docker compose up -d --build
```

Perintah ini akan membangun dan menjalankan **7 container**:

| Container               | Port host | Keterangan                 |
| ----------------------- | --------- | -------------------------- |
| `pa_disdukcapil_nginx`  | 8000      | Web server (HTTP)          |
| `pa_disdukcapil_app`    | –         | PHP-FPM Laravel            |
| `pa_disdukcapil_worker` | –         | Queue worker (OCR + Graph) |
| `pa_disdukcapil_ocr`    | 5001      | Python OCR microservice    |
| `pa_disdukcapil_mysql`  | 3306      | MySQL 8                    |
| `pa_disdukcapil_redis`  | 6379      | Redis 7                    |
| `pa_disdukcapil_neo4j`  | 7474/7687 | Neo4j 5 (browser/Bolt)     |

### Langkah 4 – Inisialisasi aplikasi Laravel

```bash
# Generate JWT_SECRET (APP_KEY sudah di-generate saat build)
docker compose exec app php artisan jwt:secret

# Buat link storage publik
docker compose exec app php artisan storage:link

# Jalankan migrasi database + seeding data awal
docker compose exec app php artisan migrate --seed

# Bersihkan cache konfigurasi
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
```

> **Catatan:** Jika perintah `php artisan migrate --seed` gagal karena tabel `cache` belum ada, pastikan file migrasi
> `2026_XXXX_create_cache_table.php` memiliki timestamp lebih kecil dari `create_permission_tables.php`.

### Langkah 5 – Verifikasi

Buka browser dan akses:

- **Aplikasi web** → [http://localhost:8000](http://localhost:8000)
- **Neo4j Browser** → [http://localhost:7474](http://localhost:7474)  
  Login: `neo4j` / `neo4jSecret1` _(atau sesuai `NEO4J_PASSWORD` di `.env`)_
- **OCR Microservice** (health check) → [http://localhost:5001/health](http://localhost:5001/health)

Verifikasi API login:

```bash
curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sipadu.go.id","password":"Admin@123456"}'
# Expected: {"token_type":"bearer","expires_in":3600,...}
```

### Menghentikan sistem

```bash
docker compose down           # hentikan container, pertahankan data
docker compose down -v        # hentikan dan hapus semua volume (data hilang)
```

### Melihat log

```bash
docker compose logs -f app      # Laravel
docker compose logs -f worker   # Queue worker
docker compose logs -f ocr-service  # OCR microservice
```

---

## 3. Cara Menjalankan (Lokal / Manual)

> Gunakan pendekatan ini jika tidak menggunakan Docker.

### Langkah 1 – Persiapan dependensi PHP

```bash
cd d:\ProyekTA
composer install
copy .env.example .env
php artisan key:generate
php artisan jwt:secret
```

### Langkah 2 – Konfigurasi database

Edit `.env` sesuaikan `DB_*`, `REDIS_*`, `NEO4J_*`.  
Kemudian jalankan migrasi:

```bash
php artisan migrate --seed
php artisan storage:link
```

### Langkah 3 – Jalankan OCR microservice (terminal terpisah)

```bash
cd ocr-service
pip install -r requirements.txt
python app.py
# atau dengan gunicorn (production):
# gunicorn -w 4 -b 0.0.0.0:5001 app:app
```

### Langkah 4 – Jalankan queue worker (terminal terpisah)

```bash
cd d:\ProyekTA
php artisan queue:work --queue=ocr,graph,default --tries=3 --timeout=120
```

### Langkah 5 – Jalankan development server

```bash
php artisan serve --port=8000
```

Akses di [http://localhost:8000](http://localhost:8000).

---

## 4. Kredensial Default

Setelah `php artisan migrate --seed`, akun berikut tersedia:

| Nama               | Email                              | Password       | Role                |
| ------------------ | ---------------------------------- | -------------- | ------------------- |
| Administrator      | `admin@sipadu.go.id`               | `Admin@123456` | `super_admin`       |
| Dina Asisten PA    | `asisten@pa-painan.go.id`          | `Pass@12345`   | `pa_assistant`      |
| H. Budi Ketua PA   | `ketua@pa-painan.go.id`            | `Pass@12345`   | `pa_management`     |
| Andi Staf PA       | `staf@pa-painan.go.id`             | `Pass@12345`   | `pa_staff`          |
| Rini Petugas Capil | `petugas@disdukcapil-pessel.go.id` | `Pass@12345`   | `disdukcapil_staff` |

> Ganti semua password di lingkungan produksi.

---

## 5. Cara Pengujian API

### Menggunakan cURL

#### a. Login & dapatkan token JWT

```bash
curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sipadu.go.id","password":"Admin@123456"}' \
  | python -m json.tool
```

Simpan nilai `access_token` dari respons, lalu gunakan di header:

```
Authorization: Bearer <access_token>
```

#### b. Buat kasus baru

```bash
curl -s -X POST http://localhost:8000/api/v1/cases \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <access_token>" \
  -d '{
    "petitioner_name": "Ahmad Warga",
    "petitioner_nik": "3174010101900001",
    "respondent_name": "Siti Warga",
    "respondent_nik": "3174010201900002",
    "divorce_date": "2025-06-01",
    "notes": "Pembaruan KTP dan KK"
  }'
```

#### c. Unggah dokumen

```bash
curl -s -X POST http://localhost:8000/api/v1/documents/upload \
  -H "Authorization: Bearer <access_token>" \
  -F "case_id=1" \
  -F "document_type=KTP" \
  -F "file=@/path/to/ktp.jpg"
```

#### d. Proses OCR

```bash
curl -s -X POST http://localhost:8000/api/v1/ocr/process \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <access_token>" \
  -d '{"document_id": 1}'
```

#### e. Cek status dengan token tracking (tanpa login)

```bash
curl -s http://localhost:8000/api/v1/tracking/TRK-xxxxxxxxxxxxxxxx
```

### Menggunakan Postman / Insomnia

1. Impor koleksi environment dengan base URL `http://localhost:8000`
2. Set variabel `base_url = http://localhost:8000`
3. Jalankan request **POST /api/v1/auth/login** dan simpan `access_token` ke variabel koleksi
4. Semua endpoint lain akan otomatis menggunakan token via header `Authorization: Bearer {{token}}`

---

## 6. Cara Pengujian Unit & Fitur (PHPUnit)

### Konfigurasi lingkungan pengujian

Buat file `.env.testing` di root proyek:

```dotenv
APP_ENV=testing
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sipadu_testing
DB_USERNAME=root
DB_PASSWORD=secret

NEO4J_HOST=localhost
NEO4J_PASSWORD=neo4jSecret1

REDIS_HOST=127.0.0.1
OCR_SERVICE_URL=http://localhost:5001
OCR_SECRET_KEY=ocr_testing

QUEUE_CONNECTION=sync
CACHE_DRIVER=array
SESSION_DRIVER=array
MAIL_MAILER=array
```

Buat database pengujian:

```sql
CREATE DATABASE sipadu_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Menjalankan semua test

```bash
# Lokal
php artisan test

# Docker
docker compose exec app php artisan test
```

### Menjalankan test spesifik

```bash
# Test satu file
php artisan test tests/Unit/Services/OCRServiceTest.php

# Test satu method
php artisan test --filter=test_ocr_dispatch_creates_job

# Test berdasarkan grup
php artisan test --group=ocr
php artisan test --group=workflow
php artisan test --group=rebac

# Dengan laporan coverage (membutuhkan Xdebug atau PCOV)
php artisan test --coverage
php artisan test --coverage-html reports/coverage
```

### Contoh struktur test yang disarankan

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── OCRServiceTest.php          # unit test OCRService
│   │   ├── WorkflowServiceTest.php     # unit test state machine
│   │   ├── ReBACServiceTest.php        # unit test policy engine
│   │   └── GraphServiceTest.php        # unit test Neo4j helper
│   └── Models/
│       ├── CaseModelTest.php           # tracking token, canTransitionTo
│       └── OcrResultTest.php           # isHighConfidence, toValidatedArray
└── Feature/
    ├── Auth/
    │   └── AuthControllerTest.php      # login, logout, refresh
    ├── Cases/
    │   ├── CaseControllerTest.php      # CRUD kasus
    │   └── WorkflowTest.php            # skenario alur penuh
    ├── Documents/
    │   └── DocumentControllerTest.php  # upload, download
    ├── OCR/
    │   └── OCRControllerTest.php       # mock HTTP ke microservice
    ├── Review/
    │   └── ReviewControllerTest.php    # PA review & Disdukcapil validasi
    └── Tracking/
        └── TrackingControllerTest.php  # tracking publik
```

### Contoh kode test (alur kerja penuh)

```php
// tests/Feature/Cases/WorkflowTest.php

public function test_full_workflow_from_draft_to_completed(): void
{
    $admin    = User::factory()->create()->assignRole('pa_management');
    $capil    = User::factory()->create()->assignRole('disdukcapil_staff');
    $assistant = User::factory()->create()->assignRole('pa_assistant');

    // 1. Buat kasus
    $case = CaseModel::factory()->create([
        'petitioner_id' => $assistant->id,
        'status'        => 'DRAFT',
    ]);

    // 2. Submit (PA Assistant meneruskan ke Management)
    $this->actingAs($assistant)
         ->postJson("/api/v1/review/submit/{$case->id}")
         ->assertOk();

    $this->assertEquals('SUBMITTED', $case->fresh()->status);

    // 3. Mock OCR (simulasi sync)
    $this->mock(OCRService::class, fn($m) =>
        $m->shouldReceive('process')->andReturn(OcrResult::factory()->make([
            'ocr_status'         => 'SUCCESS',
            'overall_confidence' => 0.95,
        ]))
    );

    // 4. PA Management Review & Approve
    $this->actingAs($admin)
         ->postJson('/api/v1/review/pa', [
             'case_id' => $case->id,
             'action'  => 'approve',
             'notes'   => 'Dokumen lengkap',
         ])
         ->assertOk();

    // 5. Disdukcapil Validasi
    $this->actingAs($capil)
         ->postJson('/api/v1/review/disdukcapil', [
             'case_id' => $case->id,
             'action'  => 'approve',
             'notes'   => 'NIK sesuai',
         ])
         ->assertOk();

    $this->assertEquals('COMPLETED', $case->fresh()->status);
}
```

---

## 7. Pengujian OCR Microservice

### Health check

```bash
curl http://localhost:5001/health
# Expected: {"status": "ok", "tesseract_version": "5.x.x"}
```

### Uji OCR langsung (bypass Laravel)

```bash
curl -X POST http://localhost:5001/ocr/process \
  -H "X-OCR-Secret: <isi OCR_SECRET_KEY dari .env>" \
  -F "file=@/path/to/ktp_sample.jpg" \
  | python -m json.tool
```

### Respons yang diharapkan

```json
{
  "nik": "3174010101900001",
  "kk": "3174010101900000",
  "nama": "AHMAD WARGA",
  "tgl_lahir": "01-01-1990",
  "alamat": "JL. CONTOH NO. 1",
  "confidence": {
    "nik": 0.97,
    "nama": 0.93,
    "alamat": 0.88
  },
  "raw_text": "...",
  "engine_version": "tesseract-5.x"
}
```

### Menjalankan test Python

```bash
cd ocr-service
pip install pytest
pytest tests/ -v                   # jika direktori tests/ ada
```

---

## 8. Daftar Endpoint API

Base URL: `http://localhost:8000/api/v1`

### Autentikasi

| Method | Endpoint        | Keterangan               | Auth |
| ------ | --------------- | ------------------------ | ---- |
| POST   | `/auth/login`   | Login, terima JWT        | –    |
| POST   | `/auth/logout`  | Logout, invalidasi token | ✅   |
| POST   | `/auth/refresh` | Perbarui token           | ✅   |
| GET    | `/auth/me`      | Profil pengguna aktif    | ✅   |

### Kasus

| Method | Endpoint             | Keterangan                 | Auth |
| ------ | -------------------- | -------------------------- | ---- |
| GET    | `/cases`             | Daftar kasus (filter role) | ✅   |
| POST   | `/cases`             | Buat kasus baru            | ✅   |
| GET    | `/cases/{id}`        | Detail kasus               | ✅   |
| PATCH  | `/cases/{id}/assign` | Tugaskan petugas           | ✅   |

### Dokumen

| Method | Endpoint                   | Keterangan     | Auth |
| ------ | -------------------------- | -------------- | ---- |
| POST   | `/documents/upload`        | Upload dokumen | ✅   |
| GET    | `/documents/{id}`          | Info dokumen   | ✅   |
| GET    | `/documents/download/{id}` | Unduh dokumen  | ✅   |

### OCR

| Method | Endpoint           | Keterangan             | Auth |
| ------ | ------------------ | ---------------------- | ---- |
| POST   | `/ocr/process`     | Dispatch pekerjaan OCR | ✅   |
| GET    | `/ocr/result/{id}` | Hasil OCR per dokumen  | ✅   |
| GET    | `/ocr/job/{id}`    | Status job OCR         | ✅   |

### Tinjauan & Validasi

| Method | Endpoint                  | Keterangan                | Auth | Role                |
| ------ | ------------------------- | ------------------------- | ---- | ------------------- |
| POST   | `/review/submit/{caseId}` | PA Assistant submit kasus | ✅   | `pa_assistant`      |
| POST   | `/review/pa`              | PA approve/reject         | ✅   | `pa_management`     |
| POST   | `/review/disdukcapil`     | Disdukcapil validasi      | ✅   | `disdukcapil_staff` |

### Tracking Publik

| Method | Endpoint            | Keterangan           | Auth |
| ------ | ------------------- | -------------------- | ---- |
| GET    | `/tracking/{token}` | Status via kode TRK- | –    |

### Sinkronisasi (Admin)

| Method | Endpoint       | Keterangan                  | Auth | Role          |
| ------ | -------------- | --------------------------- | ---- | ------------- |
| POST   | `/sync/graph`  | Trigger sinkronisasi Neo4j  | ✅   | `super_admin` |
| GET    | `/sync/status` | Status antrean sinkronisasi | ✅   | `super_admin` |

---

## 9. Pemecahan Masalah

### Container tidak mau start

```bash
# Cek log spesifik
docker compose logs mysql
docker compose logs neo4j

# Reset total (hapus semua volume)
docker compose down -v
docker compose up -d --build
```

### `APP_KEY` atau `JWT_SECRET` kosong

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:secret
docker compose exec app php artisan config:clear
```

### Migrasi gagal

```bash
# Cek koneksi database
docker compose exec app php artisan db:show

# Rollback dan ulangi
docker compose exec app php artisan migrate:fresh --seed
```

#### Error: `Table 'cache' doesn't exist` saat migrate

Urutkan file migrasi sehingga `create_cache_table` berjalan sebelum `create_permission_tables`.
Rename timestamp file cache menjadi lebih kecil dari permission tables:

```bash
# Contoh: ubah 2026_02_21_174214_create_cache_table.php → 2026_02_21_173900_create_cache_table.php
mv database/migrations/2026_02_21_174214_create_cache_table.php \
   database/migrations/2026_02_21_173900_create_cache_table.php
```

#### Error: `Interface JWTSubject not found`

Install paket JWT yang belum ada di `composer.json`:

```bash
docker compose exec app composer require php-open-source-saver/jwt-auth
docker compose exec app php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
docker compose exec app php artisan jwt:secret --force
```

#### Error: `Class Controller not found`

Di Laravel 11, file `app/Http/Controllers/Controller.php` tidak di-generate otomatis. Buat secara manual:

```bash
# app/Http/Controllers/Controller.php
cat > app/Http/Controllers/Controller.php << 'EOF'
<?php
namespace App\Http\Controllers;
abstract class Controller {}
EOF
```

#### API returns 404 padahal route terdaftar

Pastikan direktori `public/` ada dan berisi `index.php` Laravel. Ini adalah web root yang dibutuhkan nginx:

```bash
ls public/index.php   # harus ada
# Jika tidak ada, buat manual atau copy dari proyek Laravel baru
```

Juga pastikan `config/auth.php` ada dan memiliki guard `api` dengan driver JWT:

```bash
docker compose exec app php artisan config:publish auth
# Kemudian tambahkan guard 'api' di config/auth.php
```

```php
// config/auth.php
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'users'],
    'api' => ['driver' => 'jwt',     'provider' => 'users'],  // tambahkan ini
],
```

### OCR microservice error 401

Pastikan nilai `OCR_SECRET_KEY` di `.env` sama persis dengan yang digunakan di `ocr-service`. Cek dengan:

```bash
docker compose exec app printenv OCR_SECRET_KEY
docker compose exec ocr-service printenv OCR_SECRET_KEY
```

> Juga pastikan `OCR_SERVICE_URL=http://ocr-service:5001` di `.env` (bukan `localhost:5001`) saat menggunakan Docker.

### Neo4j tidak terhubung / terus restart

Penyebab paling umum: **password kurang dari 8 karakter** (Neo4j 5 menolak password < 8 karakter).

```bash
# Di .env, gunakan password minimal 8 karakter:
NEO4J_PASSWORD=neo4jSecret1

# Cek log untuk konfirmasi
docker compose logs neo4j --tail=20

# Setelah password diubah, hapus volume lama dan restart:
docker compose down -v   # PERINGATAN: data Neo4j ikut terhapus
docker compose up -d neo4j

# Verifikasi koneksi
docker compose exec neo4j cypher-shell -u neo4j -p neo4jSecret1 "RETURN 1;"

# Atau cek dari Laravel
docker compose exec app php artisan tinker
>>> app(\App\Services\GraphService::class)->run('RETURN 1 AS test');
```

> **Penting:** Nilai `NEO4J_PASSWORD` di `.env` harus cocok dengan `NEO4J_AUTH` di `docker-compose.yml` DAN dengan password yang digunakan di healthcheck.

### Queue tidak memproses

```bash
# Restart worker
docker compose restart worker

# Cek failed jobs
docker compose exec app php artisan queue:failed

# Retry semua gagal
docker compose exec app php artisan queue:retry all
```

### Error 419: CSRF Token Mismatch / PAGE EXPIRED

Error 419 saat login adalah masalah CSRF (Cross-Site Request Forgery) protection. Berikut troubleshooting lengkap:

#### A. Diagnosa Cepat

```powershell
# 1. Cek session files terbuat
dir storage\framework\sessions
# Harus ada file session

# 2. Cek log Laravel untuk detail error
Get-Content storage\logs\laravel.log -Tail 50

# 3. Test via curl (bypass browser)
curl -X POST http://localhost:8000/auth/login `
  -H "Content-Type: application/x-www-form-urlencoded" `
  -d "email=asisten@pa-painan.go.id&password=Pass@12345&_token=test"
```

#### B. Solusi Berdasarkan Penyebab

**1. Browser Cache / Extension:**

- ✅ **SOLUSI TERCEPAT:** Buka **Incognito/Private mode**
- Clear browser cache & cookies completely
- Disable browser extensions (uBlock, Privacy Badger, dll)
- Test dengan browser lain (Chrome, Firefox, Edge)

**2. Session Driver Issue:**

```powershell
# Cek .env
cat .env | Select-String SESSION_DRIVER
# Harus: SESSION_DRIVER=file

# Clear session files
docker compose exec app rm -rf storage/framework/sessions/*
docker compose exec app php artisan session:table  # jika pakai database
docker compose exec app php artisan migrate

# Verify session directory permissions
docker compose exec app ls -la storage/framework/sessions
# Harus writable (755/775)
```

**3. Cookie Settings:**

Tambahkan di `.env`:

```dotenv
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false      # false untuk http://
SESSION_SAME_SITE=lax            # lax untuk localhost
SESSION_DOMAIN=null              # null untuk localhost
```

Di `docker-compose.yml`, tambahkan:

```yaml
environment:
  SESSION_SECURE_COOKIE: false
  SESSION_SAME_SITE: lax
```

Restart containers:

```powershell
docker compose restart app nginx
```

**4. CSRF Middleware Configuration:**

Verifikasi `app/Http/Middleware/VerifyCsrfToken.php` ada dan benar:

```php
<?php
namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        // Jangan tambahkan /auth/login kecuali memang ingin disable CSRF
    ];
}
```

**5. View Template Check:**

Verifikasi `resources/views/auth/login.blade.php` memiliki:

```blade
<meta name="csrf-token" content="{{ csrf_token() }}" />

<form method="POST" action="{{ route('auth.login.post') }}">
    @csrf
    <!-- form fields -->
</form>
```

**6. Clear All Laravel Caches:**

```powershell
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan route:clear
docker compose exec app rm -rf bootstrap/cache/*.php
docker compose restart app nginx
```

**7. Debug Mode (Development Only):**

Tambahkan logging di `app/Http/Middleware/VerifyCsrfToken.php`:

```php
use Illuminate\Support\Facades\Log;

public function handle($request, \Closure $next)
{
    Log::info('CSRF Check', [
        'url' => $request->fullUrl(),
        'session_token' => $request->session()->token(),
        'input_token' => $request->input('_token'),
        'has_session' => $request->hasSession(),
    ]);

    return parent::handle($request, $next);
}
```

Lalu test login dan cek log:

```powershell
Get-Content storage\logs\laravel.log -Tail 100 -Wait
```

#### C. Test Manual (PowerShell)

Jika browser tidak work, test dengan PowerShell untuk verify backend:

```powershell
# Step 1: Get CSRF token & session cookie
$response = Invoke-WebRequest -Uri "http://localhost:8000/auth/login" -SessionVariable session -UseBasicParsing
$token = ($response.Content | Select-String -Pattern 'name="_token"\s+value="([^"]+)"').Matches[0].Groups[1].Value

# Step 2: Submit login
$body = @{
    email = "asisten@pa-painan.go.id"
    password = "Pass@12345"
    _token = $token
}

$loginResponse = Invoke-WebRequest -Uri "http://localhost:8000/auth/login" `
    -Method POST `
    -Body $body `
    -WebSession $session `
    -MaximumRedirection 0 `
    -ErrorAction SilentlyContinue

Write-Host "Status: $($loginResponse.StatusCode)"
# Expected: 302 (redirect ke dashboard)
```

Jika PowerShell berhasil (302) tapi browser gagal (419), masalahnya di browser/extension.

#### D. Jika Masih Error

Jika semua cara di atas gagal, coba migrasi ke **Laragon** untuk development yang lebih stabil di Windows. Lihat panduan lengkap di [MIGRASI_LARAGON.md](MIGRASI_LARAGON.md).

### Log tersimpan di mana?

```
storage/logs/laravel.log     ← umum
storage/logs/ocr/            ← OCR service
storage/logs/graph/          ← Neo4j sync
storage/logs/policy/         ← ReBAC policy
storage/logs/audit/          ← audit trail
storage/logs/workflow/       ← state machine
```

---

## 10. Checklist Kelengkapan Konfigurasi

Gunakan daftar berikut untuk memastikan semua komponen sudah dikonfigurasi sebelum menjalankan sistem di lingkungan baru.

### 10.1 File Environment (`.env`)

| Variabel                      | Wajib | Keterangan                                                      |
| ----------------------------- | ----- | --------------------------------------------------------------- |
| `APP_KEY`                     | ✅    | Generate via `php artisan key:generate`                         |
| `APP_URL`                     | ✅    | URL publik aplikasi, cth: `http://localhost:8000`               |
| `JWT_SECRET`                  | ✅    | Generate via `php artisan jwt:secret`                           |
| `DB_HOST`                     | ✅    | Host MySQL — `mysql` (Docker) atau `127.0.0.1` (lokal)          |
| `DB_DATABASE`                 | ✅    | Nama database MySQL                                             |
| `DB_USERNAME` / `DB_PASSWORD` | ✅    | Kredensial MySQL                                                |
| `NEO4J_HOST`                  | ✅    | Host Neo4j — `neo4j` (Docker) atau `localhost` (lokal)          |
| `NEO4J_PORT`                  | ✅    | Port Bolt Neo4j, default `7687`                                 |
| `NEO4J_USERNAME`              | ✅    | Default `neo4j`                                                 |
| `NEO4J_PASSWORD`              | ✅    | Harus sesuai `NEO4J_AUTH` di docker-compose                     |
| `NEO4J_DATABASE`              | ✅    | Default `neo4j` (nama database Neo4j)                           |
| `REDIS_HOST`                  | ✅    | `redis` (Docker) atau `127.0.0.1` (lokal)                       |
| `REDIS_PORT`                  | —     | Default `6379`                                                  |
| `OCR_SERVICE_URL`             | ✅    | `http://ocr-service:5001` (Docker) atau `http://localhost:5001` |
| `OCR_SECRET_KEY`              | ✅    | Kunci rahasia antara Laravel dan microservice OCR               |
| `QUEUE_CONNECTION`            | ✅    | `database` (production) atau `sync` (testing)                   |
| `CACHE_DRIVER`                | —     | Disarankan `redis`                                              |
| `SESSION_DRIVER`              | —     | Disarankan `redis`                                              |
| `MAIL_MAILER`                 | —     | Konfigurasi SMTP untuk notifikasi email                         |
| `FILESYSTEM_DISK`             | —     | `local` atau `s3` untuk penyimpanan dokumen                     |

### 10.2 MySQL

- [ ] Database dibuat (otomatis via Docker, atau manual: `CREATE DATABASE sipadu`)
- [ ] User `sipadu_app` dibuat (via `docker/mysql/init.sql`)
- [ ] Semua migrasi dijalankan: `php artisan migrate`
- [ ] Data awal di-seed: `php artisan db:seed`
- [ ] Tabel yang harus ada setelah migrasi:

```
users               institutions        cases
case_transitions    documents           ocr_results
ocr_jobs            integration_queue   graph_sync_log
access_logs         audit_logs          jobs
failed_jobs         password_reset_tokens
sessions            personal_access_tokens
```

### 10.3 Neo4j _(paling kritis — lihat bagian 11 untuk detail lengkap)_

- [ ] Neo4j berjalan dan dapat diakses di port `7474` (browser) dan `7687` (Bolt)
- [ ] Password `NEO4J_PASSWORD` di `.env` sesuai dengan `NEO4J_AUTH` di docker-compose
- [ ] Plugin APOC aktif (dibutuhkan untuk beberapa operasi graph)
- [ ] **Constraint unik** pada `mysql_id` dibuat untuk setiap node label
- [ ] **Index** dibuat untuk properti yang sering di-query
- [ ] Koneksi Bolt diverifikasi dari Laravel (`php artisan tinker`)
- [ ] Sinkronisasi awal dijalankan setelah seeding MySQL

### 10.4 Redis

- [ ] Redis berjalan di port `6379`
- [ ] Konfigurasi cache (`CACHE_DRIVER=redis`) dan session (`SESSION_DRIVER=redis`) di `.env`
- [ ] Koneksi Redis diverifikasi: `redis-cli ping` → `PONG`

### 10.5 OCR Microservice

- [ ] Container `ocr-service` berjalan di port `5001`
- [ ] `OCR_SECRET_KEY` sama antara `.env` Laravel dan environment container OCR
- [ ] Tesseract data bahasa Indonesia tersedia (`tesseract-ocr-ind`)
- [ ] Health check berhasil: `curl http://localhost:5001/health`

### 10.6 Queue Worker

- [ ] Worker aktif (via container `pa_disdukcapil_worker` atau `php artisan queue:work`)
- [ ] Tabel `jobs` dan `failed_jobs` ada di database
- [ ] Queue `ocr` dan `graph` diproses: cek `php artisan queue:monitor ocr,graph`

### 10.7 Storage & Permission

- [ ] `php artisan storage:link` sudah dijalankan
- [ ] Direktori `storage/` dan `bootstrap/cache/` dapat ditulis (permission 775)
- [ ] Direktori log khusus dibuat:

```bash
mkdir -p storage/logs/ocr storage/logs/graph storage/logs/policy \
         storage/logs/audit storage/logs/workflow
```

---

## 11. Konfigurasi Neo4j Lengkap

Neo4j adalah komponen **paling kritis** yang perlu dikonfigurasi secara manual setelah container pertama kali berjalan.

### 11.1 Verifikasi Koneksi

```bash
# Dari terminal host
curl -u neo4j:neo4jSecret1 http://localhost:7474/db/data/

# Dari dalam container
docker compose exec neo4j cypher-shell -u neo4j -p neo4jSecret1 "RETURN 'connected' AS status;"

# Dari Laravel (Tinker)
docker compose exec app php artisan tinker
>>> app(\App\Services\GraphService::class)->run('RETURN 1 AS ok');
```

### 11.2 Constraint & Index Wajib

Setelah Neo4j pertama kali berjalan, jalankan perintah Cypher berikut melalui Neo4j Browser (`http://localhost:7474`) atau `cypher-shell`:

```cypher
// ── Unique constraint ──────────────────────────────────────────────────────
// Memastikan tidak ada duplikasi node berdasarkan mysql_id

CREATE CONSTRAINT user_mysql_id IF NOT EXISTS
FOR (u:User) REQUIRE u.mysql_id IS UNIQUE;

CREATE CONSTRAINT institution_mysql_id IF NOT EXISTS
FOR (i:Institution) REQUIRE i.mysql_id IS UNIQUE;

CREATE CONSTRAINT case_mysql_id IF NOT EXISTS
FOR (c:Case) REQUIRE c.mysql_id IS UNIQUE;

CREATE CONSTRAINT document_mysql_id IF NOT EXISTS
FOR (d:Document) REQUIRE d.mysql_id IS UNIQUE;

// ── Index untuk query performa tinggi ──────────────────────────────────────
// Digunakan oleh ReBACService untuk traversal kebijakan akses

CREATE INDEX user_email IF NOT EXISTS
FOR (u:User) ON (u.email);

CREATE INDEX case_tracking_token IF NOT EXISTS
FOR (c:Case) ON (c.tracking_token);

CREATE INDEX case_status IF NOT EXISTS
FOR (c:Case) ON (c.status);

CREATE INDEX institution_type IF NOT EXISTS
FOR (i:Institution) ON (i.type);
```

Verifikasi constraint dan index:

```cypher
SHOW CONSTRAINTS;
SHOW INDEXES;
```

### 11.3 Skema Graf (Node & Relationship)

Sistem menggunakan skema graf berikut:

```
(:User)-[:WORKS_AT]->(:Institution)
(:User)-[:SUBMITTED]->(:Case)
(:User)-[:MANAGES]->(:Case)
(:Institution)-[:ISSUES]->(:Case)
(:Institution)-[:VERIFIES]->(:Case)
(:Case)-[:HAS]->(:Document)
(:User)-[:HAS_ROLE]->(:Role)
```

**Properti setiap node:**

| Node          | Properti Wajib                                        | Properti Opsional |
| ------------- | ----------------------------------------------------- | ----------------- |
| `User`        | `mysql_id`, `name`, `email`                           | `institution_id`  |
| `Institution` | `mysql_id`, `code`, `name`, `type`                    | –                 |
| `Case`        | `mysql_id`, `case_number`, `tracking_token`, `status` | –                 |
| `Document`    | `mysql_id`, `document_type`, `status`, `case_id`      | –                 |
| `Role`        | `name`                                                | –                 |

### 11.4 Inisialisasi Data Graf (Setelah Seeding MySQL)

Setelah `php artisan migrate --seed`, jalankan sinkronisasi awal untuk memuat data MySQL ke Neo4j:

```bash
# Via API (membutuhkan token super_admin)
curl -X POST http://localhost:8000/api/v1/sync/graph \
  -H "Authorization: Bearer <super_admin_token>" \
  -H "Content-Type: application/json"

# Atau via Artisan (jika ada command sync)
docker compose exec app php artisan tinker
>>> app(\App\Services\GraphService::class)->upsertUser([
...   'id' => 1, 'name' => 'Administrator',
...   'email' => 'admin@sipadu.go.id', 'institution_id' => 1
... ]);
```

Verifikasi data di Neo4j Browser:

```cypher
// Cek semua node
MATCH (n) RETURN labels(n) AS label, COUNT(n) AS jumlah;

// Cek relasi User → Institution
MATCH (u:User)-[r:WORKS_AT]->(i:Institution)
RETURN u.name, type(r), i.name LIMIT 10;

// Cek jalur ReBAC: apakah user bisa akses kasus tertentu?
MATCH path = (u:User {mysql_id: 1})-[*1..3]-(c:Case {mysql_id: 1})
RETURN path LIMIT 5;
```

### 11.5 Konfigurasi APOC (Plugin Neo4j)

Plugin APOC diaktifkan via `docker-compose.yml`. Verifikasi:

```cypher
RETURN apoc.version();
```

Jika gagal, cek variabel lingkungan di docker-compose:

```yaml
NEO4J_PLUGINS: '["apoc"]'
NEO4J_dbms_security_procedures_unrestricted: apoc.*
```

### 11.6 Konfigurasi di `.env` Laravel

```dotenv
# ── Neo4j ──────────────────────────────────────────────────────────────────
NEO4J_HOST=neo4j          # nama service Docker, atau 'localhost' jika lokal
NEO4J_PORT=7687           # port Bolt (bukan 7474)
NEO4J_USERNAME=neo4j
NEO4J_PASSWORD=neo4jSecret1   # WAJIB minimal 8 karakter!
NEO4J_DATABASE=neo4j          # nama database, default 'neo4j'
```

> ⚠ **Penting:** `NEO4J_PORT` harus `7687` (Bolt), bukan `7474` (HTTP browser).  
> Laravel menggunakan protokol Bolt untuk koneksi ke Neo4j, bukan HTTP.

> ⚠ **Penting:** Neo4j 5 mewajibkan password minimal **8 karakter**. Password seperti `secret` (6 karakter) akan menyebabkan container Neo4j gagal start dengan error `InvalidPasswordException: A password must be at least 8 characters`.

### 11.7 Konfigurasi di `config/neo4j.php`

File ini sudah dibuat di `d:\ProyekTA\config\neo4j.php`. Nilai penting yang bisa disesuaikan:

```php
// TTL cache kebijakan ReBAC (detik)
// Lebih tinggi = lebih cepat, tapi perubahan relasi butuh waktu lebih lama untuk direfresh
'policy_cache_ttl' => 300,  // 5 menit (default)
```

Untuk development, bisa dikurangi menjadi `60`. Untuk production disarankan `300–600`.

### 11.8 Pembersihan Cache ReBAC

Jika ada perubahan relasi di Neo4j (misalnya petugas berpindah institusi), cache ReBAC harus dibersihkan:

```bash
# Bersihkan semua cache ReBAC
docker compose exec app php artisan cache:forget rebac:*

# Atau bersihkan semua cache Redis
docker compose exec app php artisan cache:clear
```

### 11.9 Neo4j Lokal (Tanpa Docker)

Jika menjalankan Neo4j secara lokal (bukan Docker):

1. **Unduh Neo4j 5.x** dari [neo4j.com/download](https://neo4j.com/download/)
2. **Set password** melalui browser `http://localhost:7474` (login pertama: `neo4j`/`neo4j`, ganti password)
3. **Aktifkan APOC:**
   - Unduh `apoc-5.x.x-core.jar` dari [GitHub APOC Releases](https://github.com/neo4j/apoc/releases)
   - Letakkan di folder `plugins/` Neo4j
   - Tambahkan di `neo4j.conf`:
     ```
     dbms.security.procedures.unrestricted=apoc.*
     ```
4. **Restart Neo4j** dan jalankan constraint/index dari bagian 11.2

---

_Dokumen ini dibuat untuk keperluan Tugas Akhir SiPadu · 2024-2026_

---

## 14. Perancangan Sistem OCR

> Dokumentasi lengkap perancangan sistem OCR tersedia di **[OCR_DESIGN.md](OCR_DESIGN.md)**

### 14.1 Overview

Sistem OCR (Optical Character Recognition) di SiPadu menggunakan **Tesseract 5.x** dengan preprocessing **OpenCV** untuk mengekstrak data terstruktur dari dokumen KTP, KK, dan dokumen identitas lainnya.

**Teknologi:**

- **OCR Engine**: Tesseract 5.x (open-source)
- **Preprocessing**: OpenCV 4.9 (image enhancement)
- **Microservice**: Python 3.11 + Flask
- **Queue**: Laravel Queue (async processing)
- **Validation**: Auto-comparison dengan Levenshtein distance

### 14.2 Arsitektur dengan Validasi Otomatis

```
┌─────────────────────────────────────────────────────────────────────┐
│ 1. INPUT & UPLOAD                                                   │
├─────────────────────────────────────────────────────────────────────┤
│  PA Assistant / Pengaju Publik:                                     │
│  ├─ Isi form (NIK, nama, alamat, dll)                               │
│  └─ Upload dokumen (KTP, KK, Akta)                                  │
└─────────────────────────────────────────────────────────────────────┘
                           ↓ Event: DocumentUploaded
┌─────────────────────────────────────────────────────────────────────┐
│ 2. OCR AUTO-TRIGGER (Queue)                                         │
├─────────────────────────────────────────────────────────────────────┤
│  OCRJob dispatched → Queue Worker → OCRService                      │
│  Document.status: PENDING → PROCESSING                              │
└─────────────────────────────────────────────────────────────────────┘
                           ↓ HTTP Request
┌─────────────────────────────────────────────────────────────────────┐
│ 3. PYTHON OCR MICROSERVICE (localhost:5001)                         │
├─────────────────────────────────────────────────────────────────────┤
│  ├─ Preprocessing: grayscale, denoise, binarization, deskew         │
│  ├─ Tesseract 5.x: text extraction                                  │
│  ├─ Field detection: regex pattern matching (NIK, nama, dll)        │
│  └─ Confidence scoring: per field + overall                         │
└─────────────────────────────────────────────────────────────────────┘
                           ↓ JSON Response
┌─────────────────────────────────────────────────────────────────────┐
│ 4. AUTO-COMPARISON VALIDATION                                       │
├─────────────────────────────────────────────────────────────────────┤
│  OCRValidationService:                                              │
│  ├─ Fetch input data (Case/PublicSubmission)                        │
│  ├─ Compare with OCR result (field-by-field)                        │
│  ├─ Calculate similarity (Levenshtein distance)                     │
│  ├─ Generate comparison report (JSON)                               │
│  └─ Save to ocr_validations table                                   │
│                                                                      │
│  Validation Status:                                                 │
│  • MATCH (≥95% similarity)                                          │
│  • PARTIAL_MATCH (80-94%)                                           │
│  • MISMATCH (<80% or NIK ≠)                                         │
│  • MANUAL_REVIEW (60-79%)                                           │
└─────────────────────────────────────────────────────────────────────┘
                           ↓ Database Updated
┌─────────────────────────────────────────────────────────────────────┐
│ 5. DASHBOARD PA MANAGEMENT                                          │
├─────────────────────────────────────────────────────────────────────┤
│  Tampilan Review:                                                   │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │ 📊 Overall Match Score: 95%  ████████████████░░               │ │
│  ├───────────────────────────────────────────────────────────────┤ │
│  │ Field         │ Input Manual  │ Hasil OCR     │ Similarity   │ │
│  ├───────────────┼───────────────┼───────────────┼──────────────┤ │
│  │ NIK           │ 3174...001    │ 3174...001    │ ✅ 100%     │ │
│  │ Nama          │ AHMAD WARGA   │ AHMAD WARGA   │ ✅ 100%     │ │
│  │ Tanggal Lahir │ 01-01-1990    │ 01-01-1990    │ ✅ 100%     │ │
│  │ Alamat        │ JL SUDIRMAN.. │ JL SUDIRMAN.. │ ⚠️  92%      │ │
│  └───────────────┴───────────────┴───────────────┴──────────────┘ │
│                                                                      │
│  Actions:                                                           │
│  [✓ Approve & Continue] [✗ Reject] [📝 Request Correction]         │
└─────────────────────────────────────────────────────────────────────┘
                           ↓ Approved
┌─────────────────────────────────────────────────────────────────────┐
│ 6. WORKFLOW CONTINUES                                               │
├─────────────────────────────────────────────────────────────────────┤
│  Case.status: SUBMITTED → REVIEWED → ...                            │
│  Notification sent to petitioner (WhatsApp)                         │
└─────────────────────────────────────────────────────────────────────┘
```

### 14.3 Features

| Feature                 | Status     | Accuracy |
| ----------------------- | ---------- | -------- |
| **NIK Extraction**      | ✅ Working | 95%      |
| **Nama Extraction**     | ✅ Working | 88%      |
| **Tanggal Lahir**       | ✅ Working | 92%      |
| **Alamat**              | ✅ Working | 81%      |
| **No KK**               | ✅ Working | 90%      |
| **Preprocessing**       | ✅ Working | –        |
| **Confidence Scoring**  | ✅ Working | –        |
| **🆕 Auto-Validation**  | ✅ Working | –        |
| **🆕 Field Comparison** | ✅ Working | –        |

### 14.4 Instalasi Tesseract (Windows)

**Step 1: Download**

```
https://github.com/UB-Mannheim/tesseract/wiki
```

**Step 2: Install dengan Indonesian language data**

- ✅ Centang "Additional Language Data"
- ✅ Pilih: **Indonesian (ind)** + English (eng)

**Step 3: Set Environment Variable**

```powershell
$tesseractPath = "C:\Program Files\Tesseract-OCR"
[Environment]::SetEnvironmentVariable("Path",
    $env:Path + ";$tesseractPath", "Machine")
```

**Step 4: Update .env**

```env
OCR_SERVICE_URL=http://localhost:5001
OCR_SECRET_KEY=ocr_rahasia_sipadu_2026
TESSERACT_CMD=C:\Program Files\Tesseract-OCR\tesseract.exe
```

### 14.5 Menjalankan OCR Service

```bash
# Install dependencies
cd ocr-service
pip install -r requirements.txt

# Start service
python app.py
# Running on http://127.0.0.1:5001

# Health check
curl http://localhost:5001/health
```

### 14.6 Testing OCR

```bash
# Via API
curl -X POST http://localhost:8000/api/v1/ocr/process \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"document_id": 1}'

# Direct to microservice
curl -X POST http://localhost:5001/ocr/process \
  -H "X-OCR-Secret: ocr_rahasia_sipadu_2026" \
  -F "file=@ktp_sample.jpg"
```

### 14.7 Validasi Otomatis OCR vs Input Manual

> **🆕 Fitur Baru (11 Maret 2026)**: Sistem otomatis membandingkan data input manual dengan hasil OCR

**Flow Otomatis:**

```
Upload Dokumen → OCR Auto-Trigger → Field Extraction →
Auto-Comparison → Dashboard PA Management (Approve/Reject)
```

**Perbandingan Data:**

- **NIK** – Harus 100% match (critical field)
- **Nama** – Threshold 90% similarity
- **Alamat** – Threshold 85% similarity
- **Tanggal Lahir** – Format normalization + match
- **Tempat Lahir, No KK, RT/RW** – String comparison

**Dashboard PA Management:**

- Lihat perbandingan field-by-field
- Overall match score (0-100%)
- Visual indicators (✅ Match / ⚠️ Mismatch)
- Actions: Approve / Reject / Request Correction

**Status Validasi:**

- `MATCH` – ≥95% similarity, semua field cocok
- `PARTIAL_MATCH` – 80-94% similarity, beberapa field berbeda
- `MISMATCH` – <80% similarity atau NIK tidak match
- `MANUAL_REVIEW` – 60-79% similarity, memerlukan review manual

**Auto-Trigger:**
Setiap dokumen KTP/KK/Akta yang diupload (dari PA Assistant atau Pengaju Publik) akan **otomatis diproses OCR** dan hasilnya langsung dibandingkan dengan data input.

**URL Dashboard:**

```
/dashboard/review/cases/{id}  → Lihat validasi OCR
POST /dashboard/review/cases/{id}/validate → Approve/Reject
```

**Dokumentasi Lengkap:** [OCR_VALIDATION_DESIGN.md](OCR_VALIDATION_DESIGN.md)

### 14.8 Monitoring

**Queue Status:**

```bash
php artisan queue:monitor ocr
```

**Failed Jobs:**

```bash
php artisan queue:failed --queue=ocr
php artisan queue:retry all
```

**Logs:**

```bash
tail -f storage/logs/ocr/ocr-service.log
tail -f storage/logs/laravel.log
```

### 14.9 Dokumentasi Lengkap

Untuk detail lengkap tentang:

- Preprocessing pipeline (grayscale, denoise, binarization, deskew)
- Field extraction algorithms
- Confidence scoring
- Error handling & retry logic
- Performance optimization
- Troubleshooting
- **Validasi Otomatis & Comparison Algorithm**

**Lihat:**

- **[OCR_DESIGN.md](OCR_DESIGN.md)** – Dokumentasi OCR Engine (70+ halaman)
- **[OCR_VALIDATION_DESIGN.md](OCR_VALIDATION_DESIGN.md)** – Sistem Validasi Otomatis (30+ halaman)

---

_Dokumen ini dibuat untuk keperluan Tugas Akhir SiPadu · 2024-2026_

---

## 12. Riwayat Perbaikan Konfigurasi

### 28 Februari 2026 – Pembersihan Mockup HTML & File Redundan

#### A. Penghapusan Mockup HTML (8 files)

Menghapus semua file HTML statis di root folder yang hanya berfungsi sebagai mockup/prototype UI saat fase awal development:

| #   | File                       | Keterangan                                                                           |
| --- | -------------------------- | ------------------------------------------------------------------------------------ |
| 1   | `superadmin.html`          | Mockup dashboard super admin → Diganti `resources/views/dashboard/admin/*.blade.php` |
| 2   | `admin-disdukcapil.html`   | Mockup dashboard Disdukcapil → Diganti Laravel Blade dengan role guard               |
| 3   | `admin-pa-assistant.html`  | Mockup dashboard PA Assistant → Diganti Laravel Blade                                |
| 4   | `admin-pa-management.html` | Mockup dashboard PA Management → Diganti Laravel Blade                               |
| 5   | `admin-pa-staff.html`      | Mockup dashboard PA Staff → Diganti Laravel Blade                                    |
| 6   | `dashboard.html`           | Mockup dashboard umum → Diganti `resources/views/dashboard/index.blade.php`          |
| 7   | `index.html`               | Mockup landing page → Diganti Laravel route + Blade                                  |
| 8   | `tentang.html`             | Mockup halaman tentang → Diganti Laravel route + Blade                               |

#### B. Penghapusan File Dokumentasi Redundan & Temporary (10 files + 2 folders)

| #   | File/Folder                        | Alasan Penghapusan                                                         |
| --- | ---------------------------------- | -------------------------------------------------------------------------- |
| 1   | `QUICKSTART.txt`                   | Redundan dengan `PANDUAN.md` yang lebih lengkap                            |
| 2   | `FINAL_STATUS_REPORT.md`           | Status report temporary dari debugging session                             |
| 3   | `OPTIMIZATION.md`                  | Konten sudah tercakup dalam `PANDUAN.md`                                   |
| 4   | `OPTIMIZATION_DEPLOYMENT_GUIDE.md` | Redundan dengan section deployment di `PANDUAN.md`                         |
| 5   | `OPTIMIZATION_SUMMARY.md`          | Summary temporary yang tidak diperlukan lagi                               |
| 6   | `PERFORMANCE_STATUS.md`            | Status monitoring temporary                                                |
| 7   | `QUERYOPTIMIZATION_GUIDE.md`       | Query optimization sudah tercakup di dokumentasi utama                     |
| 8   | `optimize.ps1`                     | Script optimasi, fungsinya sudah tersedia via `php artisan` commands       |
| 9   | `optimize.sh`                      | Script optimasi Linux, fungsinya sudah tersedia via `php artisan` commands |
| 10  | `.env.optimization`                | Environment file khusus yang tidak perlu                                   |
| 11  | `.zencoder/`                       | Cache IDE/tool, tidak diperlukan di version control                        |
| 12  | `.zenflow/`                        | Cache IDE/tool, tidak diperlukan di version control                        |

**Total file dihapus: 20 items (8 HTML + 12 redundant/temporary files)**

**Alasan pembersihan:**

- Sistem sudah 100% menggunakan Laravel Blade dengan backend terintegrasi
- Mockup HTML tidak terhubung ke database dan bisa membingungkan developer baru
- Dokumentasi redundan membuat repository cluttered dan sulit dipelihara
- Memperkecil ukuran repositori dan deployment package (dari ~200MB ke ~150MB)
- Scripts optimasi sudah digantikan dengan Artisan commands standard Laravel
- Menghindari kebingungan antara file mockup vs sistem yang aktif

**Struktur folder setelah pembersihan:**

```
d:\ProyekTA\
├── .env, .env.example, .env.production   (konfigurasi)
├── composer.json, composer.lock          (dependencies)
├── docker-compose.yml                    (Docker setup)
├── artisan                               (Laravel CLI)
├── PANDUAN.md                            (dokumentasi utama - LENGKAP)
├── app/                                  (aplikasi Laravel)
├── bootstrap/, config/, database/        (Laravel core)
├── docker/                               (Docker config)
├── ocr-service/                          (Python microservice)
├── public/, resources/, routes/          (Laravel structure)
├── storage/, vendor/                     (runtime & dependencies)
```

**Commands untuk optimasi (menggantikan optimize.ps1/sh):**

```bash
# Docker
docker compose exec app php artisan optimize
docker compose exec app php artisan view:cache
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache

# Lokal
php artisan optimize
php artisan view:cache
php artisan config:cache
php artisan route:cache
```

**Sistem aktif sekarang:**

```
http://localhost:8000/               → resources/views/welcome-new.blade.php (landing page)
http://localhost:8000/dashboard      → resources/views/dashboard/index.blade.php (role-based)
http://localhost:8000/dashboard/admin/users → resources/views/dashboard/admin/users.blade.php
http://localhost:8000/pengajuan      → resources/views/pengajuan/publik.blade.php
```

---

### 22 Februari 2026 – Audit & Perbaikan Menyeluruh

Pemeriksaan penuh terhadap semua konfigurasi mengidentifikasi dan memperbaiki **10 masalah** berikut:

| #   | File                                  | Masalah                                                                                       | Perbaikan                                                      |
| --- | ------------------------------------- | --------------------------------------------------------------------------------------------- | -------------------------------------------------------------- |
| 1   | `.env`                                | Password Neo4j `secret` hanya 6 karakter (minimal 8)                                          | Diubah ke `neo4jSecret1`                                       |
| 2   | `.env`                                | Banyak variabel penting tidak ada (JWT, NEO4J_HOST, REDIS_HOST, dll)                          | Ditambahkan semua variabel dari `.env.example`                 |
| 3   | `docker-compose.yml`                  | `version: "3.9"` sudah obsolete dan menyebabkan warning                                       | Field `version` dihapus                                        |
| 4   | `docker-compose.yml`                  | Healthcheck Neo4j pakai password hardcoded `password123`                                      | Diganti `${NEO4J_PASSWORD:-neo4jSecret1}`                      |
| 5   | `docker-compose.yml`                  | `NEO4J_AUTH` default `password123` tidak konsisten dengan `.env`                              | Default diubah ke `neo4jSecret1`                               |
| 6   | `docker/mysql/init.sql`               | Referensi database `sipadu` (salah nama)                                                      | Diganti `pa_disdukcapil`                                       |
| 7   | `docker/nginx/nginx.conf`             | `limit_req_zone` di dalam blok `server` (harus di konteks `http`)                             | Dipindah ke atas, sebelum blok `server`                        |
| 8   | `database/migrations/`                | `create_cache_table` berjalan setelah `create_permission_tables` (menyebabkan error FK cache) | Rename timestamp menjadi `173900` dan `173901`                 |
| 9   | `composer.json`                       | Package `php-open-source-saver/jwt-auth` tidak ada                                            | Di-install via `composer require`                              |
| 10  | `config/auth.php`                     | Tidak ada file ini, guard `api` JWT tidak terdefinisi                                         | Publish via `artisan config:publish auth` + tambah guard `api` |
| 11  | `app/Http/Controllers/Controller.php` | File tidak ada (Laravel 11 tidak membuatnya otomatis)                                         | Dibuat manual                                                  |
| 12  | `public/index.php`                    | Direktori `public/` tidak ada sama sekali                                                     | Dibuat dengan isi standard Laravel 11                          |

**Status akhir setelah perbaikan (22 Feb 2026):**

| Container               | Status          | Port            |
| ----------------------- | --------------- | --------------- |
| `pa_disdukcapil_nginx`  | ✅ Up           | 8000, 8443      |
| `pa_disdukcapil_app`    | ✅ Up           | 9000 (internal) |
| `pa_disdukcapil_worker` | ✅ Up           | –               |
| `pa_disdukcapil_mysql`  | ✅ Up (healthy) | 3306            |
| `pa_disdukcapil_redis`  | ✅ Up (healthy) | 6379            |
| `pa_disdukcapil_neo4j`  | ✅ Up (healthy) | 7474, 7687      |
| `pa_disdukcapil_ocr`    | ✅ Up           | 5001            |

**Verifikasi API:** `POST /api/v1/auth/login` → HTTP 200, token JWT berhasil diterima.

---

## 13. Fitur Pengajuan Publik (Tanpa Akun)

> Ditambahkan: 23 Februari 2026

Fitur ini memungkinkan **warga / mantan pasangan** mengajukan pembaruan dokumen pasca perceraian **tanpa perlu membuat akun atau password**.

### 13.1 Alur Pengajuan Publik

```
Warga buka /pengajuan
    ↓
Isi form (NIK, nama, nomor WA, data perceraian)
    ↓ [validasi rate-limit: maks 3× / 15 hari per NIK]
Upload dokumen (KTP wajib, lainnya opsional)
    ↓
Sistem membuat PublicSubmission + token PUB-XXXXX
    ↓
Token dikirim ke nomor WhatsApp pemohon
    ↓
Warga bisha lacak status di /lacak/{token} atau /tracking
    ↓
Petugas PA/Disdukcapil proses di dashboard → /dashboard/public-inbox
    ↓ [jika disetujui]
Kasus resmi (Case) dibuat otomatis, notifikasi WA dikirim ke warga
```

### 13.2 Halaman Publik

| URL                             | Keterangan                                 |
| ------------------------------- | ------------------------------------------ |
| `GET /pengajuan`                | Form pengajuan publik (tanpa login)        |
| `POST /pengajuan`               | Proses simpan pengajuan + kirim WA         |
| `POST /pengajuan/cek-nik`       | AJAX cek kuota NIK                         |
| `GET /pengajuan/sukses/{token}` | Halaman konfirmasi sukses                  |
| `GET /lacak/{token}`            | Halaman lacak status via token WA          |
| `GET /tracking`                 | Halaman lacak umum (form isi token manual) |

### 13.3 Endpoint API Publik

```
POST /api/v1/public/submissions           ← buat pengajuan (multipart/form-data)
POST /api/v1/public/submissions/check-nik ← cek kuota NIK (JSON)
GET  /api/v1/public/submissions/{token}   ← tracking via API
GET  /api/v1/tracking/{token}             ← tracking umum (kasus resmi + publik)
```

### 13.4 Rate-Limit per NIK

- Satu NIK hanya dapat mengajukan **3 kali** dalam **15 hari** terakhir.
- Pengajuan dengan status `REJECTED` tidak dihitung terhadap kuota.
- Saat kuota habis, form menampilkan tanggal kapan warga bisa mengajukan lagi.
- Nilai default dapat diubah via `.env`:

```dotenv
PUBLIC_SUBMISSION_MAX=3
PUBLIC_SUBMISSION_LIMIT_DAYS=15
PUBLIC_SUBMISSION_MAX_FILE_MB=5
PUBLIC_SUBMISSION_WA=true
```

### 13.5 Konfigurasi WhatsApp Gateway

Sistem mendukung tiga driver WA yang dikonfigurasi via `.env`:

| Driver   | Keterangan                                         |
| -------- | -------------------------------------------------- |
| `log`    | Pesan hanya ditulis ke `laravel.log` (default dev) |
| `fonnte` | Gateway [Fonnte](https://fonnte.com)               |
| `wablas` | Gateway [Wablas](https://wablas.com)               |

```dotenv
# Driver WA (log | fonnte | wablas)
WA_DRIVER=log

# Fonnte
WA_FONNTE_TOKEN=your_fonnte_token_here

# Wablas
WA_WABLAS_TOKEN=your_wablas_token_here
WA_WABLAS_DOMAIN=solo.wablas.com

# Nama pengirim
WA_SENDER_NAME=SiPadu – PA & Disdukcapil
WA_TIMEOUT=15
```

> ⚠️ Untuk production, gunakan driver `fonnte` atau `wablas` dan pastikan token valid.
> Untuk testing/development, gunakan `WA_DRIVER=log` — pesan ditulis ke `storage/logs/laravel.log`.

### 13.6 Dashboard Petugas: Kotak Masuk Pengajuan Publik

Petugas dengan role `pa_assistant`, `pa_management`, `disdukcapil_staff`, atau `super_admin` dapat memproses pengajuan melalui:

```
/dashboard/public-inbox                ← daftar semua pengajuan publik
/dashboard/public-inbox/{id}           ← detail + aksi proses
```

**Aksi yang tersedia:**

- **Mulai Tinjau** → ubah status `PENDING` → `REVIEWING`
- **Setujui & Buat Kasus** → buat `Case` resmi + notifikasi WA ke pemohon
- **Tolak** → ubah status ke `REJECTED` + notifikasi WA alasan penolakan
- **Kirim Ulang WA** → kirim ulang token tracking ke nomor WA pemohon

### 13.7 Struktur File Baru

```
app/
  Models/
    PublicSubmission.php              ← model + rate-limit logic + statusLabel()
    PublicSubmissionDocument.php      ← model dokumen yang diupload
  Services/
    WhatsAppService.php               ← abstraksi gateway WA (fonnte/wablas/log)
    PublicSubmissionService.php       ← orkestrasi: create, validateNik, sendWA
  Http/Controllers/
    Web/PublicSubmissionController.php       ← halaman publik (form + sukses)
    Web/PublicSubmissionStaffController.php  ← dashboard petugas
    API/PublicSubmissionController.php       ← API endpoint publik

config/
  whatsapp.php         ← konfigurasi driver WA
  public_submission.php ← batas rate-limit, ukuran file

database/migrations/
  2026_02_23_000001_create_public_submissions_table.php

resources/views/
  pengajuan/
    publik.blade.php   ← form pengajuan publik
    sukses.blade.php   ← halaman konfirmasi sukses
  dashboard/
    public-inbox/
      index.blade.php  ← daftar pengajuan (staff)
      show.blade.php   ← detail + aksi proses (staff)
  tracking/
    public.blade.php   ← diperbarui: mendukung PUB- dan TRK- token
```

### 13.8 Menjalankan Migrasi Baru

```bash
# Docker
docker compose exec app php artisan migrate

# Lokal
php artisan migrate
```

### 13.9 Pengujian Pengajuan Publik via cURL

```bash
# 1. Cek kuota NIK
curl -X POST http://localhost:8000/api/v1/public/submissions/check-nik \
  -H "Content-Type: application/json" \
  -d '{"nik":"3174010101900001"}'
# Expected: {"allowed":true,"remaining":3,"max":3,"days":15}

# 2. Buat pengajuan (multipart/form-data)
curl -X POST http://localhost:8000/api/v1/public/submissions \
  -F "nik=3174010101900001" \
  -F "petitioner_name=Ahmad Warga" \
  -F "phone_wa=081234567890" \
  -F "respondent_name=Siti Warga" \
  -F "divorce_date=2025-06-01" \
  -F "verdict_number=0123/Pdt.G/2025/PA.JS" \
  -F "documents[KTP]=@/path/to/ktp.jpg"
# Expected: {"message":"Pengajuan berhasil diterima.","tracking_token":"PUB-XXXXX",...}

# 3. Lacak status
curl http://localhost:8000/api/v1/tracking/PUB-XXXXX
# Expected: {"type":"public_submission","status":"PENDING",...}
```
