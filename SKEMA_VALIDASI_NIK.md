# Skema Database: Validasi NIK & Penggantian Data

> **Tanggal:** 9 Maret 2026  
> **Fitur:** Tracking NIK, Rate Limit, Validasi Pasangan

---

## 📋 Ringkasan Aturan Bisnis

Sistem pengajuan publik sekarang memiliki 4 aturan utama:

### 1. **Data NIK Terbaru Menggantikan Data Lama**
- Jika ada 2 atau lebih pengajuan dengan **NIK yang sama**, sistem akan menggunakan **data inputan terbaru**
- Data lama akan **di-soft delete** dan ditandai dengan kolom `replaced_by` yang menunjuk ke data baru
- Data lama **tidak ditampilkan** di dashboard petugas (hanya data dengan `is_active=true` yang muncul)

### 2. **Limit Pendaftaran: 3x Per Minggu**
- Setiap NIK hanya dapat mendaftar **maksimal 3 kali dalam 7 hari**
- Hitungan hanya untuk status `PENDING`, `REVIEWING`, `WAITING_OCR`, `APPROVED`, `COMPLETED` (status `REJECTED` tidak dihitung)
- Jika kuota habis, sistem akan memberikan informasi kapan bisa mendaftar lagi

### 3. **Pembekuan NIK Saat Dalam Proses PA/Disdukcapil**
- **NIK DIBEKUKAN** jika ada data yang sedang dalam proses:
  - **PublicSubmission** dengan status: `REVIEWING`, `WAITING_OCR`, atau `APPROVED`
  - **Case (Kasus Resmi)** dengan status: `PA_REVIEW` atau `DISDUKCAPIL_VALIDATION`
- Selama NIK dibekukan, **tidak bisa input data baru** dengan NIK yang sama
- Pembekuan otomatis dicabut setelah proses selesai (`COMPLETED`) atau ditolak (`REJECTED`)

### 4. **Validasi Pasangan NIK di Disdukcapil**
- Jika data **sudah sampai ke Disdukcapil** (status `APPROVED` atau `COMPLETED`), maka **2 NIK pasangan tidak boleh ditemukan lagi** dalam data yang sama
- Sistem akan mengecek apakah pasangan NIK (petitioner + respondent) sudah ada di data aktif Disdukcapil
- Validasi bekerja untuk kedua arah pasangan (A+B atau B+A)
- NIK pemohon **tidak boleh sama** dengan NIK pasangan

---

## 🗄️ Struktur Tabel Database

### Tabel: `public_submissions`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `tracking_token` | VARCHAR(64) | Token untuk tracking (PUB-xxxxx) |
| `nik` | VARCHAR(16) | NIK pemohon (diindex) |
| `petitioner_name` | VARCHAR(255) | Nama pemohon |
| `phone_wa` | VARCHAR(20) | Nomor WhatsApp (normalisasi 62xxx) |
| `respondent_name` | VARCHAR(255) | Nama pasangan |
| `respondent_nik` | VARCHAR(16) | NIK pasangan |
| `divorce_date` | DATE | Tanggal cerai |
| `verdict_number` | VARCHAR(255) | Nomor putusan PA |
| `notes` | TEXT | Catatan tambahan |
| `status` | ENUM | `PENDING`, `REVIEWING`, `WAITING_OCR`, `APPROVED`, `REJECTED`, `COMPLETED` |
| `wa_sent_at` | TIMESTAMP | Waktu notifikasi WA dikirim |
| `wa_message_id` | VARCHAR(255) | ID pesan dari gateway WA |
| `wa_status` | ENUM | `pending`, `sent`, `delivered`, `failed` |
| `wa_error` | VARCHAR(255) | Pesan error WA (jika gagal) |
| `case_id` | BIGINT UNSIGNED | FK ke `cases` (jika sudah disetujui) |
| **`replaced_by`** | BIGINT UNSIGNED | **[BARU]** FK ke `public_submissions` (ID data baru yang menggantikan) |
| **`is_active`** | BOOLEAN | **[BARU]** TRUE = data terbaru, FALSE = sudah diganti |
| `processed_by` | BIGINT UNSIGNED | FK ke `users` (petugas yang memproses) |
| `processed_at` | TIMESTAMP | Waktu diproses |
| `ip_address` | VARCHAR(45) | IP address pemohon |
| `user_agent` | VARCHAR(255) | Browser user agent |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diupdate |
| `deleted_at` | TIMESTAMP | Soft delete |

