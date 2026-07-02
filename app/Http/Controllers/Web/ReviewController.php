<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\Document;
use App\Models\OcrJob;
use App\Models\OcrResult;
use App\Models\OcrValidation;
use App\Services\OCRService;
use App\Services\WorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function __construct(
        private readonly WorkflowService $workflow
    ) {}

    /**
     * Display list of cases that need review
     */
    public function index(Request $request)
    {
        $query = CaseModel::with([
            'publicSubmission',
            'ocrValidations.document',
            'ocrValidations.reviewer'
        ]);
        
        // Filter by validation status
        if ($request->filled('status')) {
            $query->whereHas('ocrValidations', function ($q) use ($request) {
                $q->where('validation_status', $request->status);
            });
        }
        
        // Filter by case status (e.g., NEEDS_REVISION untuk rejected cases)
        if ($request->filled('case_status')) {
            $query->where('status', $request->case_status);
        }
        
        // Filter by review status
        if ($request->filled('reviewed')) {
            $isReviewed = $request->reviewed === 'done';
            $query->whereHas('ocrValidations', function ($q) use ($isReviewed) {
                $q->where('is_reviewed', $isReviewed);
            });
        }
        
        // Search by NIK or Name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('petitioner_nik', 'LIKE', "%{$search}%")
                  ->orWhere('petitioner_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('publicSubmission', function ($sub) use ($search) {
                      $sub->where('nik', 'LIKE', "%{$search}%")
                          ->orWhere('nama_lengkap', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        // Filter cases that have OCR validations
        $query->whereHas('ocrValidations');
        
        // Order by latest
        $query->orderBy('created_at', 'desc');
        
        $cases = $query->paginate(20);
        
        // Statistics for summary cards
        $stats = [
            'match' => OcrValidation::where('validation_status', 'MATCH')->count(),
            'partial_match' => OcrValidation::where('validation_status', 'PARTIAL_MATCH')->count(),
            'mismatch' => OcrValidation::where('validation_status', 'MISMATCH')->count(),
        ];
        
        return view('dashboard.review.index', compact('cases', 'stats'));
    }

    /**
     * Display all data from all cases (for PA Management reporting)
     */
    public function allData(Request $request)
    {
        $query = CaseModel::with([
            'publicSubmission',
            'ocrValidations.document',
            'ocrValidations.reviewer'
        ]);
        
        // Filter by validation status
        if ($request->filled('status')) {
            $query->whereHas('ocrValidations', function ($q) use ($request) {
                $q->where('validation_status', $request->status);
            });
        }
        
        // Filter by case status
        if ($request->filled('case_status')) {
            $query->where('status', $request->case_status);
        }
        
        // Filter by review status
        if ($request->filled('reviewed')) {
            $isReviewed = $request->reviewed === 'done';
            $query->whereHas('ocrValidations', function ($q) use ($isReviewed) {
                $q->where('is_reviewed', $isReviewed);
            });
        }
        
        // Search by NIK or Name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('petitioner_nik', 'LIKE', "%{$search}%")
                  ->orWhere('petitioner_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('publicSubmission', function ($sub) use ($search) {
                      $sub->where('nik', 'LIKE', "%{$search}%")
                          ->orWhere('nama_lengkap', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        // Order by latest
        $query->orderBy('created_at', 'desc');
        
        // Get all data without OCR validations filter (unlike index)
        $allCases = $query->paginate(20);
        
        // Statistics for summary cards - aligned with dashboard PA Management
        $stats = [
            'success' => OcrValidation::where('validation_status', 'SUCCESS')->count(),
            'partial' => OcrValidation::where('validation_status', 'PARTIAL')->count(),
            'failed' => OcrValidation::where('validation_status', 'FAILED')->count(),
            'unreviewed' => OcrValidation::where('is_reviewed', false)->count(),
            'sent_to_disdukcapil' => CaseModel::where('status', 'DISDUKCAPIL_VALIDATION')->count(),
        ];
        
        return view('dashboard.review.all-data', compact('allCases', 'stats'));
    }
    
    /**
     * Display case detail with OCR validation results
     */
    public function show(int $id)
    {
        $case = CaseModel::with([
            'publicSubmission.documents',
            'documents',
            'ocrValidations' => function ($query) {
                $query->with('document', 'ocrResult', 'reviewer')
                      ->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);
        
        // Check authorization
        if (!auth()->user()->hasAnyRole(['pa_management', 'super_admin'])) {
            abort(403, 'Unauthorized access.');
        }
        
        // Validation statistics for this case
        $validationStats = [
            'total' => $case->ocrValidations->count(),
            'match' => $case->ocrValidations->where('validation_status', 'MATCH')->count(),
            'partial_match' => $case->ocrValidations->where('validation_status', 'PARTIAL_MATCH')->count(),
            'mismatch' => $case->ocrValidations->where('validation_status', 'MISMATCH')->count(),
        ];
        
        return view('dashboard.review.show', compact('case', 'validationStats'));
    }
    
    /**
     * Validate OCR result (Approve/Reject/Request Correction)
     */
    public function validateOcr(Request $request, int $id)
    {
        $request->validate([
            'validation_id' => 'required|exists:ocr_validations,id',
            'action'        => 'required|in:approve,reject,request_correction',
            'notes'         => 'nullable|string|max:1000',
        ]);
        
        $validation = OcrValidation::findOrFail($request->validation_id);
        
        // Check if validation belongs to this case
        if ($validation->case_id != $id) {
            abort(403, 'Validation does not belong to this case.');
        }
        
        // Update validation record
        $validation->update([
            'is_reviewed' => true,
            'review_action' => $request->action,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $request->notes,
        ]);
        
        // Update case status based on action
        $case = CaseModel::findOrFail($id);
        
        if ($request->action === 'approve') {
            $pendingValidations = OcrValidation::where('case_id', $case->id)
                ->where('is_reviewed', false)
                ->count();

            // Log approval
            $this->logActivitySafely(
                'OCR validation approved',
                $case,
                [
                    'validation_id' => $validation->id,
                    'match_score' => $validation->overall_match_score,
                ]
            );

            $allReviewed = OcrValidation::where('case_id', $case->id)
                ->where('is_reviewed', false)
                ->count() === 0;

            $allApproved = $allReviewed && OcrValidation::where('case_id', $case->id)
                ->where('review_action', '!=', 'approve')
                ->exists() === false;

            // PENTING: Jangan ubah status otomatis. Status diubah hanya saat user klik tombol "Approve Semua"
            if ($allApproved && $case->publicSubmission) {
                $case->publicSubmission->update(['status' => 'APPROVED']);
            }

            $this->logActivitySafely(
                'OCR validation approved',
                $case,
                [
                    'validation_id' => $validation->id,
                    'match_score' => $validation->overall_match_score,
                ]
            );
            
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Validasi OCR disetujui. Kasus dapat diproses lebih lanjut.',
                    'status' => 'approved',
                    'case_status' => $case->status,
                ]);
            }
            
            return redirect()
                ->route('dashboard.review.show', $id)
                ->with('success', 'Validasi OCR disetujui. Kasus dapat diproses lebih lanjut.');
        }
        
        if ($request->action === 'reject') {
            // 1. Update case status untuk PA Assistant dashboard
            $case->update(['status' => 'NEEDS_REVISION']);
            
            // 2. Update public submission status untuk tracking page (pemohon notifikasi)
            if ($case->publicSubmission) {
                $case->publicSubmission->update(['status' => 'REJECTED']);
            }
            
            // 3. Log activity dengan detail lengkap (untuk audit trail)
            $this->logActivitySafely(
                'OCR validation rejected - dual notification sent',
                $case,
                [
                    'validation_id' => $validation->id,
                    'reason' => $request->notes,
                    'tracking_token' => $case->publicSubmission?->tracking_token,
                    'workflow_target' => 'tracking_page_and_pa_dashboard',
                    'notification_status' => 'pending',
                ]
            );
            
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Validasi OCR ditolak. Pemohon akan diminta mengunggah ulang dokumen melalui halaman tracking. PA Assistant diberitahu.',
                    'status' => 'rejected',
                    'notification_targets' => [
                        'tracking_page' => true,
                        'pa_dashboard' => true
                    ]
                ]);
            }
            
            return redirect()
                ->route('dashboard.review.show', $id)
                ->with('warning', 'Validasi OCR ditolak. Notifikasi dikirim ke:\n• Halaman Tracking (pemohon bisa reupload dokumen)\n• PA Assistant Dashboard (internal follow-up)');
        }
        
        // request_correction
        $this->logActivitySafely(
            'OCR validation needs correction',
            $case,
            [
                'validation_id' => $validation->id,
                'notes' => $request->notes,
            ]
        );
        
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Permintaan koreksi dikirim ke PA Assistant.',
                'status' => 'correction_requested'
            ]);
        }
        
        return redirect()
            ->route('dashboard.review.show', $id)
            ->with('info', 'Permintaan koreksi dikirim ke PA Assistant.');
    }

    /**
     * Kirim kasus ke Disdukcapil setelah OCR approval selesai
     * Route: POST /dashboard/review/cases/{id}/send-to-disdukcapil
     */
    public function sendToDisdukcapil(int $id)
    {
        $case = CaseModel::findOrFail($id);
        $user = auth()->user();

        // Check permission
        if (!$user->hasAnyRole(['pa_management', 'super_admin'])) {
            abort(403, 'Unauthorized to send case to Disdukcapil');
        }

        // Verify all OCR validations are approved
        $allApproved = OcrValidation::where('case_id', $case->id)
            ->where('is_reviewed', false)
            ->count() === 0 && 
            OcrValidation::where('case_id', $case->id)
                ->where('review_action', '!=', 'approve')
                ->count() === 0;

        if (!$allApproved) {
            $message = 'Tidak semua OCR validation sudah disetujui. Harap review semua validasi terlebih dahulu.';
            
            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json(['message' => $message], 400);
            }
            
            return redirect()
                ->route('dashboard.review.show', $id)
                ->with('error', $message);
        }

        // Send to Disdukcapil (explicit action)
        $case = $this->workflow->sendToDisdukcapil($case, $user, 'Dikirim dari OCR Validation review');

        // Auto-assign first available Disdukcapil staff to handle validation
        $disdukcapilInst = \App\Models\Institution::where('type', 'DISDUKCAPIL')->first();
        $disdukcapilStaff = null;
        
        if ($disdukcapilInst) {
            $disdukcapilStaff = \App\Models\User::where('institution_id', $disdukcapilInst->id)
                ->whereHas('roles', function ($q) {
                    $q->where('name', 'disdukcapil_staff');
                })
                ->first();
        }

        if ($disdukcapilStaff) {
            $case->update(['assigned_disdukcapil_user_id' => $disdukcapilStaff->id]);
            
            // Trigger graph sync to update Neo4j relationships
            \App\Models\IntegrationQueue::create([
                'aggregate_type' => 'Case',
                'aggregate_id'   => $case->id,
                'event_type'     => 'updated',
                'payload'        => ['assigned_disdukcapil_user_id' => $disdukcapilStaff->id],
                'available_at'   => now(),
            ]);
        }

        $this->logActivitySafely(
            'Case sent to Disdukcapil after OCR approval',
            $case,
            [
                'action' => 'send_to_disdukcapil',
                'triggered_by' => $user->id,
                'assigned_to' => $disdukcapilStaff?->id,
            ]
        );

        if (request()->expectsJson() || request()->wantsJson()) {
            return response()->json([
                'message' => 'Data sudah dikirim ke Disdukcapil',
                'case_id' => $case->id,
                'status' => $case->status,
            ]);
        }

        return redirect()
            ->route('dashboard.review.show', $id)
            ->with('success', 'Data sudah dikirim ke Disdukcapil. Status kasus diperbarui.');
    }

    /**
     * Simpan koreksi manual untuk hasil OCR pada level validasi.
     */
    public function correctOcr(Request $request, int $id)
    {
        $validated = $request->validate([
            'validation_id'      => 'required|exists:ocr_validations,id',
            'target'             => 'nullable|in:ocr,manual',
            'ocr_nik'            => 'nullable|string|max:16',
            'ocr_nama'           => 'nullable|string|max:255',
            'ocr_tempat_lahir'   => 'nullable|string|max:255',
            'ocr_tgl_lahir'      => 'nullable|string|max:50',
            'ocr_alamat'         => 'nullable|string',
            'ocr_rt_rw'          => 'nullable|string|max:10',
            'ocr_kelurahan'      => 'nullable|string|max:255',
            'ocr_kecamatan'      => 'nullable|string|max:255',
            'ocr_no_kk'          => 'nullable|string|max:16',
            'input_nik'          => 'nullable|string|max:16',
            'input_nama'         => 'nullable|string|max:255',
            'input_tempat_lahir' => 'nullable|string|max:255',
            'input_tgl_lahir'    => 'nullable|string|max:50',
            'input_alamat'       => 'nullable|string',
            'input_rt_rw'        => 'nullable|string|max:10',
            'input_kelurahan'    => 'nullable|string|max:255',
            'input_kecamatan'    => 'nullable|string|max:255',
            'input_no_kk'        => 'nullable|string|max:16',
            'correction_notes'   => 'nullable|string|max:1000',
        ]);

        $validation = OcrValidation::findOrFail($validated['validation_id']);

        if ($validation->case_id != $id) {
            abort(403, 'Validation does not belong to this case.');
        }

        $ocrFieldMap = [
            'nik' => 'ocr_nik',
            'nama' => 'ocr_nama',
            'alamat' => 'ocr_alamat',
            'rt_rw' => 'ocr_rt_rw',
            'kelurahan' => 'ocr_kelurahan',
            'kecamatan' => 'ocr_kecamatan',
        ];

        $manualFieldMap = [
            'nik' => 'input_nik',
            'nama' => 'input_nama',
            'alamat' => 'input_alamat',
            'rt_rw' => 'input_rt_rw',
            'kelurahan' => 'input_kelurahan',
            'kecamatan' => 'input_kecamatan',
        ];

        $target = $validated['target'] ?? null;
        if ($target === null) {
            $target = collect($ocrFieldMap)->contains(function ($column) use ($validated) {
                return filled($validated[$column] ?? null);
            }) ? 'ocr' : 'manual';
        }

        $inputValues = [];
        foreach ($manualFieldMap as $field => $column) {
            $inputValues[$field] = isset($validated[$column])
                ? trim((string) $validated[$column])
            : trim((string) ($validation->{$column} ?? ''));
        }

        $ocrValues = [];
        foreach ($ocrFieldMap as $field => $column) {
            $ocrValues[$field] = isset($validated[$column])
                ? trim((string) $validated[$column])
                : trim((string) ($validation->{$column} ?? ''));
        }

        if ($target === 'manual') {
            $this->syncManualSourceData($validation, $inputValues);
        }

        $comparisonResults = [];
        $matchedCount = 0;
        $totalFields = 0;

        foreach ($ocrFieldMap as $field => $ocrColumn) {
            $inputValue = $inputValues[$field] ?? '';
            $ocrValue = $ocrValues[$field] ?? '';

            if ((empty($inputValue) || trim((string) $inputValue) === '') && (empty($ocrValue) || trim((string) $ocrValue) === '')) {
                continue;
            }

            $totalFields++;

            $similarity = $this->calculateSimilarity(
                $this->normalizeValue($inputValue),
                $this->normalizeValue($ocrValue)
            );

            $isMatch = $similarity >= 0.90;

            if ($isMatch) {
                $matchedCount++;
            }

            $comparisonResults[$field] = [
                'input' => $inputValue,
                'ocr' => $ocrValue,
                'similarity' => round($similarity, 4),
                'match' => $isMatch,
                'confidence' => data_get($validation->comparison_results, "{$field}.confidence", 0),
            ];
        }

        // Hitung overall score menggunakan RATA-RATA SIMILARITY SCORES (konsisten dengan OCRValidationService)
        $similarityScores = [];
        foreach ($comparisonResults as $field => $result) {
            $similarity = (float)($result['similarity'] ?? 0);
            if ($similarity >= 0) {
                // Similarity is 0-1 scale, convert to 0-100
                $similarityScores[] = $similarity * 100;
            }
        }

        $overallScore = count($similarityScores) > 0
            ? round(array_sum($similarityScores) / count($similarityScores), 2)
            : 0;

        $newStatus = $this->determineValidationStatus($overallScore, $comparisonResults);

        $noteSuffix = trim((string)($validated['correction_notes'] ?? ''));
        $auditNote = 'Koreksi OCR manual oleh PA Management pada ' . now()->format('d-m-Y H:i');
        $reviewNotes = trim(($validation->review_notes ? $validation->review_notes . "\n\n" : '') . $auditNote . ($noteSuffix !== '' ? "\nCatatan: {$noteSuffix}" : ''));

        $validation->update([
            'input_nik' => $this->nullIfEmpty($inputValues['nik'] ?? null),
            'input_nama' => $this->nullIfEmpty($inputValues['nama'] ?? null),
            'input_tempat_lahir' => $this->nullIfEmpty($inputValues['tempat_lahir'] ?? null),
            'input_tgl_lahir' => $this->normalizeDateOrNull($inputValues['tgl_lahir'] ?? null),
            'input_alamat' => $this->nullIfEmpty($inputValues['alamat'] ?? null),
            'input_rt_rw' => $this->nullIfEmpty($inputValues['rt_rw'] ?? null),
            'input_kelurahan' => $this->nullIfEmpty($inputValues['kelurahan'] ?? null),
            'input_kecamatan' => $this->nullIfEmpty($inputValues['kecamatan'] ?? null),
            'input_no_kk' => $this->nullIfEmpty($inputValues['no_kk'] ?? null),
            'ocr_nik' => $this->nullIfEmpty($ocrValues['nik'] ?? null),
            'ocr_nama' => $this->nullIfEmpty($ocrValues['nama'] ?? null),
            'ocr_tempat_lahir' => $this->nullIfEmpty($ocrValues['tempat_lahir'] ?? null),
            'ocr_tgl_lahir' => $this->nullIfEmpty($ocrValues['tgl_lahir'] ?? null),
            'ocr_alamat' => $this->nullIfEmpty($ocrValues['alamat'] ?? null),
            'ocr_rt_rw' => $this->nullIfEmpty($ocrValues['rt_rw'] ?? null),
            'ocr_kelurahan' => $this->nullIfEmpty($ocrValues['kelurahan'] ?? null),
            'ocr_kecamatan' => $this->nullIfEmpty($ocrValues['kecamatan'] ?? null),
            'ocr_no_kk' => $this->nullIfEmpty($ocrValues['no_kk'] ?? null),
            'comparison_results' => $comparisonResults,
            'overall_match_score' => $overallScore,
            'fields_matched' => $matchedCount,
            'fields_total' => $totalFields,
            'validation_status' => $newStatus,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $reviewNotes,
        ]);

        $this->logActivitySafely(
            'OCR result manually corrected by PA Management',
            $validation->case,
            [
                'validation_id' => $validation->id,
                'overall_match_score' => $overallScore,
                'validation_status' => $newStatus,
            ]
        );

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Koreksi hasil OCR berhasil disimpan dan skor validasi diperbarui.',
                'overall_match_score' => $overallScore,
                'validation_status' => $newStatus
            ]);
        }

        return redirect()
            ->route('dashboard.review.show', $id)
            ->with('success', 'Koreksi hasil OCR berhasil disimpan dan skor validasi diperbarui.');
    }

    /**
     * Sinkronkan perubahan manual ke sumber data kasus agar snapshot tetap konsisten.
     */
    private function syncManualSourceData(OcrValidation $validation, array $values): void
    {
        $case = $validation->case;
        if (! $case) {
            return;
        }

        $documentType = optional($validation->document)->document_type;
        $isSpouse = $documentType === 'KTP_ISTRI';

        if ($isSpouse) {
            $case->update([
                'spouse_nik' => $values['nik'] ?? null,
                'spouse_name' => $values['nama'] ?? null,
                'spouse_alamat' => $values['alamat'] ?? null,
                'spouse_rt_rw' => $values['rt_rw'] ?? null,
                'spouse_kelurahan' => $values['kelurahan'] ?? null,
                'spouse_kecamatan' => $values['kecamatan'] ?? null,
            ]);
        } else {
            $case->update([
                'petitioner_nik' => $values['nik'] ?? null,
                'petitioner_name' => $values['nama'] ?? null,
                'petitioner_alamat' => $values['alamat'] ?? null,
                'petitioner_rt_rw' => $values['rt_rw'] ?? null,
                'petitioner_kelurahan' => $values['kelurahan'] ?? null,
                'petitioner_kecamatan' => $values['kecamatan'] ?? null,
            ]);
        }

        $publicSubmission = $case->publicSubmission;
        if (! $publicSubmission) {
            return;
        }

        if ($isSpouse) {
            $publicSubmission->update([
                'nik_istri' => $values['nik'] ?? null,
                'nama_istri' => $values['nama'] ?? null,
                'alamat_istri' => $values['alamat'] ?? null,
                'rt_rw_istri' => $values['rt_rw'] ?? null,
                'kelurahan_istri' => $values['kelurahan'] ?? null,
                'kecamatan_istri' => $values['kecamatan'] ?? null,
            ]);
        } else {
            $publicSubmission->update([
                'nik_suami' => $values['nik'] ?? null,
                'nama_suami' => $values['nama'] ?? null,
                'alamat_suami' => $values['alamat'] ?? null,
                'rt_rw_suami' => $values['rt_rw'] ?? null,
                'kelurahan_suami' => $values['kelurahan'] ?? null,
                'kecamatan_suami' => $values['kecamatan'] ?? null,
                'nik' => $values['nik'] ?? null,
                'petitioner_name' => $values['nama'] ?? null,
            ]);
        }
    }

    /**
     * Hapus data OCR lama dan proses ulang OCR secara cepat (sinkron) untuk kasus ini.
     */
    public function refreshOcr(Request $request, int $id, OCRService $ocrService)
    {
        $case = CaseModel::with('documents')->findOrFail($id);

        if ($case->status === 'DRAFT') {
            $case->update([
                'status' => 'SUBMITTED',
                'submitted_at' => $case->submitted_at ?? now(),
            ]);
        }

        $processableTypes = ['KTP', 'KTP_SUAMI', 'KTP_ISTRI'];
        $documents = $case->documents->whereIn('document_type', $processableTypes);

        if ($documents->isEmpty()) {
            return redirect()
                ->route('dashboard.review.show', $id)
                ->with('warning', 'Tidak ada dokumen yang bisa diproses OCR untuk kasus ini.');
        }

        $documentIds = $documents->pluck('id')->all();

        DB::transaction(function () use ($documentIds) {
            OcrValidation::whereIn('document_id', $documentIds)->delete();
            OcrResult::whereIn('document_id', $documentIds)->delete();
            OcrJob::whereIn('document_id', $documentIds)->delete();
            Document::whereIn('id', $documentIds)->update(['status' => 'PENDING']);
        });

        $processed = 0;
        $failed = 0;

        foreach ($documents as $document) {
            try {
                $ocrService->process($document->fresh());
                $processed++;
            } catch (\Throwable $e) {
                $failed++;
                report($e);
            }
        }

        $this->logActivitySafely(
            'PA Management refreshed OCR and removed old OCR data',
            $case,
            [
                'processed_documents' => $processed,
                'failed_documents' => $failed,
                'reset_document_ids' => $documentIds,
            ]
        );

        if ($processed === 0) {
            return redirect()
                ->route('dashboard.review.show', $id)
                ->with('error', 'Proses ulang OCR gagal. Periksa log OCR service dan queue.');
        }

        $message = "OCR diproses ulang cepat: {$processed} dokumen berhasil";
        if ($failed > 0) {
            $message .= ", {$failed} gagal";
        }

        return redirect()
            ->route('dashboard.review.show', $id)
            ->with($failed > 0 ? 'warning' : 'success', $message . '.');
    }
    
    /**
     * Display validation statistics dashboard
     */
    public function statistics()
    {
        // Overall statistics
        $totalValidations = OcrValidation::count();
        
        $stats = [
            'total' => $totalValidations,
            'match' => OcrValidation::where('validation_status', 'MATCH')->count(),
            'partial_match' => OcrValidation::where('validation_status', 'PARTIAL_MATCH')->count(),
            'mismatch' => OcrValidation::where('validation_status', 'MISMATCH')->count(),
            'pending_review' => OcrValidation::where('is_reviewed', false)->count(),
            'approved' => OcrValidation::where('review_action', 'approve')->count(),
            'rejected' => OcrValidation::where('review_action', 'reject')->count(),
            'need_correction' => OcrValidation::where('review_action', 'request_correction')->count(),
            'avg_score' => OcrValidation::avg('overall_match_score') ?? 0,
        ];
        
        // Field-specific statistics
        $fields = ['nik', 'nama', 'alamat', 'rt_rw', 'kelurahan', 'kecamatan'];
        $stats['by_field'] = [];
        
        foreach ($fields as $field) {
            $all = OcrValidation::whereNotNull("comparison_results->{$field}")
                ->get();
            
            $stats['by_field'][$field] = [
                'total' => $all->count(),
                'match' => $all->filter(function ($v) use ($field) {
                    $score = $v->comparison_results[$field] ?? 0;
                    return $score >= 90;  // Standardized: ≥90% is MATCH
                })->count(),
                'partial' => $all->filter(function ($v) use ($field) {
                    $score = $v->comparison_results[$field] ?? 0;
                    return $score >= 75 && $score < 90;  // Standardized: 75-89% is PARTIAL_MATCH
                })->count(),
                'mismatch' => $all->filter(function ($v) use ($field) {
                    $score = $v->comparison_results[$field] ?? 0;
                    return $score < 80;
                })->count(),
                'avg_score' => $all->avg(function ($v) use ($field) {
                    return $v->comparison_results[$field] ?? 0;
                }) ?? 0,
            ];
        }
        
        // Recent reviews (only reviewed items)
        $recentReviews = OcrValidation::with('case', 'reviewer')
            ->where('is_reviewed', true)
            ->orderBy('reviewed_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('dashboard.review.statistics', compact('stats', 'recentReviews'));
    }

    private function normalizeValue($value): string
    {
        if (empty($value)) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        $normalized = strtoupper(trim((string) $value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = preg_replace('/[^A-Z0-9\s]/', '', $normalized);

        return $normalized;
    }

    private function calculateSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }

        if ($str1 === '' || $str2 === '') {
            return 0.0;
        }

        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen > 255) {
            similar_text($str1, $str2, $percent);
            return $percent / 100;
        }

        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLen);
    }

    private function determineValidationStatus(float $score, array $results): string
    {
        // Score-based logic only (3 statuses)
        if ($score >= 90) {
            return 'MATCH';  // ≥90%: Excellent similarity
        }

        if ($score >= 75) {
            return 'PARTIAL_MATCH';  // 75-89%: Good similarity
        }

        return 'MISMATCH';  // <75%: Poor similarity
    }

    private function nullIfEmpty($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        return $normalized === '' ? null : $normalized;
    }

    private function normalizeDateOrNull($value): ?string
    {
        $normalized = $this->nullIfEmpty($value);
        if ($normalized === null) {
            return null;
        }

        try {
            return Carbon::parse(str_replace('/', '-', $normalized))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function logActivitySafely(string $message, $subject = null, array $properties = []): void
    {
        try {
            if (! function_exists('activity')) {
                return;
            }

            $logger = activity();
            if ($subject) {
                $logger->performedOn($subject);
            }

            if (auth()->check()) {
                $logger->causedBy(auth()->user());
            }

            if (! empty($properties)) {
                $logger->withProperties($properties);
            }

            $logger->log($message);
        } catch (\Throwable $e) {
            // Logging must never block the primary business flow.
        }
    }
}
