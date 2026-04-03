# Integrasi Pengajuan Publik ke PA Assistant & PA Management

## 📋 Ringkasan Integrasi

Data dari **Pengajuan Berupa Publik** (form yang diakses masyarakat di `/pengajuan`) sekarang terintegrasi penuh dengan dashboard **PA Assistant** dan **PA Management** serta **Disdukcapil Staff**.

## ✨ Fitur Terintegrasi

### 1. Data Dari Pengajuan Publik di Dashboard PA

**Lokasi:** Dashboard > Kasus > Tab "Semua" atau "Pengajuan Publik"

- Pengajuan publik menampilkan status: `PENDING`, `REVIEWING`, `APPROVED`
- Data ditampilkan dalam tabel yang sama dengan kasus internal
- **Badge "Pengajuan Publik"** membedakan dari kasus internal
- Icons & warna berbeda untuk visual distinction

### 2. Konversi ke Case (Kasus Resmi)

**Fitur:** PA Assistant dapat mengkonversi pengajuan publik menjadi kasus resmi

**Cara Kerja:**
```
Pengajuan Publik → [Convert to Case] → CaseModel
         ↓
    Dapat diproses oleh:
    - PA Management (OCR Review)
    - Disdukcapil Staff (Validasi Final)
```

**Method:** `POST /dashboard/cases/from-public/{publicSubmissionId}`

Automated conversion includes:
- Copy data Pemohon/Pasangan → Petitioner/Spouse
- Copy semua dokumen dari PublicSubmission → CaseDocuments
- Set `source_type = 'public'` pada case
- Create link `case.public_submission_id = submission.id`

### 3. Label & Identifikasi Sumber

Setiap case menampilkan badge:
- **"Pengajuan Publik"** (emerald/hijau) - dari form publik
- **"Internal"** (blue/biru) - dibuat langsung oleh PA Assistant

**Lokasi Badge:**
- Dashboard Cases Table (kolom "Sumber")
- Case Detail Page
- PA Management Review Workspace

## 🗄️ Perubahan Database

### Tabel: `cases`

**Kolom Baru:**
```sql
- public_submission_id BIGINT UNSIGNED NULL
- source_type ENUM('internal', 'public') DEFAULT 'internal'
```

**Indexing:**
- `INDEX public_submission_id`
- `INDEX source_type`

**Foreign Key:**
- `FK public_submission_id → public_submissions.id ON DELETE SET NULL`

### Tabel: `public_submissions`

**Kolom Existing:**
- Sebelumnya: Data pemohon tunggal (`nik`, `petitioner_name`)
- Sekarang: Data pasangan (`nik_suami`, `nama_suami`, `nik_istri`, `nama_istri`)
- Address fields: `alamat_suami`, `rt_rw_suami`, `kelurahan_suami`, `kecamatan_suami`, dsb

**Hubungan ke Cases:**
- `PublicSubmission.generatedCase()` - Kasus yang dibuat dari submission ini
- `CaseModel.sourcePublicSubmission()` - PublicSubmission asal case ini

## 📊 Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│  USER (Masyarakat)                                          │
│  http://localhost:8000/pengajuan                            │
│  ↓                                                           │
│  PublicSubmission Created {nik_suami, nama_suami, ...}     │
│  Status: PENDING                                            │
│  ↓                                                           │
│  [Email/WhatsApp Notification]                              │
│  ↓                                                           │
│  PA ASSISTANT DASHBOARD                                     │
│  Dashboard > Kasus > "Pengajuan Publik" Tab                 │
│  ↓                                                           │
│  [Button: Convert to Case]                                  │
│  ↓                                                           │
│  CaseModel Created (source_type='public')                  │
│  public_submission_id = submission.id                       │
│  Status: SUBMITTED                                          │
│  ↓                                                           │
│  PA MANAGEMENT (OCR Review)                                 │
│  Dashboard > Review Cases                                   │
│  [Case dengan badge "Pengajuan Publik"]                     │
│  ↓                                                           │
│  DISDUKCAPIL STAFF (Final Validation)                       │
│  Dashboard > Validasi Cases                                 │
│  [Case dengan badge "Pengajuan Publik"]                     │
│  ↓                                                           │
│  ✅ COMPLETED atau ❌ REJECTED                              │
└─────────────────────────────────────────────────────────────┘
```

## 🔧 Implementation Details

### Service: `PublicSubmissionToCaseService`

**File:** `app/Services/PublicSubmissionToCaseService.php`

**Method:** `convertToCase(PublicSubmission, int $submitterId): CaseModel`

**Proses:**
1. Create CaseModel dengan data dari PublicSubmission
2. Copy documents dari PublicSubmissionDocument → Document
3. Map document types (KTP_SUAMI → KTP, etc.)
4. Set `source_type = 'public'`
5. Link case_id ke public_submission.case_id
6. Update PublicSubmission status → 'APPROVED'

### Route

**File:** `routes/web.php`

```php
Route::post('/cases/from-public/{publicSubmission}', 
    [DashboardController::class, 'createFromPublicSubmission'])
    ->name('cases.from-public');