### Index & Constraint

```sql
-- Primary & Unique
PRIMARY KEY (id)
UNIQUE KEY (tracking_token)

-- Foreign Keys
FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE SET NULL
FOREIGN KEY (replaced_by) REFERENCES public_submissions(id) ON DELETE SET NULL
FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL

-- Index untuk query performa tinggi
INDEX (nik)                                           -- Cek rate limit per NIK
INDEX (is_active)                                     -- Filter data aktif
INDEX (nik, created_at)                               -- Rate limit query
INDEX (nik, is_active, created_at)                    -- Query data aktif per NIK
INDEX (nik, respondent_nik, status)                   -- Validasi pasangan di Disdukcapil
```

---

## 🔄 Alur Penggantian Data Lama

### Skenario: User dengan NIK `3174010101900001` mendaftar 2 kali

**Pengajuan 1 (ID=100):**
```
nik: 3174010101900001
petitioner_name: Ahmad Warga
status: PENDING
is_active: true
replaced_by: null
```

**Pengajuan 2 (ID=105) - NIK sama:**
Ketika user mendaftar lagi dengan NIK yang sama, sistem akan:

1. **Buat data baru** (ID=105):
   ```
   nik: 3174010101900001
   petitioner_name: Ahmad Warga (updated)
   status: PENDING
   is_active: true
   replaced_by: null
   ```

2. **Update & Soft Delete data lama** (ID=100):
   ```
   is_active: false
   replaced_by: 105
   deleted_at: 2026-03-09 14:30:00
   ```

3. **Dashboard petugas hanya menampilkan data baru** (ID=105) karena query selalu filter `is_active=true`

4. **History tetap tersimpan** - Data lama (ID=100) masih bisa diakses via `withTrashed()` untuk audit log

---

## 🚦 Validasi Rate Limit

### Query untuk Cek Kuota NIK

```sql
-- Hitung pengajuan aktif dalam 7 hari terakhir
SELECT COUNT(*) 
FROM public_submissions
WHERE nik = '3174010101900001'
  AND is_active = true
  AND status NOT IN ('REJECTED')
  AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  AND deleted_at IS NULL;
```

### Logika di PHP

```php
// Cek apakah NIK masih bisa mendaftar
$count = PublicSubmission::countActiveByNik($nik);
$isAllowed = $count < 3;  // MAX_SUBMISSIONS = 3

// Hitung sisa kuota
$remaining = max(0, 3 - $count);

// Cari tanggal pengajuan tertua untuk hitung kapan bisa daftar lagi
$oldestDate = PublicSubmission::where('nik', $nik)
    ->where('is_active', true)
    ->where('status', '!=', 'REJECTED')
    ->where('created_at', '>=', now()->subDays(7))
    ->oldest()
    ->value('created_at');

$nextAllowed = $oldestDate 
    ? Carbon::parse($oldestDate)->addDays(7)
    : null;
```

---

## 👫 Validasi Pasangan NIK

### Aturan

Pasangan NIK (petitioner + respondent) yang **sama** tidak boleh mendaftar jika:
- Salah satu pengajuan mereka sudah berstatus `APPROVED` atau `COMPLETED`
- Data mereka masih `is_active=true` (belum digantikan)

### Query untuk Cek Pasangan di Disdukcapil

