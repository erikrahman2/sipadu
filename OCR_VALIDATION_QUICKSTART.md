# Quick Start: Implementasi Validasi OCR

> Setup cepat sistem validasi otomatis OCR vs Input Manual

---

## 1. Migration (5 menit)

```bash
# Buat migration
php artisan make:migration create_ocr_validations_table

# Jalankan migration
php artisan migrate

# Verifikasi
php artisan db:show
```

**Schema:**
- Tabel baru: `ocr_validations`
- Update: `ocr_results.has_validation` (boolean)

---

## 2. Model (2 menit)

Buat `app/Models/OcrValidation.php`:

```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OcrValidation extends Model
{
    protected $fillable = [
        'ocr_result_id', 'case_id', 'public_submission_id', 'document_id',
        'comparison_results', 'overall_match_score', 'validation_status',
        'is_reviewed', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];
    
    protected $casts = [
        'comparison_results' => 'array',
        'overall_match_score' => 'decimal:2',
        'is_reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];
    
    public function ocrResult() { return $this->belongsTo(OcrResult::class); }
    public function case() { return $this->belongsTo(CaseModel::class); }
    public function document() { return $this->belongsTo(Document::class); }
}
```

---

## 3. Service (10 menit)

Buat `app/Services/OCRValidationService.php`:

**Key Methods:**
- `getInputData()` – Ambil data dari Case/PublicSubmission
- `compare(OcrResult $ocrResult)` – Bandingkan & generate validation
- `normalize()` – String normalization
- `calculateSimilarity()` – Levenshtein distance

---

## 4. Update OCRService (5 menit)

Edit `app/Services/OCRService.php`, method `process()`:

```php
public function process(Document $document): OcrResult
{
    // ... existing OCR processing ...
    
    $result = $this->persistResult($document, $payload, $startTime);
    
    // ✨ AUTO-VALIDATION
    if ($document->case_id || $document->public_submission_id) {
        $validationService = app(OCRValidationService::class);
        $validation = $validationService->compare($result);
        $result->update(['has_validation' => true]);
    }
    
    return $result;
}
```

---

## 5. Event Listener (5 menit)

Buat `app/Listeners/ProcessOcrAfterUpload.php`:

```php
<?php
namespace App\Listeners;

use App\Events\DocumentUploaded;
use App\Services\OCRService;

class ProcessOcrAfterUpload
{
    public function __construct(private OCRService $ocrService) {}
    
    public function handle(DocumentUploaded $event): void
    {
        $document = $event->document;
        
        if (in_array($document->document_type, ['KTP', 'KK', 'AKTA_KELAHIRAN'])) {
            $this->ocrService->dispatch($document);
        }
    }
}
```

Register di `EventServiceProvider`:

```php
protected $listen = [
    \App\Events\DocumentUploaded::class => [
        \App\Listeners\ProcessOcrAfterUpload::class,
    ],
];
```

---

## 6. Controller (10 menit)

Buat `app/Http/Controllers/Web/ReviewController.php`:

```php
<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\OcrValidation;

class ReviewController extends Controller
{
    public function show(int $id)
    {
        $case = CaseModel::with('documents.ocrResult.validation')->findOrFail($id);
        $validations = OcrValidation::where('case_id', $id)
            ->with('document', 'ocrResult')
            ->get();
        
        return view('dashboard.review.show', compact('case', 'validations'));
    }
    
    public function validateOcr(Request $request, int $id)
    {
        $validation = OcrValidation::findOrFail($request->validation_id);
        
        $validation->update([
            'is_reviewed' => true,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $request->notes,
        ]);
        
        // Update case status based on action
        // ...
        
        return redirect()->back()->with('success', 'Validasi berhasil diproses');
    }
}
```

---

## 7. Routes (2 menit)

Tambahkan di `routes/web.php`:

```php
Route::middleware(['auth', 'role:pa_management|super_admin'])
    ->prefix('dashboard')
    ->group(function () {
        Route::get('/review/cases/{id}', [ReviewController::class, 'show'])
            ->name('dashboard.review.show');
        Route::post('/review/cases/{id}/validate', [ReviewController::class, 'validateOcr'])
            ->name('dashboard.review.validate');
    });
```

---

## 8. View (15 menit)

Buat `resources/views/dashboard/review/show.blade.php`:

**Komponen utama:**
- Card header dengan overall score
- Progress bar match score
- Tabel perbandingan field-by-field
- Action buttons (Approve/Reject/Request Correction)
- Modals untuk reject/correction

**Tampilan:**
```
┌────────────────────────────────────────┐
│ 🔍 Validasi OCR vs Input Manual        │
├────────────────────────────────────────┤
│ Overall Match: 95% ████████████████░░ │
│                                        │
│ ┌──────────┬────────────┬────────────┐│
│ │ Field    │ Input      │ OCR Result ││
│ ├──────────┼────────────┼────────────┤│
│ │ NIK      │ 3174...001 │ 3174...001 ││
│ │ Nama     │ AHMAD...   │ AHMAD...   ││
│ └──────────┴────────────┴────────────┘│
│                                        │
│ [✓ Approve] [✗ Reject] [📝 Correction]│
└────────────────────────────────────────┘
```

---

## 9. Testing (10 menit)

```bash
# Unit test
php artisan make:test OCRValidationServiceTest --unit

# Feature test
php artisan make:test OCRValidationControllerTest

# Run tests
php artisan test --filter OCRValidation
```

**Test cases:**
- Perfect match (100%)
- Partial match (80-94%)
- Mismatch (<80%)
- NIK mismatch (always MISMATCH)

---

## 10. Deployment Checklist

- [ ] Migration dijalankan: `php artisan migrate`
- [ ] Cache di-clear: `php artisan config:clear`
- [ ] Queue worker running: `php artisan queue:work --queue=ocr`
- [ ] OCR service running: `curl http://localhost:5001/health`
- [ ] Test upload dokumen → auto OCR
- [ ] Test validation muncul di dashboard
- [ ] Test approve/reject workflow
- [ ] Monitor logs: `tail -f storage/logs/laravel.log`

---

## Troubleshooting

**❌ OCR tidak auto-trigger:**
```bash
# Cek event listener registered
php artisan event:list | grep DocumentUploaded

# Cek queue
php artisan queue:monitor ocr
```

**❌ Validation tidak muncul:**
```bash
# Cek has_validation flag
php artisan tinker
>>> Document::find(1)->ocrResult->has_validation
```

**❌ Similarity score selalu 0:**
```bash
# Test normalization
php artisan tinker
>>> $service = app(\App\Services\OCRValidationService::class);
>>> $service->calculateSimilarity('AHMAD WARGA', 'AHMAD WARGA')
# Expected: 1.0
```

---

**Total Waktu Implementasi:** ~1 jam (tanpa testing)  
**Dokumentasi Lengkap:** [OCR_VALIDATION_DESIGN.md](OCR_VALIDATION_DESIGN.md)

