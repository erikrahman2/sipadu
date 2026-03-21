# 🚀 Quick Start - Dashboard SiPadu

## ⚡ Jalankan dalam 5 Menit

### Step 1: Start All Services
```bash
cd d:\ProyekTA
docker-compose up -d
```

**Verifikasi semua service running:**
```bash
docker-compose ps
```

Pastikan 7 containers berjalan:
- ✅ nginx (port 8080)
- ✅ app (PHP-FPM)
- ✅ worker (Laravel Queue)
- ✅ mysql (port 3306)
- ✅ redis (port 6379)
- ✅ neo4j (port 7474, 7687)
- ✅ ocr-service (port 5000)

### Step 2: Setup Database (First Time Only)
```bash
# Jalankan migrasi
docker exec sipadu_app php artisan migrate --force

# Seed data awal (users, institutions, roles)
docker exec sipadu_app php artisan db:seed

# Sync users ke Neo4j
docker exec sipadu_app php artisan queue:work --once
```

### Step 3: Akses Dashboard
```
URL: http://localhost:8080/dashboard
```

**Login dengan kredensial default:**

| Role                | Email                                  | Password |
|---------------------|----------------------------------------|----------|
| Super Admin         | admin@sipadu.go.id                     | password |
| PA Assistant        | assistant@pa-jakarta.go.id             | password |
| PA Management       | management@pa-jakarta.go.id            | password |
| PA Staff            | staff@pa-jakarta.go.id                 | password |
| Disdukcapil Staff   | disdukcapil@disdukcapil-jakarta.go.id | password |

> **⚠️ IMPORTANT**: Ganti password default setelah login pertama di production!

---

## 🧪 Test Workflow

### Test 1: Buat Kasus Baru (PA Assistant)
1. Login sebagai `assistant@pa-jakarta.go.id`
2. Dashboard → Klik **"Buat Pengajuan"**
3. Isi form:
   - Institusi: PA Jakarta Selatan
   - NIK Pasangan: 3171234567890001
   - Nama Pasangan: Budi Santoso
   - Tanggal Cerai: 2024-01-15
   - No. Putusan: 123/Pdt.G/2024/PA.JKT
4. Klik **"Buat Kasus"**
5. Redirect otomatis ke detail kasus (status: DRAFT)

### Test 2: Upload Dokumen
1. Dari detail kasus, klik **"Upload"**
2. Pilih kasus yang baru dibuat
3. Pilih jenis dokumen: **KTP**
4. Upload file gambar KTP (JPG/PNG)
5. Centang **"Proses OCR otomatis"**
6. Klik **"Upload Dokumen"**
7. Progress bar muncul → selesai
8. Cek hasil OCR di menu **"OCR Result"**

### Test 3: Submit Case untuk Review
1. Buka detail kasus (masih DRAFT)
2. Klik tombol **"Submit"**
3. Status berubah: DRAFT → SUBMITTED
4. OCR job otomatis dijalankan (lihat di queue worker log)

### Test 4: Review & Approve (PA Management)
1. Logout → Login sebagai `management@pa-jakarta.go.id`
2. Dashboard → Klik **"Review Hasil OCR"** (filter OCR_PROCESSED)
3. Pilih kasus
4. Cek dokumen & data OCR
5. Klik **"Setujui"**
6. Status berubah: OCR_PROCESSED → PA_REVIEW → DISDUKCAPIL_VALIDATION

### Test 5: Validasi Final (Disdukcapil)
1. Logout → Login sebagai `disdukcapil@disdukcapil-jakarta.go.id`
2. Dashboard → Klik **"Validasi Masuk"**
3. Pilih kasus DISDUKCAPIL_VALIDATION
4. Cek data final
5. Klik **"Validasi"**
6. Status berubah: DISDUKCAPIL_VALIDATION → COMPLETED

### Test 6: Admin Features (Super Admin)
1. Logout → Login sebagai `admin@sipadu.go.id`
2. Langsung redirect ke **Admin Panel**
3. **Users**: Lihat daftar semua user
4. **Sync Status**: Trigger manual sync Neo4j
5. **Audit Trail**: Lihat log aktivitas (case created, approved, dll)
6. **Access Logs**: Monitor HTTP requests, filter slow queries

---

## 🔍 Monitoring & Debugging

### Check Logs
```bash
# Laravel log
docker exec sipadu_app tail -f storage/logs/laravel.log

# Queue worker (OCR processing, Neo4j sync)
docker logs -f sipadu_worker

# Neo4j
docker logs -f sipadu_neo4j
```

### Test Neo4j Connection
```bash
docker exec sipadu_app php artisan tinker
```
```php
app(App\Services\GraphService::class)->run('MATCH (n) RETURN count(n) as total');
```

### Check Queue Jobs
```bash
docker exec sipadu_app php artisan queue:work --once
# atau
docker exec sipadu_app php artisan queue:listen
```

### Clear Cache
```bash
docker exec sipadu_app php artisan cache:clear
docker exec sipadu_app php artisan config:clear
docker exec sipadu_app php artisan route:clear
docker exec sipadu_app php artisan view:clear
```