```sql
-- Cek apakah pasangan NIK sudah ada di data Disdukcapil
SELECT COUNT(*) 
FROM public_submissions
WHERE is_active = true
  AND status IN ('APPROVED', 'COMPLETED')
  AND deleted_at IS NULL
  AND (
    -- Kombinasi 1: A sebagai petitioner, B sebagai respondent
    (nik = '3174010101900001' AND respondent_nik = '3174010201900002')
    OR
    -- Kombinasi 2: B sebagai petitioner, A sebagai respondent (kebalikan)
    (nik = '3174010201900002' AND respondent_nik = '3174010101900001')
  );
```

### Logika di PHP

```php
// Validasi 1: NIK tidak boleh sama
if (PublicSubmission::isSameNik($petitionerNik, $respondentNik)) {
    throw new \RuntimeException('NIK pemohon tidak boleh sama dengan NIK pasangan.');
}

// Validasi 2: Pasangan belum ada di Disdukcapil
if (PublicSubmission::hasCoupleInDisdukcapil($petitionerNik, $respondentNik)) {
    throw new \RuntimeException(
        'Pasangan NIK ini sudah terdaftar dan sedang/telah diproses oleh Disdukcapil.'
    );
}
```

---

## 🔒 Pembekuan NIK (Freeze Logic)

### Konsep

**NIK dibekukan sementara** ketika ada data dengan NIK tersebut yang sedang dalam proses di PA Management atau Disdukcapil. Selama dibekukan, NIK tidak dapat melakukan pengajuan baru.

### Status yang Membekukan NIK

#### A. Dari PublicSubmission

| Status | Keterangan | Dibekukan? |
|--------|------------|-----------|
| `PENDING` | Baru masuk, belum diproses | ❌ Tidak |
| `REVIEWING` | Sedang ditinjau petugas | ✅ **YA** |
| `WAITING_OCR` | Menunggu hasil OCR | ✅ **YA** |
| `APPROVED` | Disetujui, case dibuat | ✅ **YA** |
| `REJECTED` | Ditolak | ❌ Tidak (pencabutan freeze) |
| `COMPLETED` | Selesai | ❌ Tidak (pencabutan freeze) |

#### B. Dari Case (Kasus Resmi)

| Status | Keterangan | Dibekukan? |
|--------|------------|-----------|
| `DRAFT` | Draft awal | ❌ Tidak |
| `SUBMITTED` | Sudah disubmit | ❌ Tidak |
| `OCR_PROCESSED` | OCR selesai | ❌ Tidak |
| `PA_REVIEW` | **Review PA Management** | ✅ **YA** |
| `DISDUKCAPIL_VALIDATION` | **Validasi Disdukcapil** | ✅ **YA** |
| `COMPLETED` | Selesai penuh | ❌ Tidak (pencabutan freeze) |
| `REJECTED` | Ditolak | ❌ Tidak (pencabutan freeze) |

### Query untuk Cek NIK Frozen

```sql
-- Cek apakah NIK dibekukan dari PublicSubmission
SELECT COUNT(*) 
FROM public_submissions
WHERE nik = '3174010101900001'
  AND is_active = true
  AND status IN ('REVIEWING', 'WAITING_OCR', 'APPROVED')
  AND deleted_at IS NULL;

-- Cek apakah NIK dibekukan dari Case
SELECT COUNT(*) 
FROM cases
WHERE petitioner_nik = '3174010101900001'
  AND status IN ('PA_REVIEW', 'DISDUKCAPIL_VALIDATION')
  AND deleted_at IS NULL;
```

### Logika di PHP

```php
// Cek apakah NIK dibekukan
if (PublicSubmission::isNikFrozen($nik)) {
    $reason = PublicSubmission::getFrozenReason($nik);
    
    // $reason berisi:
    // [
    //   'type' => 'public_submission' atau 'case',
    //   'status' => 'REVIEWING', 'PA_REVIEW', dll,
    //   'token' => 'PUB-xxxxx' atau 'TRK-xxxxx',
    //   'case_number' => 'CASE-xxxxx' (jika type=case)
    // ]
    
    throw new \RuntimeException(
        "NIK ini tidak dapat mengajukan permohonan baru karena masih ada pengajuan yang sedang diproses. " .
        "Token tracking: {$reason['token']}"
    );
}
```

