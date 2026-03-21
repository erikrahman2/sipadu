# Sistem Validasi Otomatis OCR vs Input Manual

> **SiPadu – Pembaruan Dokumen Pasca Perceraian**  
> Dokumentasi Teknis: OCR Auto-Validation System  
> Dibuat: 11 Maret 2026

---

## 1. Overview

Sistem validasi otomatis membandingkan data yang diinput manual (dari form PA Assistant atau Pengajuan Publik) dengan hasil ekstraksi OCR dari dokumen yang diunggah. Tujuannya adalah:

1. **Deteksi Kesalahan Input** – Identifikasi typo atau kesalahan input manual
2. **Validasi Identitas** – Pastikan dokumen yang diupload sesuai dengan data pemohon
3. **Quality Control** – PA Management dapat memverifikasi akurasi sebelum approve
4. **Audit Trail** – Catat semua perbedaan untuk keperluan investigasi

---

## 2. Workflow Otomatis

### 2.1 Alur Proses

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. INPUT DATA MANUAL                                            │
├─────────────────────────────────────────────────────────────────┤
│  PA Assistant / Pengaju Publik mengisi form:                    │
│  - NIK Pemohon: 3174010101900001                                │
│  - Nama: AHMAD WARGA                                            │
│  - Tempat Lahir: JAKARTA                                        │
│  - Tanggal Lahir: 01-01-1990                                    │
│  - Alamat: JL SUDIRMAN NO 123                                   │
└─────────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. UPLOAD DOKUMEN                                               │
├─────────────────────────────────────────────────────────────────┤
│  Upload KTP (wajib), KK (opsional), dll                         │
│  Status: PENDING                                                │
└─────────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. OCR AUTO-TRIGGER                                             │
├─────────────────────────────────────────────────────────────────┤
│  Event Listener (DocumentUploaded):                             │
│  - Dispatch OCRJob ke queue                                     │
│  - Document.status → PROCESSING                                 │
└─────────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. OCR PROCESSING                                               │
├─────────────────────────────────────────────────────────────────┤
│  Python Microservice:                                           │
│  - Preprocessing (OpenCV)                                       │
│  - Tesseract extraction                                         │
│  - Field detection (NIK, nama, dll)                             │
│  - Confidence scoring                                           │
└─────────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5. AUTO-COMPARISON                                              │
├─────────────────────────────────────────────────────────────────┤
│  OCRValidationService:                                          │
│  - Ambil data input dari Case/PublicSubmission                  │
│  - Bandingkan dengan OcrResult                                  │
│  - Hitung similarity score (Levenshtein distance)               │
│  - Generate comparison report                                   │
│  - Save ke ocr_validations table                                │
└─────────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ 6. DASHBOARD PA MANAGEMENT                                      │
├─────────────────────────────────────────────────────────────────┤
│  Tampilan perbandingan:                                         │
│                                                                 │
│  ┌───────────────────┬───────────────────┬──────────┬─────────┐│
│  │ Field             │ Input Manual      │ Hasil OCR│ Status  ││
│  ├───────────────────┼───────────────────┼──────────┼─────────┤│
│  │ NIK               │ 3174010101900001  │ 31740... │ ✅ Match││
│  │ Nama              │ AHMAD WARGA       │ AHMAD... │ ✅ Match││
│  │ Tempat Lahir      │ JAKARTA           │ JAKARTA  │ ✅ Match││
│  │ Tanggal Lahir     │ 01-01-1990        │ 01-01... │ ✅ Match││
│  │ Alamat            │ JL SUDIRMAN NO 123│ JL SUDI..│ ⚠️  95% ││
│  └───────────────────┴───────────────────┴──────────┴─────────┘│
│                                                                 │
│  Overall Match Score: 98%                                       │
│  Confidence: HIGH                                               │
│                                                                 │
│  [✓ Approve] [✗ Reject] [📝 Request Correction]                │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Database Schema

### 3.1 Tabel Baru: `ocr_validations`