---

## 🐛 Common Issues & Solutions

### ❌ "Dashboard tidak muncul / blank screen"
**Solusi:**
```bash
# Clear semua cache
docker exec sipadu_app php artisan optimize:clear

# Pastikan session driver adalah redis
# Check .env: SESSION_DRIVER=redis

# Restart containers
docker-compose restart app worker
```

### ❌ "Unauthenticated" setelah login
**Solusi:**
```bash
# Regenerate JWT secret
docker exec sipadu_app php artisan jwt:secret --force

# Clear Laravel cache
docker exec sipadu_app php artisan cache:clear

# Check cookie domain di config/session.php
```

### ❌ Upload dokumen gagal
**Solusi:**
```bash
# Pastikan folder writable
docker exec sipadu_app chmod -R 775 storage/app/documents

# Check PHP upload limit di .env
# upload_max_filesize=10M
# post_max_size=12M
```

### ❌ OCR tidak jalan
**Solusi:**
```bash
# Check OCR service running
docker ps | grep ocr-service

# Test OCR endpoint
curl http://localhost:5000/health

# Restart OCR service
docker-compose restart ocr-service

# Check worker processing queue
docker logs -f sipadu_worker
```

### ❌ Neo4j sync gagal
**Solusi:**
```bash
# Test Neo4j connection
docker exec sipadu_app php artisan neo4j:test

# Check password (minimal 8 karakter)
# .env: NEO4J_PASSWORD=neo4jSecret1

# Trigger manual sync dari dashboard:
# Admin → Sync Status → "Sync Sekarang"
```

### ❌ "This action is unauthorized" (ReBACService)
**Solusi:**
```bash
# Sync semua user ke Neo4j
docker exec sipadu_app php artisan app:sync-rebac

# Clear policy cache
docker exec sipadu_app php artisan cache:forget rebac:*

# Check user sudah masuk Neo4j
docker exec sipadu_neo4j cypher-shell -u neo4j -p neo4jSecret1
> MATCH (u:User) RETURN u.email, u.name;
```

---

## 📊 Monitoring Performance

### Dashboard Admin → Access Logs
- Filter slow requests: `>1000ms`
- Check error rate: 4xx/5xx
- Monitor endpoint paling sering diakses

### Metrics to Watch
| Metric                  | Good      | Warning   | Critical |
|-------------------------|-----------|-----------|----------|
| Dashboard load time     | <500ms    | 500-1000ms| >1000ms  |
| Upload 5MB file         | <5s       | 5-10s     | >10s     |
| OCR processing (1 page) | <3s       | 3-10s     | >10s     |
| Neo4j sync (100 nodes)  | <2s       | 2-5s      | >5s      |
| Case list pagination    | <200ms    | 200-500ms | >500ms   |

---

## 🔐 Security Checklist

- [ ] Ganti semua password default (users di seeder)
- [ ] Set `APP_DEBUG=false` di production
- [ ] Enable HTTPS (nginx SSL certificate)
- [ ] Set strong `APP_KEY` & `JWT_SECRET`
- [ ] Restrict database access (hanya dari container app)
- [ ] Enable firewall: buka hanya port 80, 443
- [ ] Backup database reguler (daily)
- [ ] Monitor audit logs untuk aktivitas mencurigakan
- [ ] Set rate limiting ketat di production (throttle:10,1 untuk login)

---

## 📦 Deployment Production

### Pre-Deployment
```bash
# Test semua fitur di staging
# Run automated tests
docker exec sipadu_app php artisan test

# Optimize Laravel
docker exec sipadu_app php artisan config:cache
docker exec sipadu_app php artisan route:cache
docker exec sipadu_app php artisan view:cache

# Compile assets (jika ada)
npm run build
```

### Deploy
```bash
# Pull latest code
git pull origin main

# Rebuild containers
docker-compose build --no-cache

# Restart services
docker-compose down
docker-compose up -d

# Run migrations
docker exec sipadu_app php artisan migrate --force

# Clear cache
docker exec sipadu_app php artisan optimize:clear
```

### Post-Deployment Health Check
1. ✅ Akses `http://your-domain.com/dashboard` → berhasil load
2. ✅ Login → redirect ke dashboard sesuai role
3. ✅ Create case → berhasil simpan
4. ✅ Upload dokumen → berhasil & OCR jalan
5. ✅ Neo4j sync → running (check Admin → Sync Status)
6. ✅ Queue worker active: `docker logs sipadu_worker`

---

## 📞 Support

**Error Persisting?**
1. Collect logs: `docker-compose logs > error.log`
2. Check Laravel log: `storage/logs/laravel.log`
3. Check queue worker: `docker logs sipadu_worker`
4. Review audit trail di Admin Panel
5. Konsultasi dengan README lengkap di `DASHBOARD_GUIDE.md`

---

**Happy Coding! 🚀**

Dashboard SiPadu v1.0.0 — Built with ❤️ using Laravel 11, Neo4j 5, Alpine.js, Tailwind CSS