### Alur Timeline Pembekuan

```
Waktu 0    → User input NIK A (PENDING) ✅ Boleh input
    ↓
Waktu 1    → Petugas mulai review (REVIEWING) 🔒 NIK DIBEKUKAN
    ↓       → User coba input lagi dengan NIK A ❌ DITOLAK
    ↓
Waktu 2    → Petugas approve, buat Case (APPROVED) 🔒 MASIH DIBEKUKAN
    ↓
Waktu 3    → Case masuk PA Review (PA_REVIEW) 🔒 MASIH DIBEKUKAN
    ↓       → User coba input lagi ❌ DITOLAK (ada case sedang direview PA)
    ↓
Waktu 4    → PA approve, ke Disdukcapil (DISDUKCAPIL_VALIDATION) 🔒 MASIH DIBEKUKAN
    ↓
Waktu 5    → Disdukcapil approve (COMPLETED) ✅ PENCABUTAN FREEZE
    ↓
Waktu 6    → User boleh input lagi ✅ Boleh input (jika belum ada pasangan NIK)
```

### Pesan Error untuk User

Ketika NIK dibekukan, sistem akan memberikan pesan error yang informatif:

```
"NIK ini tidak dapat mengajukan permohonan baru karena masih ada pengajuan 
yang sedang ditinjau petugas. Token tracking: PUB-ABC123XYZ. 
Silakan tunggu hingga proses selesai atau hubungi kantor terkait."
```

atau

```
"NIK ini tidak dapat mengajukan permohonan baru karena masih ada pengajuan 
yang sedang direview oleh Pengadilan Agama. Nomor kasus: CASE-20260309-ABC123. 
Silakan tunggu hingga proses selesai atau hubungi kantor terkait."
```

---

## 🔍 Query Dashboard Petugas

### Tampilkan hanya data aktif (tidak termasuk data yang sudah diganti)

```sql
SELECT * 
FROM public_submissions
WHERE is_active = true
  AND deleted_at IS NULL
ORDER BY created_at DESC;
```

### Eloquent Query

```php
// Dashboard petugas - hanya data aktif
$submissions = PublicSubmission::active()  // scope: where is_active=true
    ->whereIn('status', ['PENDING', 'REVIEWING'])
    ->latest()
    ->paginate(20);

// Cek data yang sudah sampai Disdukcapil
$inDisdukcapil = PublicSubmission::active()
    ->reachedDisdukcapil()  // scope: whereIn status APPROVED/COMPLETED
    ->get();
```

---

## 📊 Contoh Skenario

### Skenario 1: User Daftar 3x dalam Seminggu (Data Terbaru Mengganti Lama)

| Waktu | Aksi | Status | is_active | Keterangan |
|-------|------|--------|-----------|------------|
| Senin 08:00 | Daftar #1 (ID=10) | PENDING | TRUE | Pengajuan pertama |
| Selasa 10:00 | Daftar #2 (ID=11) | PENDING | TRUE | Pengajuan kedua (data lama ID=10 di-replace) |
| Rabu 14:00 | Daftar #3 (ID=12) | PENDING | TRUE | Pengajuan ketiga (data lama ID=11 di-replace) |
| Kamis 09:00 | Daftar #4 ❌ | - | - | **DITOLAK: Kuota habis, bisa daftar lagi Senin depan** |

**Data di database:**
- ID=10: `is_active=false`, `replaced_by=11`, `deleted_at=[timestamp]`
- ID=11: `is_active=false`, `replaced_by=12`, `deleted_at=[timestamp]`
- ID=12: `is_active=true`, `replaced_by=null` ← **Yang tampil di dashboard**

### Skenario 2: Pembekuan NIK Saat Proses PA/Disdukcapil