```sql
CREATE TABLE ocr_validations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Relasi
    ocr_result_id BIGINT UNSIGNED NOT NULL,
    case_id BIGINT UNSIGNED NULL,
    public_submission_id BIGINT UNSIGNED NULL,
    document_id BIGINT UNSIGNED NOT NULL,
    
    -- Data Input Manual (snapshot saat validasi)
    input_nik VARCHAR(16) NULL,
    input_nama VARCHAR(255) NULL,
    input_tempat_lahir VARCHAR(255) NULL,
    input_tgl_lahir DATE NULL,
    input_alamat TEXT NULL,
    input_rt_rw VARCHAR(10) NULL,
    input_kelurahan VARCHAR(255) NULL,
    input_kecamatan VARCHAR(255) NULL,
    input_no_kk VARCHAR(16) NULL,
    
    -- Data OCR (snapshot)
    ocr_nik VARCHAR(16) NULL,
    ocr_nama VARCHAR(255) NULL,
    ocr_tempat_lahir VARCHAR(255) NULL,
    ocr_tgl_lahir VARCHAR(50) NULL,
    ocr_alamat TEXT NULL,
    ocr_rt_rw VARCHAR(10) NULL,
    ocr_kelurahan VARCHAR(255) NULL,
    ocr_kecamatan VARCHAR(255) NULL,
    ocr_no_kk VARCHAR(16) NULL,
    
    -- Comparison Results
    comparison_results JSON NOT NULL COMMENT 'Field-by-field comparison: {field: {match: bool, similarity: float, input: str, ocr: str, confidence: float}}',
    
    -- Overall Metrics
    overall_match_score DECIMAL(5,2) NOT NULL COMMENT 'Percentage 0-100',
    fields_matched INT UNSIGNED DEFAULT 0,
    fields_total INT UNSIGNED DEFAULT 0,
    
    -- Status
    validation_status ENUM('MATCH', 'PARTIAL_MATCH', 'MISMATCH', 'MANUAL_REVIEW') NOT NULL,
    is_reviewed BOOLEAN DEFAULT FALSE,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT NULL,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (ocr_result_id) REFERENCES ocr_results(id) ON DELETE CASCADE,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    FOREIGN KEY (public_submission_id) REFERENCES public_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_case_id (case_id),
    INDEX idx_public_submission_id (public_submission_id),
    INDEX idx_validation_status (validation_status),
    INDEX idx_is_reviewed (is_reviewed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 Update Tabel `ocr_results`

Tambahkan kolom untuk tracking validation:

```sql
ALTER TABLE ocr_results ADD COLUMN has_validation BOOLEAN DEFAULT FALSE AFTER is_validated;
ALTER TABLE ocr_results ADD INDEX idx_has_validation (has_validation);
```

---

## 4. Service Layer

### 4.1 OCRValidationService

```php
<?php
// app/Services/OCRValidationService.php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\OcrResult;
use App\Models\OcrValidation;
use App\Models\PublicSubmission;

class OCRValidationService
{
    /**
     * Ambil data input manual dari Case atau PublicSubmission
     */
    public function getInputData($caseId = null, $publicSubmissionId = null): array
    {
        if ($caseId) {
            $case = CaseModel::findOrFail($caseId);
            return [
                'nik'           => $case->petitioner_nik,
                'nama'          => $case->petitioner_name,
                'tempat_lahir'  => $case->petitioner_birth_place ?? null,
                'tgl_lahir'     => $case->petitioner_birth_date ?? null,
                'alamat'        => $case->petitioner_address ?? null,
                'no_kk'         => $case->petitioner_kk ?? null,
                // ... field lain
            ];
        }
        
        if ($publicSubmissionId) {
            $submission = PublicSubmission::findOrFail($publicSubmissionId);
            return [
                'nik'           => $submission->nik,
                'nama'          => $submission->petitioner_name,
                'tempat_lahir'  => $submission->petitioner_birth_place ?? null,
                'tgl_lahir'     => $submission->petitioner_birth_date ?? null,
                'alamat'        => $submission->petitioner_address ?? null,
                'no_kk'         => $submission->petitioner_kk ?? null,
            ];
        }
        
        return [];
    }
    
