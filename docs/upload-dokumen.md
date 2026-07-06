# Mekanisme Upload Dokumen

Dokumen di-upload melalui dua jalur utama: **form pengajuan publik** dan **API**. Kedua jalur menyimpan file ke storage dan membuat record di database, kemudian memicu proses OCR secara otomatis.

---

## Aliran Data (Flow)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  JALUR PUBLIK (Form Pengajuan)          JALUR API (PA Staff/Management)     │
│  Web/PublicSubmissionController          API/DocumentController             │
└──────────┬─────────────────────────────────────┬───────────────────────────┘
           │                                     │
           ▼                                     ▼
    ┌──────────────┐                      ┌──────────────┐
    │ File Upload │                      │ File Upload  │
    │ Validation  │                      │ Validation   │
    └──────┬───────┘                      └──────┬───────┘
           │                                     │
           ▼                                     ▼
    ┌──────────────────┐                 ┌──────────────────┐
    │ Storage: public  │                 │ Storage: local   │
    │ public_submis... │                 │ documents/{id}/  │
    └──────┬───────────┘                 └──────┬───────────┘
           │                                     │
           ▼                                     ▼
    ┌──────────────────┐                 ┌──────────────────┐
    │ PublicSubmission  │                 │ Document         │
    │ Document         │                 │ (Case Model)     │
    │ created          │                 │ created          │
    └──────┬───────────┘                 └──────┬───────────┘
           │                                     │
           │           ┌─────────────────────────┘
           │           │
           ▼           ▼
    ┌──────────────────────────────────┐
    │ DocumentUploaded Event (fired)   │
    └──────────────┬───────────────────┘
                   │
                   ▼
    ┌──────────────────────────────────┐
    │ ProcessOcrAfterUpload Listener  │
    │ Cek: apakah tipe dokumen KTP?   │
    └──────────────┬───────────────────┘
                   │
                   ▼
    ┌──────────────────────────────────┐
    │ OCRService::dispatch()           │
    │ Queue job ke Redis (queue:ocr)  │
    └──────────────┬───────────────────┘
                   │
                   ▼
    ┌──────────────────────────────────┐
    │ OCRJob / ProcessPublic...Ocr   │
    │ Kirim ke OCR Service Flask     │
    │ Parse hasil → Simpan OcrResult │
    └──────────────────────────────────┘
```

---

## Dua Jalur Upload

### 1. Jalur Publik (Form Pengajuan)

**Controller:** `app/Http/Controllers/Web/PublicSubmissionController.php`

Warga mengakses form pengajuan di `/pengajuan` tanpa login. Form mengumpulkan data suami-istri + dokumen, kemudian:

```php
// store() method
$files = $request->file('documents', []);  // ['KTP_SUAMI' => UploadedFile, ...]
$submission = $this->service->create($validated, $files, $request);
```

**Validasi:**

| Field | Rule |
|-------|------|
| KTP_SUAMI | Wajib, `jpg,jpeg,png,pdf`, max 5MB |
| KTP_ISTRI | Wajib, `jpg,jpeg,png,pdf`, max 5MB |
| Dokumen lain | Opsional, `jpg,jpeg,png,pdf`, max 5MB |

**Penyimpanan file:**

```php
$path = $file->storeAs(
    'public_submissions/' . $submission->id,
    $docType . '_' . Str::uuid() . '.' . $extension,
    'public'  // disk public — bisa diakses via /storage/
);
```

**Model target:** `PublicSubmissionDocument` (`public_submission_id`)

---

### 2. Jalur API (PA Staff / Management)

**Controller:** `app/Http/Controllers/API/DocumentController.php`

PA staff upload dokumen ke case yang sudah ada via endpoint `POST /api/documents/upload`.

```php
// upload() method
$storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
$path = $file->storeAs(
    "documents/{$case->id}",
    $storedName,
    config('documents.disk', 'local')
);
```

**Validasi:**

| Field | Rule |
|-------|------|
| case_id | Wajib, harus ada di database |
| document_type | Wajib, enum: KTP, KTP_SUAMI, KTP_ISTRI, KK, AKTA_CERAI, dll |
| file | Wajib, `jpeg,png,pdf,tiff`, max 10MB |

**Model target:** `Document` (`case_id`)

---

## Simpan ke Database

### Public Submission (Jalur Publik)

1. Buat `PublicSubmission` record
2. Simpan dokumen ke `PublicSubmissionDocument`
3. Buat `CaseModel` resmi (auto-submit) dan mirror dokumen ke `Document`
4. Fire `DocumentUploaded` event untuk setiap dokumen yang di-mirror
5. IntegrationQueue entry untuk sinkronisasi Neo4j

### Case Document (Jalur API)

1. Buat `Document` record dengan metadata lengkap
2. Fire `DocumentUploaded` event langsung
3. IntegrationQueue entry untuk Neo4j

---

## Integrasi Neo4j

Setiap kali dokumen dibuat/diubah/dihapus, `Document` model otomatis membuat entry di `integration_queues`:

```php
// Document.php boot()
static::created(function ($model) {
    IntegrationQueue::create([
        'aggregate_type' => 'Document',
        'aggregate_id'   => $model->id,
        'event_type'     => 'created',
        'payload'        => ['case_id' => $model->case_id, 'document_type' => $model->document_type],
        'available_at'   => now(),
    ]);
});
```

---

## Proses OCR Otomatis

### Event-Driven Architecture

```
DocumentUploaded Event
    └── ProcessOcrAfterUpload Listener
            └── OCRService::dispatch()
                    └── Queue Job (ocr)
                            └── OCR Service (Flask)
                                    └── Simpan OcrResult