**User dengan NIK 3174010101900001:**

| Waktu | Event | Status | NIK Frozen? | Bisa Input Baru? |
|-------|-------|--------|-------------|------------------|
| **Senin 08:00** | User input pengajuan | PENDING | ❌ Tidak | ✅ Ya (belum diproses) |
| **Senin 10:00** | Petugas mulai review | REVIEWING | ✅ **YA** | ❌ **TIDAK** |
| **Senin 11:00** | User coba input lagi | - | ✅ **YA** | ❌ **DITOLAK** dengan pesan: "NIK ini masih ada pengajuan yang sedang ditinjau petugas" |
| **Selasa 09:00** | Proses OCR | WAITING_OCR | ✅ **YA** | ❌ **TIDAK** |
| **Selasa 14:00** | Petugas approve, case dibuat | APPROVED → Case PA_REVIEW | ✅ **YA** | ❌ **TIDAK** |
| **Rabu 10:00** | PA Management review | Case PA_REVIEW | ✅ **YA** | ❌ **DITOLAK** dengan pesan: "Ada kasus yang sedang direview PA" |
| **Kamis 15:00** | PA approve, ke Disdukcapil | Case DISDUKCAPIL_VALIDATION | ✅ **YA** | ❌ **TIDAK** |
| **Jumat 16:00** | Disdukcapil approve selesai | Case COMPLETED | ❌ Tidak | ✅ **BOLEH INPUT LAGI** |

**Kesimpulan:** NIK dibekukan total **4 hari** (Senin 10:00 s/d Jumat 16:00)

### Skenario 3: Validasi Pasangan di Disdukcapil

| NIK Pemohon | NIK Pasangan | Status | is_active | Boleh Daftar Lagi? |
|-------------|--------------|--------|-----------|-------------------|
| A | B | APPROVED | TRUE | ❌ **TIDAK** - Sudah di Disdukcapil |
| B | A | - | - | ❌ **TIDAK** - Kombinasi kebalikan tetap invalid |
| A | C | - | - | ✅ **YA** - Pasangan berbeda |
| C | D | PENDING | TRUE | ✅ **YA** - Belum sampai Disdukcapil |

### Skenario 4: Data Ditolak (REJECTED)

NIK `A` mendaftar 5x dalam seminggu:
- Pengajuan #1: `REJECTED` → **Tidak dihitung** ke kuota, **freeze dicabut**
- Pengajuan #2: `PENDING` → Dihitung (1/3)
- Pengajuan #3: `REJECTED` → **Tidak dihitung** ke kuota
- Pengajuan #4: `REVIEWING` → Dihitung (2/3), **NIK frozen**
- Pengajuan #5: Coba input → ❌ **DITOLAK** karena #4 sedang REVIEWING

### Skenario 5: Kombinasi Replace + Freeze

**User NIK X daftar berkali-kali:**

| Waktu | Aksi | ID | Status | is_active | Frozen? |
|-------|------|----|--------|-----------|---------|
| Senin 08:00 | Input #1 | 100 | PENDING | TRUE | ❌ |
| Senin 09:00 | Input #2 (data sama, revisi) | 101 | PENDING | TRUE | ❌ |
|  | *ID=100 replaced & soft deleted* | 100 | PENDING | FALSE | - |
| Senin 10:00 | Petugas review ID=101 | 101 | REVIEWING | TRUE | ✅ |
| Senin 11:00 | User coba input #3 | - | - | - | ❌ **DITOLAK: NIK frozen** |
| Selasa 14:00 | Petugas tolak ID=101 | 101 | REJECTED | TRUE | ❌ Freeze dicabut |
| Selasa 15:00 | User input #3 lagi | 102 | PENDING | TRUE | ❌ Boleh (freeze sudah dicabut) |

**Dashboard petugas hanya tampilkan:**
- ID=101 (REJECTED, is_active=true)
- ID=102 (PENDING, is_active=true)