```

**Permission:** `role:pa_assistant`

### Controller Method

**File:** `app/Http/Controllers/Web/DashboardController.php`

```php
public function createFromPublicSubmission(PublicSubmission $submission)
{
    // Validasi: user adalah PA Assistant
    // Konversi menggunakan PublicSubmissionToCaseService
    // Redirect ke case detail page
}
```

### Dashboard Controller Updates

**Method:** `cases()`
- Query PublicSubmission dengan status PENDING, REVIEWING
- Gabungkan dengan CaseModel dalam satu tampilan
- Filter by type: 'cases', 'public', atau 'all'
- Count statistics untuk masing-masing

**Method:** `buildStats()`
- Include public submission counts dalam statistics

## 👁️ UI/UX Changes

### Cases Table (Component: `cases-table.blade.php`)

**Kolom Baru:** "Sumber"
- Badge "Pengajuan Publik" (emerald) untuk `source_type='public'`
- Badge "Internal" (blue) untuk `source_type='internal'`
- Icon untuk quick visual identification

### Dashboard Index Page

**Changes:**
- Validation cases table sekarang menampilkan badge source
- Menampilkan public submissions dalam recent items feed

### Cases List Page (`dashboard/cases/index.blade.php`)

**Tab Navigation:**
- "Semua" - Cases + Public Submissions
- "Kasus" - Internal cases only
- "Pengajuan Publik" - Public submissions only

**Status Filters:**
- Public submissions menampilkan status: PENDING, REVIEWING, APPROVED
- Cases menampilkan standard case statuses

## 📝 Usage Examples

### Untuk PA Assistant

1. **Lihat Pengajuan Publik:**
   - Navigate ke Dashboard > Kasus
   - Klik tab "Pengajuan Publik"
   - Lihat daftar public submissions dengan badge

2. **Konversi ke Kasus:**
   - Buka detail pengajuan publik
   - Klik tombol "Konversi ke Kasus"
   - Sistem auto-create CaseModel
   - Redirect ke case detail page

3. **Buat Kasus Manual:**
   - Tetap bisa buat kasus langsung melalui form
   - Kasus ini akan punya `source_type='internal'`

### Untuk PA Management

1. **Review Cases dari Publik:**
   - Filter/tab dapat memilih "Pengajuan Publik"
   - OCR review workflow sama seperti case internal
   - Badge menunjukkan asal case

### Untuk Disdukcapil Staff

1. **Validasi Cases dari Publik:**
   - Kasus publik tampil dalam queue standard
   - Badge menunjukkan asal submission
   - Validasi process sama dengan internal

## 🔒 Permission & Security

- **Create case from public:** Hanya PA Assistant & PA Management
- **View public submissions:** PA Assistant, PA Management, Disdukcapil Staff, PA Staff
- **Convert permission:** Checked in controller method
- **Foreign key constraint:** `ON DELETE SET NULL` - case tetap ada jika submission dihapus

## 🐛 Error Handling

**Conversion Failures:**
- Log error ke `laravel.log`
- User-friendly error message
- Redirect back dengan session error

**Validation:**
- Check if already converted (`submission.case_id` exists)
- Check user permissions
- Validate data integrity

## 📚 Related Models & Methods

```
PublicSubmission
├─ generatedCase() → CaseModel (inverse relation)
├─ case() → CaseModel (original relation for PA-created)
├─ documents() → PublicSubmissionDocument
└─ institution() → Institution

CaseModel
├─ sourcePublicSubmission() → PublicSubmission
├─ publicSubmission() → PublicSubmission
└─ documents() → Document
    └─ MapTo: document_types KTP_SUAMI=KTP, etc.
```

## Testing Checklist

- [ ] Create public submission via form
- [ ] Verify appears in PA Assistant dashboard
- [ ] Verify badge shows "Pengajuan Publik"
- [ ] Convert to case successfully
- [ ] Verify case has `source_type='public'`
- [ ] Verify documents copied
- [ ] Test PA Management review workflow
- [ ] Test Disdukcapil validation workflow
- [ ] Test error handling (already converted, etc.)
- [ ] Verify permissions (only PA Assistant can convert)