```

### Listener: `ProcessOcrAfterUpload`

**File:** `app/Listeners/ProcessOcrAfterUpload.php`

Hanya memproses dokumen **KTP**:

```php
$processableTypes = ['KTP', 'KTP_SUAMI', 'KTP_ISTRI'];

if (!in_array($document->document_type, $processableTypes)) {
    return;  // Skip non-KTP
}

$this->ocrService->dispatch($document);
```

### Queue Job

| Jalur | Job Class |
|-------|-----------|
| Case (API) | `OCRJob` via `OCRService::dispatch()` |
| Public Submission | `ProcessPublicSubmissionOcr` |

**Retry policy:** 3x dengan backoff [10s, 30s, 60s]

---

## Konfigurasi Storage

### Disk Configuration (`config/filesystems.php`)

```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
    ],
]
```

### Environment Variables

```env
# Laravel
FILESYSTEM_DISK=local

# Default document disk (API uploads)
DOCUMENTS_DISK=local

# OCR Service
OCR_SERVICE_URL=http://localhost:5001
```

---

## Tipe Dokumen

### Public Submission Document Types

**Wajib:**
- `KTP_SUAMI` - KTP，丈夫（husband）
- `KTP_ISTRI` - KTP，妻子（wife）

**Opsional berdasarkan cerai_type:**
- `KK` - Kartu Keluarga
- `AKTA_CERAI` - Akta Cerai
- `PUTUSAN_PA` - Putusan Pengadilan Agama
- `AKTA_NIKAH` - Akta Nikah / Buku Nikah
- `SURAT_PENGANTAR` - Surat Pengantar RT/RW
- `AKTA_KEMATIAN` - Akta Kematian (cerai_mati)
- `SURAT_PINDAH` - Surat Pindah (cerai_pindah)
- `SURAT_KETERANGAN_GHAIB` - Surat Keterangan Ghaib (cerai_ghaib)
- `AKTA_KELAHIRAN_ANAK` - Akta Kelahiran Anak (cerai_hak_asuh)

### Case Document Types (Schema Mapping)

Public document types di-map ke case document types via `DocumentTypeMapper`:
- `KTP_SUAMI` → `KTP_SUAMI`
- `KTP_ISTRI` → `KTP_ISTRI`
- `KK` → `KK`
- `AKTA_CERAI` → `AKTA_CERAI`
- dll.

---

## Rate Limiting (Pengajuan Publik)

| Aturan | Nilai |
|--------|-------|
| Maks pengajuan per NIK | 3 kali |
| Periode | 7 hari |
| Cek terhadap | nik_suami DAN nik_istri |

**NIK dibekukan** jika ada pengajuan dengan status: `REVIEWING`, `WAITING_OCR`, `APPROVED`

---

## Struktur File Storage

### Public Submissions

```
storage/app/public/
└── public_submissions/
    └── {submission_id}/
        ├── KTP_SUAMI_xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.jpg
        ├── KTP_ISTRI_yyyyyyyy-yyyy-yyyy-yyyy-yyyyyyyyyyyy.png
        └── AKTA_CERAI_zzzzzzzz-zzzz-zzzz-zzzz-zzzzzzzzzzzz.pdf
```

### Case Documents (API)

```
storage/app/
├── documents/
│   └── {case_id}/
│       ├── xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.jpg
│       └── ...
└── public/          ← symlink ke ../public untuk /storage/
```

---

## Error Handling

| Skenario | Penanganan |
|----------|------------|
| File tidak valid | ValidationException, 422 |
| Kuota NIK habis | RuntimeException, dialihkan ke halaman error |
| Storage gagal | Log warning, tetap buat record (file belum tersimpan) |
| OCR service down | Job retry 3x, gagal = remain in queue |
| File tidak ditemukan saat OCR | Skip, log warning |

---

## Route Summary

| Method | Endpoint | Controller | Fungsi |
|--------|----------|-----------|--------|
| GET | `/pengajuan` | Web/PublicSubmissionController | Form pengajuan publik |
| POST | `/pengajuan` | Web/PublicSubmissionController@store | Submit pengajuan + upload |
| GET | `/api/submissions/check-nik` | API/PublicSubmissionController | Cek kuota NIK |
| POST | `/api/documents/upload` | API/DocumentController | Upload ke case |
| GET | `/api/documents/{id}` | API/DocumentController | Detail dokumen |
| GET | `/api/documents/download/{id}` | API/DocumentController | Download dokumen |