**ID=100 tidak tampil** (is_active=false, sudah di-replace)

---

## 🛠️ Menjalankan Migrasi

```bash
# Jalankan migrasi baru
php artisan migrate

# Atau dengan Docker
docker compose exec app php artisan migrate

# Rollback jika perlu
php artisan migrate:rollback --step=1
```

### Verify Migrasi Berhasil

```bash
# MySQL
docker compose exec mysql mysql -u sipadu_app -p pa_disdukcapil -e "
  DESCRIBE public_submissions;
  SHOW INDEX FROM public_submissions;
"

# Atau via Laravel
docker compose exec app php artisan db:show public_submissions
```

---

## 📝 Testing di Tinker

```bash
docker compose exec app php artisan tinker
```

```php
use App\Models\PublicSubmission;
use App\Models\CaseModel;

// Test 1: Cek rate limit
$nik = '3174010101900001';
$count = PublicSubmission::countActiveByNik($nik);
// Output: 0 (jika belum ada data)

// Test 2: Buat pengajuan pertama
$sub1 = PublicSubmission::create([
    'nik' => $nik,
    'petitioner_name' => 'Ahmad',
    'phone_wa' => '081234567890',
    'status' => 'PENDING',
    'is_active' => true,
]);

// Test 3: Buat pengajuan kedua dengan NIK sama (akan replace yang pertama)
$sub2 = PublicSubmission::create([
    'nik' => $nik,
    'petitioner_name' => 'Ahmad (Updated)',
    'phone_wa' => '081234567890',
    'status' => 'PENDING',
    'is_active' => true,
]);

PublicSubmission::replaceOldSubmissions($nik, $sub2->id);
// Output: 1 (1 data lama di-replace)

$sub1->refresh();
$sub1->is_active;  // false
$sub1->replaced_by;  // $sub2->id
$sub1->trashed();  // true

// Test 4: Validasi pasangan NIK
PublicSubmission::isSameNik('123', '123');  // true (invalid)
PublicSubmission::isSameNik('123', '456');  // false (valid)

PublicSubmission::hasCoupleInDisdukcapil('123', '456');  // false (belum ada di DB)

// Test 5: Pembekuan NIK (Freeze)
PublicSubmission::isNikFrozen($nik);  // false (status PENDING tidak freeze)

// Update status ke REVIEWING untuk simulate freeze
$sub2->update(['status' => 'REVIEWING']);
PublicSubmission::isNikFrozen($nik);  // TRUE (NIK frozen!)

$reason = PublicSubmission::getFrozenReason($nik);
// Output: [
//   'type' => 'public_submission',
//   'status' => 'REVIEWING',
//   'token' => 'PUB-xxxxx'
// ]

// Test 6: Freeze dari Case
$case = CaseModel::create([
    'petitioner_nik' => $nik,
    'petitioner_name' => 'Ahmad',
    'status' => 'PA_REVIEW',  // Status yang freeze
    'institution_id' => 1,
]);

PublicSubmission::isNikFrozen($nik);  // TRUE (frozen dari Case)

$reason = PublicSubmission::getFrozenReason($nik);
// Output: [
//   'type' => 'case',
//   'status' => 'PA_REVIEW',
//   'token' => 'TRK-xxxxx',
//   'case_number' => 'CASE-xxxxx'
// ]

// Test 7: Pencabutan freeze
$case->update(['status' => 'COMPLETED']);  // Proses selesai
$sub2->update(['status' => 'COMPLETED']);
PublicSubmission::isNikFrozen($nik);  // FALSE (freeze dicabut)

// Test 8: Kombinasi lengkap
// Buat 3 pengajuan dalam seminggu
for ($i = 1; $i <= 3; $i++) {
    PublicSubmission::create([
        'nik' => '9999999999999999',
        'petitioner_name' => "Test User $i",
        'phone_wa' => '081234567890',
        'status' => 'PENDING',
        'is_active' => true,
    ]);
}

$count = PublicSubmission::countActiveByNik('9999999999999999');
// Output: 3 (sudah limit)

// Coba buat yang ke-4 (harus validasi di service dulu, di tinker langsung bisa create)
```