    /**
     * Bandingkan data input vs OCR
     */
    public function compare(OcrResult $ocrResult): OcrValidation
    {
        // Ambil data input original
        $inputData = $this->getInputData(
            $ocrResult->case_id,
            $ocrResult->public_submission_id
        );
        
        // Fields yang akan dibandingkan
        $fieldsToCompare = [
            'nik', 'nama', 'tempat_lahir', 'tgl_lahir',
            'alamat', 'rt_rw', 'kelurahan', 'kecamatan', 'no_kk'
        ];
        
        $comparisonResults = [];
        $matchedCount = 0;
        $totalFields = 0;
        
        foreach ($fieldsToCompare as $field) {
            $inputValue = $inputData[$field] ?? null;
            $ocrValue = $this->getOcrFieldValue($ocrResult, $field);
            
            // Skip jika kedua field kosong
            if (empty($inputValue) && empty($ocrValue)) {
                continue;
            }
            
            $totalFields++;
            
            // Normalisasi untuk perbandingan
            $inputNormalized = $this->normalize($inputValue);
            $ocrNormalized = $this->normalize($ocrValue);
            
            // Hitung similarity
            $similarity = $this->calculateSimilarity($inputNormalized, $ocrNormalized);
            $isMatch = $similarity >= 0.90; // 90% threshold
            
            if ($isMatch) {
                $matchedCount++;
            }
            
            $comparisonResults[$field] = [
                'input'      => $inputValue,
                'ocr'        => $ocrValue,
                'similarity' => round($similarity, 4),
                'match'      => $isMatch,
                'confidence' => $ocrResult->confidence_scores[$field] ?? 0,
            ];
        }
        
        // Hitung overall match score
        $overallScore = $totalFields > 0
            ? round(($matchedCount / $totalFields) * 100, 2)
            : 0;
        
        // Tentukan validation status
        $validationStatus = $this->determineValidationStatus($overallScore, $comparisonResults);
        
        // Simpan hasil validasi
        return OcrValidation::updateOrCreate(
            [
                'ocr_result_id' => $ocrResult->id,
            ],
            [
                'case_id'               => $ocrResult->case_id,
                'public_submission_id'  => $ocrResult->public_submission_id,
                'document_id'           => $ocrResult->document_id,
                
                // Snapshot input
                'input_nik'             => $inputData['nik'] ?? null,
                'input_nama'            => $inputData['nama'] ?? null,
                'input_tempat_lahir'    => $inputData['tempat_lahir'] ?? null,
                'input_tgl_lahir'       => $inputData['tgl_lahir'] ?? null,
                'input_alamat'          => $inputData['alamat'] ?? null,
                'input_rt_rw'           => $inputData['rt_rw'] ?? null,
                'input_kelurahan'       => $inputData['kelurahan'] ?? null,
                'input_kecamatan'       => $inputData['kecamatan'] ?? null,
                'input_no_kk'           => $inputData['no_kk'] ?? null,
                
                // Snapshot OCR
                'ocr_nik'               => $ocrResult->nik,
                'ocr_nama'              => $ocrResult->nama,
                'ocr_tempat_lahir'      => $ocrResult->tempat_lahir,
                'ocr_tgl_lahir'         => $ocrResult->tgl_lahir,
                'ocr_alamat'            => $ocrResult->alamat,
                'ocr_rt_rw'             => $ocrResult->rt_rw,
                'ocr_kelurahan'         => $ocrResult->kelurahan,
                'ocr_kecamatan'         => $ocrResult->kecamatan,
                'ocr_no_kk'             => $ocrResult->no_kk,
                
                // Results
                'comparison_results'    => $comparisonResults,
                'overall_match_score'   => $overallScore,
                'fields_matched'        => $matchedCount,
                'fields_total'          => $totalFields,
                'validation_status'     => $validationStatus,
            ]
        );
    }
    
