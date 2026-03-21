# Panduan Migrasi dari Docker ke Laragon

> **SiPadu – Sistem Pembaruan Dokumen Pasca Perceraian**  
> Panduan migrasi environment development dari Docker ke Laragon

---

## Daftar Isi

1. [Prasyarat](#1-prasyarat)
2. [Instalasi Laragon](#2-instalasi-laragon)
3. [Setup Komponen](#3-setup-komponen)
4. [Migrasi Aplikasi](#4-migrasi-aplikasi)
5. [Konfigurasi Neo4j](#5-konfigurasi-neo4j)
6. [Setup OCR Service](#6-setup-ocr-service)
7. [Setup Queue Worker](#7-setup-queue-worker)
8. [Testing & Verifikasi](#8-testing--verifikasi)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Prasyarat

### Hardware Minimum
- RAM: 8 GB (disarankan 16 GB)
- Storage: 10 GB free space
- CPU: Dual-core (disarankan Quad-core)

### Software yang Akan Diinstall
| Software | Versi | Keterangan |
|----------|-------|------------|
| Laragon | 6.0+ | Development environment |
| PHP | 8.1+ | Sudah include di Laragon |
| MySQL | 8.0+ | Sudah include di Laragon |
| Redis | 7.0+ | Download terpisah |
| Neo4j Desktop | 1.5.x | Download dari neo4j.com |
| Python | 3.11+ | Untuk OCR microservice |
| Tesseract OCR | 5.x | OCR engine |

---

## 2. Instalasi Laragon

### Langkah 1 – Download & Install Laragon

1. **Download Laragon Full:**
   - Kunjungi: https://laragon.org/download/
   - Pilih **Laragon Full** (bukan Lite) – sudah include PHP 8.1, MySQL, Redis
   - Size: ~300 MB

2. **Install Laragon:**
   ```
   - Jalankan installer
   - Pilih lokasi install (default: C:\laragon)
   - Checklist semua komponen: PHP, Apache, MySQL, Redis
   - Finish instalasi
   ```

3. **Konfigurasi Awal:**
   - Buka Laragon
   - Klik kanan icon di system tray → **Preferences**
   - General tab:
     - ✅ Auto start Laragon
     - ✅ Run Laragon in background
   - Services & Ports tab:
     - Apache: 80
     - MySQL: 3306
     - Redis: 6379

### Langkah 2 – Verifikasi Instalasi

Jalankan Laragon dan klik **Start All**. Verifikasi:

```powershell
# Cek PHP
php -v
# Expected: PHP 8.1.x atau 8.2.x

# Cek MySQL
mysql --version
# Expected: mysql Ver 8.0.x

# Cek Composer
composer --version
# Expected: Composer version 2.x

# Cek Redis (di Laragon terminal)
redis-cli ping
# Expected: PONG
```

---

## 3. Setup Komponen

### A. PHP Extensions

Laragon Full sudah include extensions, tapi perlu aktifkan beberapa:

1. **Buka `php.ini`:**
   - Laragon → Menu → PHP → php.ini

2. **Uncomment extensions berikut** (hapus `;` di depan):
   ```ini
   extension=curl
   extension=fileinfo
   extension=gd
   extension=mbstring
   extension=openssl
   extension=pdo_mysql
   extension=redis
   extension=zip
   extension=intl
   ```

3. **Save dan restart Apache:**
   - Laragon → Stop → Start All

### B. MySQL

MySQL sudah running via Laragon. Setup user dan database:

1. **Akses MySQL via HeidiSQL** (include di Laragon):
   - Laragon → Menu → MySQL → HeidiSQL
   - Connection: (default, tanpa password)

2. **Buat database:**
   ```sql
   CREATE DATABASE pa_disdukcapil CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Buat user aplikasi:**
   ```sql
   CREATE USER 'sipadu_app'@'localhost' IDENTIFIED BY 'Passw0rd!SiPadu';
   GRANT ALL PRIVILEGES ON pa_disdukcapil.* TO 'sipadu_app'@'localhost';
   FLUSH PRIVILEGES;
   ```

### C. Redis

Redis sudah running via Laragon. Test koneksi:

```powershell
# Di Laragon terminal
redis-cli
> ping
# Expected: PONG
> exit
```

---

## 4. Migrasi Aplikasi

### Langkah 1 – Stop Docker (jika masih running)

```powershell
cd D:\ProyekTA
docker compose down
```

### Langkah 2 – Copy Project ke Laragon

```powershell
# Option 1: Copy folder ProyekTA ke Laragon www
xcopy D:\ProyekTA C:\laragon\www\sipadu /E /I /H /Y

# Option 2: Tetap di D:\ProyekTA, tambahkan Virtual Host di Laragon
# (Rekomendasi: gunakan Option 2 agar tidak perlu copy ulang)
```

**Cara Setup Virtual Host (Option 2 - Direkomendasikan):**

1. Laragon → Menu → Apache → sites-enabled → Add Virtual Host
2. Isi:
   ```apache
   <VirtualHost *:80>
       DocumentRoot "D:/ProyekTA/public"
       ServerName sipadu.test
       <Directory "D:/ProyekTA/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
3. Add entry di `C:\Windows\System32\drivers\etc\hosts`:
   ```
   127.0.0.1    sipadu.test
   ```
4. Laragon → Reload Apache

### Langkah 3 – Update File `.env`

Edit `D:\ProyekTA\.env`:

```dotenv
APP_NAME="PA-Disdukcapil System"
APP_ENV=local
APP_KEY=base64:GYeFh1ihyIi4xZRWZqiIheK2YqCIDHKI3FFusi92wSw=
APP_DEBUG=true
APP_URL=http://sipadu.test

# ─── MySQL (Laragon) ──────────────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pa_disdukcapil
DB_USERNAME=sipadu_app
DB_PASSWORD=Passw0rd!SiPadu

# ─── Neo4j (Desktop) ──────────────────────────────────────────────────────────
NEO4J_HOST=localhost
NEO4J_PORT=7687
NEO4J_USERNAME=neo4j
NEO4J_PASSWORD=neo4jSecret1
NEO4J_DATABASE=neo4j

# ─── Queue ────────────────────────────────────────────────────────────────────
QUEUE_CONNECTION=database
QUEUE_RETRY_AFTER=90
QUEUE_MAX_TRIES=3

# ─── Cache / Session ──────────────────────────────────────────────────────────
CACHE_STORE=redis
CACHE_DRIVER=redis
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

# ─── Redis (Laragon) ──────────────────────────────────────────────────────────
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ─── Mail ─────────────────────────────────────────────────────────────────────
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@pa-disdukcapil.go.id"
MAIL_FROM_NAME="${APP_NAME}"

# ─── JWT ──────────────────────────────────────────────────────────────────────
JWT_SECRET=EzWNMIqAb1X2TqY1aQq05XOyqDuCQg0M9fo3hipmj50lEOyFPG72KditT1ynpZ2y

# ─── OCR Service ──────────────────────────────────────────────────────────────
OCR_SERVICE_URL=http://127.0.0.1:5001
OCR_SECRET_KEY=ocr_rahasia_ganti_ini

# ─── WhatsApp Gateway ─────────────────────────────────────────────────────────
WA_DRIVER=log
WA_SENDER_NAME=SiPadu – PA & Disdukcapil
```

### Langkah 4 – Install Dependencies & Setup

```powershell
# Pindah ke folder project
cd D:\ProyekTA

# Install PHP dependencies
composer install

# Generate key jika belum
php artisan key:generate

# Generate JWT secret jika belum
php artisan jwt:secret

# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Setup storage link
php artisan storage:link

# Jalankan migrasi
php artisan migrate --seed
```

### Langkah 5 – Fix Permissions

```powershell
# Pastikan folder storage dan bootstrap/cache writable
icacls D:\ProyekTA\storage /grant Everyone:(OI)(CI)F /T
icacls D:\ProyekTA\bootstrap\cache /grant Everyone:(OI)(CI)F /T
```

---

## 5. Konfigurasi Neo4j

### Langkah 1 – Install Neo4j Desktop

1. **Download Neo4j Desktop:**
   - https://neo4j.com/download/
   - Pilih **Neo4j Desktop** (bukan Neo4j Server)
   - Daftar akun (gratis) untuk mendapatkan activation key

2. **Install dan Activate:**
   - Jalankan installer
   - Paste activation key saat diminta

### Langkah 2 – Create Database

1. **Buka Neo4j Desktop**
2. **Create New Project:**
   - New Project → "SiPadu"
3. **Add Database:**
   - Add → Local DBMS
   - Name: `sipadu-dev`
   - Password: `neo4jSecret1` (minimal 8 karakter!)
   - Version: 5.15 atau terbaru
4. **Install APOC Plugin:**
   - Klik database → Plugins tab
   - Install **APOC** (Awesome Procedures on Cypher)
5. **Start Database:**
   - Klik **Start**

### Langkah 3 – Verifikasi Koneksi

```powershell
# Test koneksi dari Laravel
cd D:\ProyekTA
php artisan tinker
>>> app(\App\Services\GraphService::class)->run('RETURN 1 AS ok');
# Expected: hasil query
```

### Langkah 4 – Setup Constraints & Indexes

Buka Neo4j Browser (http://localhost:7474) dan jalankan:

```cypher
// Unique constraints
CREATE CONSTRAINT user_mysql_id IF NOT EXISTS
FOR (u:User) REQUIRE u.mysql_id IS UNIQUE;

CREATE CONSTRAINT institution_mysql_id IF NOT EXISTS
FOR (i:Institution) REQUIRE i.mysql_id IS UNIQUE;

CREATE CONSTRAINT case_mysql_id IF NOT EXISTS
FOR (c:Case) REQUIRE c.mysql_id IS UNIQUE;

CREATE CONSTRAINT document_mysql_id IF NOT EXISTS
FOR (d:Document) REQUIRE d.mysql_id IS UNIQUE;

// Indexes
CREATE INDEX user_email IF NOT EXISTS
FOR (u:User) ON (u.email);

CREATE INDEX case_tracking_token IF NOT EXISTS
FOR (c:Case) ON (c.tracking_token);

CREATE INDEX case_status IF NOT EXISTS
FOR (c:Case) ON (c.status);

CREATE INDEX institution_type IF NOT EXISTS
FOR (i:Institution) ON (i.type);
```

---

## 6. Setup OCR Service

### Langkah 1 – Install Python

1. **Download Python 3.11:**
   - https://www.python.org/downloads/
   - Pilih "Windows installer (64-bit)"

2. **Install:**
   - ✅ Add Python to PATH
   - Install Now
   - Verifikasi: `python --version`

### Langkah 2 – Install Tesseract OCR

1. **Download Tesseract:**
   - https://github.com/UB-Mannheim/tesseract/wiki
   - Pilih `tesseract-ocr-w64-setup-5.x.x.exe`

2. **Install:**
   - Default location: `C:\Program Files\Tesseract-OCR`
   - ✅ Install additional language data: Indonesian (ind)

3. **Add to PATH:**
   ```powershell
   # Tambahkan ke System Environment Variables:
   PATH = C:\Program Files\Tesseract-OCR
   ```

### Langkah 3 – Setup OCR Microservice

```powershell
# Pindah ke folder OCR service
cd D:\ProyekTA\ocr-service

# Create virtual environment
python -m venv venv

# Activate virtual environment
.\venv\Scripts\Activate.ps1

# Install dependencies
pip install -r requirements.txt

# Test OCR service
python app.py
# Expected: Running on http://127.0.0.1:5001
```

### Langkah 4 – Setup OCR Service as Background Service

Buat file `start-ocr.bat` di `D:\ProyekTA\`:

```batch
@echo off
cd /d D:\ProyekTA\ocr-service
call venv\Scripts\activate.bat
start /B python app.py
```

**Auto-start dengan Laragon:**
- Laragon → Menu → Tools → Quick app → Add `start-ocr.bat`

---

## 7. Setup Queue Worker

### Langkah 1 – Test Queue Worker

```powershell
cd D:\ProyekTA
php artisan queue:work --queue=ocr,graph,default --tries=3 --timeout=120
```

### Langkah 2 – Setup as Background Service

Buat file `start-queue.bat` di `D:\ProyekTA\`:

```batch
@echo off
cd /d D:\ProyekTA
start /B php artisan queue:work --queue=ocr,graph,default --tries=3 --timeout=120
```

**Auto-start dengan Laragon:**
- Laragon → Menu → Tools → Quick app → Add `start-queue.bat`

**Alternatif: Gunakan Windows Task Scheduler**

1. Open Task Scheduler
2. Create Basic Task:
   - Name: "SiPadu Queue Worker"
   - Trigger: At startup
   - Action: Start a program
     - Program: `C:\laragon\bin\php\php-8.1.x\php.exe`
     - Arguments: `artisan queue:work --queue=ocr,graph,default --tries=3 --timeout=120`
     - Start in: `D:\ProyekTA`

---

## 8. Testing & Verifikasi

### Checklist Verifikasi

- [ ] **Apache running:** http://sipadu.test menampilkan landing page
- [ ] **MySQL:** HeidiSQL bisa connect ke `pa_disdukcapil`
- [ ] **Redis:** `redis-cli ping` → PONG
- [ ] **Neo4j:** Browser http://localhost:7474 bisa login
- [ ] **OCR Service:** http://127.0.0.1:5001/health → `{"status":"ok"}`
- [ ] **Queue Worker:** `php artisan queue:work` berjalan tanpa error

### Test Login

1. **Buka browser:** http://sipadu.test/auth/login
2. **Login credentials:**
   - Email: `asisten@pa-painan.go.id`
   - Password: `Pass@12345`
3. **Expected:** Redirect ke dashboard PA Assistant

### Test API

```powershell
# Test login via API
curl -X POST http://sipadu.test/api/v1/auth/login `
  -H "Content-Type: application/json" `
  -d '{"email":"admin@sipadu.go.id","password":"Admin@123456"}'

# Expected: JSON response dengan access_token
```

### Cek Logs

```powershell
# Laravel log
Get-Content D:\ProyekTA\storage\logs\laravel.log -Tail 50 -Wait

# Queue worker log (jika running)
# Lihat output terminal queue:work
```

---

## 9. Troubleshooting

### Error: "Class not found"

```powershell
composer dump-autoload
php artisan clear-compiled
php artisan config:clear
```

### Error: MySQL connection refused

1. Cek MySQL running di Laragon:
   - Laragon → MySQL → Start MySQL
2. Cek credentials di `.env` match dengan MySQL user
3. Verifikasi dengan HeidiSQL

### Error: Redis connection refused

1. Cek Redis running:
   ```powershell
   redis-cli ping
   ```
2. Jika tidak ada, cek Laragon → Preferences → Services → Redis (enable)

### Error: Neo4j connection timeout

1. Cek Neo4j Desktop database status (harus **Started**)
2. Verifikasi password di `.env` match dengan database password
3. Test manual:
   ```powershell
   # Install neo4j driver jika belum
   composer require laudis/neo4j-php-client
   
   # Test di tinker
   php artisan tinker
   >>> app(\App\Services\GraphService::class)->run('RETURN 1');
   ```

### Error: OCR service tidak bisa diakses

1. Cek Python service running:
   ```powershell
   curl http://127.0.0.1:5001/health
   ```
2. Jika tidak running, start manual:
   ```powershell
   cd D:\ProyekTA\ocr-service
   .\venv\Scripts\Activate.ps1
   python app.py
   ```

### Error: "Permission denied" saat upload file

```powershell
# Fix folder permissions
icacls D:\ProyekTA\storage /grant Everyone:(OI)(CI)F /T
icacls D:\ProyekTA\public /grant Everyone:(OI)(CI)F /T
```

### Error: 419 CSRF Token Mismatch

1. **Clear browser cache completely**
2. **Test di Incognito/Private mode**
3. **Disable browser extensions** (especially security/privacy extensions)
4. **Check session files:**
   ```powershell
   dir D:\ProyekTA\storage\framework\sessions
   # Should have session files
   ```
5. **Verify `.env` settings:**
   ```dotenv
   SESSION_DRIVER=file
   SESSION_SECURE_COOKIE=false
   SESSION_SAME_SITE=lax
   ```
6. **Clear all Laravel caches:**
   ```powershell
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   php artisan route:clear
   ```

---

## Perbandingan Docker vs Laragon

| Aspek | Docker | Laragon |
|-------|--------|---------|
| **Setup** | Complex (docker-compose) | Simple (1-click install) |
| **Performance** | Slower (virtualization) | Native (faster) |
| **Resource** | Heavy (1-2 GB RAM) | Light (500 MB RAM) |
| **Networking** | Container network | Localhost |
| **Debugging** | Harder (container logs) | Easier (native logs) |
| **Production Similarity** | High (identical env) | Medium (dev only) |
| **Windows Integration** | Medium | Excellent |

**Rekomendasi:**
- **Development:** Laragon (lebih cepat, mudah debug)
- **Production:** Docker (consistent environment)
- **Team:** Docker (same env untuk semua developer)

---

## Script Maintenance Laragon

### start-sipadu.bat

```batch
@echo off
echo Starting SiPadu Development Environment...

REM Start Laragon services
echo [1/4] Starting Laragon services...
cd /d C:\laragon
laragon.exe start

timeout /t 5

REM Start OCR Service
echo [2/4] Starting OCR microservice...
cd /d D:\ProyekTA\ocr-service
start /B cmd /c "venv\Scripts\activate.bat && python app.py"

timeout /t 3

REM Start Queue Worker
echo [3/4] Starting queue worker...
cd /d D:\ProyekTA
start /B cmd /c "php artisan queue:work --queue=ocr,graph,default --tries=3 --timeout=120"

timeout /t 2

REM Open browser
echo [4/4] Opening browser...
start http://sipadu.test

echo.
echo ====================================
echo SiPadu Development Environment Ready
echo ====================================
echo Web: http://sipadu.test
echo Neo4j: http://localhost:7474
echo OCR: http://127.0.0.1:5001
echo ====================================
pause
```

---

*Panduan ini dibuat untuk migrasi SiPadu dari Docker ke Laragon – 9 Maret 2026*