---

## 🔐 Security & Performance

### Index yang Dioptimalkan

- **`(nik, is_active, created_at)`** - Query rate limit sangat cepat (composite index)
- **`(nik, respondent_nik, status)`** - Validasi pasangan O(1) lookup
- **`is_active`** - Filter dashboard petugas

### Soft Delete vs Hard Delete

- Data lama **tidak dihapus permanen** (soft delete) untuk audit trail
- Bisa di-restore jika terjadi kesalahan operasional
- Query production selalu filter `deleted_at IS NULL` secara otomatis (via Eloquent scope)

### Rate Limiting

- Menggunakan database-level counting (bukan Redis) untuk akurasi 100%
- Transaction-safe: concurrent request dari NIK yang sama akan di-serialize oleh database

---

## 📚 Referensi Kode

| File | Keterangan |
|------|------------|
| `database/migrations/2026_03_09_000001_add_nik_tracking_to_public_submissions.php` | Migrasi baru: kolom `replaced_by`, `is_active`, dan index |
| `app/Models/PublicSubmission.php` | Model dengan method validasi & scope |
| `app/Services/PublicSubmissionService.php` | Service layer dengan logika penggantian data |
| `app/Http/Controllers/API/CaseController.php` | API Controller untuk Case (PA Assistant) dengan validasi NIK |
| `app/Http/Controllers/Web/DashboardController.php` | Web Controller untuk Case dengan validasi NIK |

---

## 🏢 Penerapan Validasi NIK pada Case (PA Assistant)

Sejak 9 Maret 2026, **validasi NIK yang sama juga diterapkan pada CaseController** (input dari PA Assistant melalui dashboard).

### Validasi yang Diterapkan

#### 1. NIK Pemohon ≠ NIK Pasangan
```php
// CaseController::store() & DashboardController::storeCase()
if (\App\Models\PublicSubmission::isSameNik($petitionerNik, $spouseNik)) {
    throw new \RuntimeException('NIK pemohon tidak boleh sama dengan NIK pasangan.');
}
```

#### 2. Pembekuan NIK (Freeze Check)
```php
// Cross-model validation: cek PublicSubmission DAN Case
if (\App\Models\PublicSubmission::isNikFrozen($petitionerNik)) {
    $reason = \App\Models\PublicSubmission::getFrozenReason($petitionerNik);
    
    // Tampilkan peringatan dengan tracking token atau case number
    if ($reason['type'] === 'public_submission') {
        $message = "NIK sedang diproses. Token: {$reason['token']}";
    } else {
        $message = "NIK sedang diproses. Kasus: {$reason['case_number']}";
    }
    
    throw new \RuntimeException($message);
}
```

#### 3. Validasi Pasangan NIK di Disdukcapil
```php
// Cek apakah couple NIK sudah ada di Disdukcapil
if ($spouseNik && \App\Models\PublicSubmission::hasCoupleInDisdukcapil($petitionerNik, $spouseNik)) {
    throw new \RuntimeException('Pasangan NIK sudah terdaftar di Disdukcapil.');
}
```

### Perbedaan dengan PublicSubmission

| Validasi | PublicSubmission | Case (PA Assistant) |
|----------|------------------|---------------------|
| **NIK pemohon ≠ NIK pasangan** | ✅ Wajib | ✅ Wajib |
| **NIK Freeze Check** | ✅ Hard block (error) | ✅ Hard block (error + peringatan koordinasi) |
| **Couple NIK di Disdukcapil** | ✅ Wajib | ✅ Wajib |
| **Rate Limiting (3x/week)** | ✅ Diterapkan | ❌ Tidak diterapkan (PA bisa unlimited) |
| **Data Replacement** | ✅ Auto-replace old data | ✅ **Auto-replace DRAFT cases** (sejak 9 Maret 2026) |