    /**
     * Normalisasi string untuk perbandingan
     */
    private function normalize(?string $value): string
    {
        if (empty($value)) {
            return '';
        }
        
        // Uppercase, hapus spasi berlebih, hapus tanda baca
        $normalized = strtoupper(trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = preg_replace('/[^A-Z0-9\s]/', '', $normalized);
        
        return $normalized;
    }
    
    /**
     * Hitung similarity menggunakan Levenshtein distance
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }
        
        if (empty($str1) || empty($str2)) {
            return 0.0;
        }
        
        $maxLen = max(strlen($str1), strlen($str2));
        $distance = levenshtein($str1, $str2);
        
        return 1 - ($distance / $maxLen);
    }
    
    /**
     * Tentukan validation status berdasarkan score
     */
    private function determineValidationStatus(float $score, array $results): string
    {
        // Jika NIK tidak match, selalu MISMATCH
        if (isset($results['nik']) && !$results['nik']['match']) {
            return 'MISMATCH';
        }
        
        if ($score >= 95) {
            return 'MATCH';
        } elseif ($score >= 80) {
            return 'PARTIAL_MATCH';
        } elseif ($score >= 60) {
            return 'MANUAL_REVIEW';
        } else {
            return 'MISMATCH';
        }
    }
    
    /**
     * Ambil nilai field dari OcrResult
     */
    private function getOcrFieldValue(OcrResult $ocrResult, string $field): ?string
    {
        return match($field) {
            'nik' => $ocrResult->nik,
            'nama' => $ocrResult->nama,
            'tempat_lahir' => $ocrResult->tempat_lahir,
            'tgl_lahir' => $ocrResult->tgl_lahir,
            'alamat' => $ocrResult->alamat,
            'rt_rw' => $ocrResult->rt_rw,
            'kelurahan' => $ocrResult->kelurahan,
            'kecamatan' => $ocrResult->kecamatan,
            'no_kk' => $ocrResult->no_kk,
            default => null,
        };
    }
}
```

### 4.2 Update OCRService

```php
<?php
// app/Services/OCRService.php (tambahkan di method process)

public function process(Document $document): OcrResult
{
    // ... existing code ...
    
    try {
        $filePath = Storage::disk($document->disk)->path($document->path);
        $payload  = $this->callMicroservice($filePath, $document->mime_type);
        
        $result = $this->persistResult($document, $payload, $startTime);
        
        // ✨ AUTO-VALIDATION (NEW)
        if ($document->case_id || $document->public_submission_id) {
            $validationService = app(OCRValidationService::class);
            $validation = $validationService->compare($result);
            
            $result->update(['has_validation' => true]);
            
            Log::channel('ocr')->info('OCR validation completed', [
                'document_id'        => $document->id,
                'validation_status'  => $validation->validation_status,
                'match_score'        => $validation->overall_match_score,
            ]);
        }
        
        $job->update(['status' => 'DONE', 'finished_at' => now(), 'result_payload' => $payload]);
        $document->update(['status' => 'PROCESSED']);
        
        return $result;
        
    } catch (\Throwable $e) {
        // ... existing error handling ...
    }
}
```

---

## 5. Dashboard PA Management

### 5.1 Route

```php
// routes/web.php

Route::middleware(['auth', 'role:pa_management|super_admin'])->prefix('dashboard')->group(function () {
    Route::get('/review/cases', [ReviewController::class, 'index'])->name('dashboard.review.index');
    Route::get('/review/cases/{id}', [ReviewController::class, 'show'])->name('dashboard.review.show');
    Route::post('/review/cases/{id}/validate', [ReviewController::class, 'validateOcr'])->name('dashboard.review.validate');
});
```

### 5.2 Controller

```php
<?php
// app/Http/Controllers/Web/ReviewController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\OcrValidation;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function show(int $id)
    {
        $case = CaseModel::with([
            'documents.ocrResult.validation',
            'petitioner',
            'institution'
        ])->findOrFail($id);
        
        // Ambil semua validasi OCR untuk kasus ini
        $validations = OcrValidation::where('case_id', $id)
            ->with('document', 'ocrResult')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('dashboard.review.show', compact('case', 'validations'));
    }
    