### Data Replacement untuk Case

Sejak **9 Maret 2026**, Case juga menerapkan logika data replacement menggunakan method `CaseModel::replaceOldCases()`:

```php
// app/Models/CaseModel.php
public static function replaceOldCases(?string $petitionerNik, ?string $spouseNik, int $newCaseId): int
{
    $count = 0;
    
    // Replace cases dengan petitioner_nik yang sama (DRAFT only)
    if ($petitionerNik) {
        $oldByPetitioner = static::withoutTrashed()
            ->where('petitioner_nik', $petitionerNik)
            ->where('id', '!=', $newCaseId)
            ->where('status', 'DRAFT')
            ->get();
        
        foreach ($oldByPetitioner as $old) {
            $old->delete();  // soft delete
            $count++;
        }
    }
    
    // Replace cases dengan spouse_nik yang sama (DRAFT only)
    if ($spouseNik) {
        $oldBySpouse = static::withoutTrashed()
            ->where('spouse_nik', $spouseNik)
            ->where('id', '!=', $newCaseId)
            ->where('status', 'DRAFT')
            ->get();
        
        foreach ($oldBySpouse as $old) {
            if (!$old->trashed()) {
                $old->delete();
                $count++;
            }
        }
    }
    
    return $count;
}
```

#### Perbedaan Data Replacement: PublicSubmission vs Case

| Aspek | PublicSubmission::replaceOldSubmissions() | CaseModel::replaceOldCases() |
|-------|------------------------------------------|------------------------------|
| **NIK yang dicek** | Hanya `nik` (pemohon) | `petitioner_nik` DAN `spouse_nik` |
| **Status yang diganti** | Semua status (PENDING, REVIEWING, dll) | ❌ **HANYA DRAFT** |
| **Proteksi data** | Tidak ada (semua di-replace) | ✅ **Cases yang sudah SUBMITTED/OCR_PROCESSED/PA_REVIEW tidak tersentuh** |
| **Mekanisme** | `replaced_by` FK + `is_active=false` + soft delete | Soft delete saja (tanpa `replaced_by`) |
| **Use case** | Data citizen bisa revisi kapan saja | Staff PA bisa revisi SEBELUM submit |

**Catatan Penting:**
- ✅ Case dengan status `DRAFT` akan di-replace dan soft-deleted
- ❌ Case dengan status `SUBMITTED`, `OCR_PROCESSED`, `PA_REVIEW`, `DISDUKCAPIL_VALIDATION`, `COMPLETED`, `REJECTED` **tidak akan disentuh**
- 📝 Proteksi ini mencegah data yang sudah diproses tercampur dengan draft baru

### Catatan Implementasi

- **Konsistensi**: Menggunakan method yang sama (`PublicSubmission::isSameNik()`, `isNikFrozen()`, `hasCoupleInDisdukcapil()`)
- **Cross-Model Validation**: `isNikFrozen()` mengecek **both PublicSubmission AND Case** untuk freeze detection
- **Data Replacement**: 
  - `PublicSubmission::replaceOldSubmissions()` untuk data citizen (replace semua status)
  - `CaseModel::replaceOldCases()` untuk data PA Assistant (replace DRAFT only)
- **Error Messages**: Informatif dengan tracking token atau case number untuk koordinasi
- **PA Flexibility**: Rate limiting tidak diterapkan untuk PA Assistant karena mereka adalah staff resmi
- **Audit Trail**: Soft delete digunakan untuk preserve history dan debugging

### Endpoint yang Menerapkan Validasi

1. **API Endpoint**: `POST /api/v1/cases` (API\CaseController::store)
2. **Web Form**: `POST /dashboard/cases` (Web\DashboardController::storeCase)

---

**Dokumentasi dibuat:** 9 Maret 2026  
**Skema versi:** 2.0  
**Kompatibel dengan:** Laravel 11, MySQL 8.0+