    public function validateOcr(Request $request, int $id)
    {
        $request->validate([
            'validation_id' => 'required|exists:ocr_validations,id',
            'action'        => 'required|in:approve,reject,request_correction',
            'notes'         => 'nullable|string|max:1000',
        ]);
        
        $validation = OcrValidation::findOrFail($request->validation_id);
        
        $validation->update([
            'is_reviewed' => true,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $request->notes,
        ]);
        
        // Update case status berdasarkan action
        $case = CaseModel::findOrFail($id);
        
        if ($request->action === 'approve') {
            // Lanjutkan ke workflow berikutnya
            return redirect()
                ->route('dashboard.review.show', $id)
                ->with('success', 'Validasi OCR disetujui. Kasus dapat diproses lebih lanjut.');
        }
        
        if ($request->action === 'reject') {
            $case->update(['status' => 'NEEDS_REVISION']);
            return redirect()
                ->route('dashboard.review.show', $id)
                ->with('warning', 'Validasi OCR ditolak. Pemohon akan diminta mengunggah ulang dokumen.');
        }
        
        // request_correction
        return redirect()
            ->route('dashboard.review.show', $id)
            ->with('info', 'Permintaan koreksi dikirim ke PA Assistant.');
    }
}
```

### 5.3 Blade View

```blade
{{-- resources/views/dashboard/review/show.blade.php --}}

@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2>Review Kasus: {{ $case->case_number }}</h2>
            <p class="text-muted">Tracking: {{ $case->tracking_token }}</p>
        </div>
    </div>
    
    {{-- OCR Validation Results --}}
    @if($validations->isNotEmpty())
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">🔍 Validasi OCR vs Input Manual</h5>
                </div>
                <div class="card-body">
                    @foreach($validations as $validation)
                    <div class="validation-item mb-4 pb-4 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong>Dokumen:</strong> {{ $validation->document->document_type }}
                                <span class="text-muted">({{ $validation->document->original_name }})</span>
                            </div>
                            <div>
                                <span class="badge badge-{{ $validation->validation_status === 'MATCH' ? 'success' : ($validation->validation_status === 'PARTIAL_MATCH' ? 'warning' : 'danger') }} badge-lg">
                                    {{ $validation->validation_status }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="metric-box">
                                    <h6>Overall Match Score</h6>
                                    <div class="progress" style="height: 30px;">
                                        <div class="progress-bar bg-{{ $validation->overall_match_score >= 90 ? 'success' : ($validation->overall_match_score >= 70 ? 'warning' : 'danger') }}" 
                                             role="progressbar" 
                                             style="width: {{ $validation->overall_match_score }}%"
                                             aria-valuenow="{{ $validation->overall_match_score }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <strong>{{ number_format($validation->overall_match_score, 1) }}%</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="metric-box">
                                    <h6>Fields Matched</h6>
                                    <p class="mb-0">
                                        <span class="h4">{{ $validation->fields_matched }}</span> / {{ $validation->fields_total }} fields
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Comparison Table --}}
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="20%">Field</th>
                                        <th width="35%">Input Manual</th>
                                        <th width="35%">Hasil OCR</th>
                                        <th width="10%" class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($validation->comparison_results as $field => $comparison)
                                    <tr class="{{ $comparison['match'] ? 'table-success' : 'table-warning' }}">
                                        <td><strong>{{ str_replace('_', ' ', strtoupper($field)) }}</strong></td>
                                        <td>
                                            <code>{{ $comparison['input'] ?? '-' }}</code>
                                        </td>
                                        <td>
                                            <code>{{ $comparison['ocr'] ?? '-' }}</code>
                                            @if(isset($comparison['confidence']))
                                            <br><small class="text-muted">Confidence: {{ number_format($comparison['confidence'] * 100, 1) }}%</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($comparison['match'])
                                                <i class="fas fa-check-circle text-success" title="Match"></i>
                                                <small class="d-block">{{ number_format($comparison['similarity'] * 100, 0) }}%</small>
                                            @else
                                                <i class="fas fa-exclamation-triangle text-warning" title="Mismatch"></i>
                                                <small class="d-block">{{ number_format($comparison['similarity'] * 100, 0) }}%</small>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        {{-- Review Actions --}}
                        @if(!$validation->is_reviewed)
                        <div class="mt-3">
                            <form action="{{ route('dashboard.review.validate', $case->id) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="validation_id" value="{{ $validation->id }}">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Approve Validation
                                </button>
                            </form>
                            
                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal{{ $validation->id }}">
                                <i class="fas fa-times"></i> Reject
                            </button>
                            
                            <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#correctionModal{{ $validation->id }}">
                                <i class="fas fa-edit"></i> Request Correction
                            </button>
                        </div>
                        
                        {{-- Modals --}}
                        {{-- Reject Modal --}}
                        <div class="modal fade" id="rejectModal{{ $validation->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('dashboard.review.validate', $case->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="validation_id" value="{{ $validation->id }}">
                                        <input type="hidden" name="action" value="reject">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title">Reject Validation</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Alasan Penolakan:</label>
                                                <textarea name="notes" class="form-control" rows="4" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Reject</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        @else
                        <div class="alert alert-info">
                            <strong>Reviewed by:</strong> {{ $validation->reviewer->name ?? 'N/A' }} 
                            at {{ $validation->reviewed_at->format('d M Y H:i') }}
                            @if($validation->review_notes)
                            <br><strong>Notes:</strong> {{ $validation->review_notes }}
                            @endif
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
```

---

## 6. Event Listener (Auto-Trigger)

```php
<?php
// app/Listeners/ProcessOcrAfterUpload.php

namespace App\Listeners;

use App\Events\DocumentUploaded;
use App\Services\OCRService;

class ProcessOcrAfterUpload
{
    private OCRService $ocrService;
    
    public function __construct(OCRService $ocrService)
    {
        $this->ocrService = $ocrService;
    }
    
    public function handle(DocumentUploaded $event): void
    {
        $document = $event->document;
        
        // Hanya proses dokumen identitas (KTP, KK)
        if (!in_array($document->document_type, ['KTP', 'KK', 'AKTA_KELAHIRAN'])) {
            return;
        }
        
        // Dispatch OCR job ke queue
        $this->ocrService->dispatch($document);
    }
}
```

Registrasi listener di `EventServiceProvider`:

```php
<?php
// app/Providers/EventServiceProvider.php

protected $listen = [
    \App\Events\DocumentUploaded::class => [
        \App\Listeners\ProcessOcrAfterUpload::class,
    ],
];
```

---

## 7. Migration

```php
<?php
// database/migrations/2026_03_11_000001_create_ocr_validations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocr_validations', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('ocr_result_id')->constrained()->onDelete('cascade');
            $table->foreignId('case_id')->nullable()->constrained('cases')->onDelete('cascade');
            $table->foreignId('public_submission_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            
            // Input snapshot
            $table->string('input_nik', 16)->nullable();
            $table->string('input_nama')->nullable();
            $table->string('input_tempat_lahir')->nullable();
            $table->date('input_tgl_lahir')->nullable();
            $table->text('input_alamat')->nullable();
            $table->string('input_rt_rw', 10)->nullable();
            $table->string('input_kelurahan')->nullable();
            $table->string('input_kecamatan')->nullable();
            $table->string('input_no_kk', 16)->nullable();
            
            // OCR snapshot
            $table->string('ocr_nik', 16)->nullable();
            $table->string('ocr_nama')->nullable();
            $table->string('ocr_tempat_lahir')->nullable();
            $table->string('ocr_tgl_lahir', 50)->nullable();
            $table->text('ocr_alamat')->nullable();
            $table->string('ocr_rt_rw', 10)->nullable();
            $table->string('ocr_kelurahan')->nullable();
            $table->string('ocr_kecamatan')->nullable();
            $table->string('ocr_no_kk', 16)->nullable();
            
            // Comparison results
            $table->json('comparison_results');
            $table->decimal('overall_match_score', 5, 2);
            $table->unsignedInteger('fields_matched')->default(0);
            $table->unsignedInteger('fields_total')->default(0);
            
            // Status
            $table->enum('validation_status', ['MATCH', 'PARTIAL_MATCH', 'MISMATCH', 'MANUAL_REVIEW']);
            $table->boolean('is_reviewed')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['case_id', 'validation_status']);
            $table->index(['public_submission_id', 'validation_status']);
            $table->index('is_reviewed');
        });
        
        // Update ocr_results table
        Schema::table('ocr_results', function (Blueprint $table) {
            $table->boolean('has_validation')->default(false)->after('is_validated');
            $table->index('has_validation');
        });
    }
    
    public function down(): void
    {
        Schema::table('ocr_results', function (Blueprint $table) {
            $table->dropIndex(['has_validation']);
            $table->dropColumn('has_validation');
        });
        
        Schema::dropIfExists('ocr_validations');
    }
};
```

---

## 8. Model

```php
<?php
// app/Models/OcrValidation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrValidation extends Model
{
    protected $fillable = [
        'ocr_result_id', 'case_id', 'public_submission_id', 'document_id',
        'input_nik', 'input_nama', 'input_tempat_lahir', 'input_tgl_lahir',
        'input_alamat', 'input_rt_rw', 'input_kelurahan', 'input_kecamatan', 'input_no_kk',
        'ocr_nik', 'ocr_nama', 'ocr_tempat_lahir', 'ocr_tgl_lahir',
        'ocr_alamat', 'ocr_rt_rw', 'ocr_kelurahan', 'ocr_kecamatan', 'ocr_no_kk',
        'comparison_results', 'overall_match_score', 'fields_matched', 'fields_total',
        'validation_status', 'is_reviewed', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];
    
    protected $casts = [
        'comparison_results' => 'array',
        'overall_match_score' => 'decimal:2',
        'input_tgl_lahir' => 'date',
        'is_reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];
    
    // Relations
    public function ocrResult(): BelongsTo
    {
        return $this->belongsTo(OcrResult::class);
    }
    
    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class);
    }
    
    public function publicSubmission(): BelongsTo
    {
        return $this->belongsTo(PublicSubmission::class);
    }
    
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
    
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
    
    // Helpers
    public function getStatusBadgeClass(): string
    {
        return match($this->validation_status) {
            'MATCH' => 'success',
            'PARTIAL_MATCH' => 'warning',
            'MISMATCH' => 'danger',
            'MANUAL_REVIEW' => 'info',
        };
    }
    
    public function getMismatchedFields(): array
    {
        return collect($this->comparison_results)
            ->filter(fn($comparison) => !$comparison['match'])
            ->keys()
            ->toArray();
    }
}
```

---

## 9. Testing

### 9.1 Unit Test

```php
<?php
// tests/Unit/Services/OCRValidationServiceTest.php

namespace Tests\Unit\Services;

use App\Models\CaseModel;
use App\Models\OcrResult;
use App\Services\OCRValidationService;
use Tests\TestCase;

class OCRValidationServiceTest extends TestCase
{
    public function test_compare_perfect_match()
    {
        $case = CaseModel::factory()->create([
            'petitioner_nik' => '3174010101900001',
            'petitioner_name' => 'AHMAD WARGA',
        ]);
        
        $ocrResult = OcrResult::factory()->create([
            'case_id' => $case->id,
            'nik' => '3174010101900001',
            'nama' => 'AHMAD WARGA',
        ]);
        
        $service = new OCRValidationService();
        $validation = $service->compare($ocrResult);
        
        $this->assertEquals('MATCH', $validation->validation_status);
        $this->assertEquals(100.0, $validation->overall_match_score);
        $this->assertEquals(2, $validation->fields_matched);
    }
    
    public function test_compare_partial_match()
    {
        $case = CaseModel::factory()->create([
            'petitioner_nik' => '3174010101900001',
            'petitioner_name' => 'AHMAD WARGA',
        ]);
        
        $ocrResult = OcrResult::factory()->create([
            'case_id' => $case->id,
            'nik' => '3174010101900001',  // match
            'nama' => 'AHMAD WARGAS',    // typo
        ]);
        
        $service = new OCRValidationService();
        $validation = $service->compare($ocrResult);
        
        $this->assertContains($validation->validation_status, ['PARTIAL_MATCH', 'MANUAL_REVIEW']);
        $this->assertLessThan(100.0, $validation->overall_match_score);
    }
}
```

---

## 10. Deployment Checklist

- [ ] Jalankan migration: `php artisan migrate`
- [ ] Clear cache: `php artisan config:clear`
- [ ] Test OCR service running: `curl http://localhost:5001/health`
- [ ] Test document upload auto-triggers OCR
- [ ] Verify validation appears in PA Management dashboard
- [ ] Test approve/reject workflow
- [ ] Monitor queue: `php artisan queue:work --queue=ocr`

---

**Status**: ✅ Siap Implementasi  
**Priority**: HIGH  
**Estimated Effort**: 2-3 hari development + 1 hari testing  

