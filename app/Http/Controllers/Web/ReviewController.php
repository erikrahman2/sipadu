<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\Document;
use App\Models\OcrJob;
use App\Models\OcrResult;
use App\Models\OcrValidation;
use App\Services\OCRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
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
            'manual_review' => OcrValidation::where('validation_status', 'MANUAL_REVIEW')->count(),
            'mismatch' => OcrValidation::where('validation_status', 'MISMATCH')->count(),
        ];
        
        return view('dashboard.review.index', compact('cases', 'stats'));
    }
    
    /**
     * Display case detail with OCR validation results
     */
    public function show(int $id)
    {
        $case = CaseModel::with([
            'publicSubmission',
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
            'manual_review' => $case->ocrValidations->where('validation_status', 'MANUAL_REVIEW')->count(),
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
            // Log approval
            activity()
                ->performedOn($case)
                ->causedBy(auth()->user())
                ->withProperties([
                    'validation_id' => $validation->id,
                    'match_score' => $validation->overall_match_score,
                ])
                ->log('OCR validation approved');
            
            return redirect()
                ->route('dashboard.review.show', $id)
                ->with('success', 'Validasi OCR disetujui. Kasus dapat diproses lebih lanjut.');
        }
        
        if ($request->action === 'reject') {
            $case->update(['status' => 'NEEDS_REVISION']);
            
            activity()
                ->performedOn($case)
                ->causedBy(auth()->user())
                ->withProperties([
                    'validation_id' => $validation->id,
                    'reason' => $request->notes,
                ])
                ->log('OCR validation rejected');
            
            return redirect()
                ->route('dashboard.review.show', $id)
                ->with('warning', 'Validasi OCR ditolak. Pemohon akan diminta mengunggah ulang dokumen.');
        }
        
        // request_correction
        activity()
            ->performedOn($case)
            ->causedBy(auth()->user())
            ->withProperties([
                'validation_id' => $validation->id,
                'notes' => $request->notes,
            ])
            ->log('OCR validation needs correction');
        
        return redirect()
            ->route('dashboard.review.show', $id)
            ->with('info', 'Permintaan koreksi dikirim ke PA Assistant.');
    }

    /**
     * Simpan koreksi manual untuk hasil OCR pada level validasi.
     */
    public function correctOcr(Request $request, int $id)
    {
        $validated = $request->validate([
            'validation_id'      => 'required|exists:ocr_validations,id',
            'ocr_nik'            => 'nullable|string|max:16',
            'ocr_nama'           => 'nullable|string|max:255',
            'ocr_tempat_lahir'   => 'nullable|string|max:255',
            'ocr_tgl_lahir'      => 'nullable|string|max:50',
            'ocr_alamat'         => 'nullable|string',
            'ocr_rt_rw'          => 'nullable|string|max:10',
            'ocr_kelurahan'      => 'nullable|string|max:255',
            'ocr_kecamatan'      => 'nullable|string|max:255',
            'ocr_no_kk'          => 'nullable|string|max:16',
            'correction_notes'   => 'nullable|string|max:1000',
        ]);

        $validation = OcrValidation::findOrFail($validated['validation_id']);

        if ($validation->case_id != $id) {
            abort(403, 'Validation does not belong to this case.');
        }

        $ocrFieldMap = [
            'nik' => 'ocr_nik',
            'nama' => 'ocr_nama',
            'tempat_lahir' => 'ocr_tempat_lahir',
            'tgl_lahir' => 'ocr_tgl_lahir',
            'alamat' => 'ocr_alamat',
            'rt_rw' => 'ocr_rt_rw',
            'kelurahan' => 'ocr_kelurahan',
            'kecamatan' => 'ocr_kecamatan',
            'no_kk' => 'ocr_no_kk',
        ];

        $comparisonResults = [];
        $matchedCount = 0;
        $totalFields = 0;

        foreach ($ocrFieldMap as $field => $ocrColumn) {
            $inputValue = $validation->{"input_{$field}"};
            $ocrValue = isset($validated[$ocrColumn]) ? trim((string) $validated[$ocrColumn]) : $validation->{$ocrColumn};

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

        $overallScore = $totalFields > 0
            ? round(($matchedCount / $totalFields) * 100, 2)
            : 0;

        $newStatus = $this->determineValidationStatus($overallScore, $comparisonResults);

        $noteSuffix = trim((string)($validated['correction_notes'] ?? ''));
        $auditNote = 'Koreksi OCR manual oleh PA Management pada ' . now()->format('d-m-Y H:i');
        $reviewNotes = trim(($validation->review_notes ? $validation->review_notes . "\n\n" : '') . $auditNote . ($noteSuffix !== '' ? "\nCatatan: {$noteSuffix}" : ''));

        $validation->update([
            'ocr_nik' => $validated['ocr_nik'] ?? null,
            'ocr_nama' => $validated['ocr_nama'] ?? null,
            'ocr_tempat_lahir' => $validated['ocr_tempat_lahir'] ?? null,
            'ocr_tgl_lahir' => $validated['ocr_tgl_lahir'] ?? null,
            'ocr_alamat' => $validated['ocr_alamat'] ?? null,
            'ocr_rt_rw' => $validated['ocr_rt_rw'] ?? null,
            'ocr_kelurahan' => $validated['ocr_kelurahan'] ?? null,
            'ocr_kecamatan' => $validated['ocr_kecamatan'] ?? null,
            'ocr_no_kk' => $validated['ocr_no_kk'] ?? null,
            'comparison_results' => $comparisonResults,
            'overall_match_score' => $overallScore,
            'fields_matched' => $matchedCount,
            'fields_total' => $totalFields,
            'validation_status' => $newStatus,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $reviewNotes,
        ]);

        activity()
            ->performedOn($validation->case)
            ->causedBy(auth()->user())
            ->withProperties([
                'validation_id' => $validation->id,
                'overall_match_score' => $overallScore,
                'validation_status' => $newStatus,
            ])
            ->log('OCR result manually corrected by PA Management');

        return redirect()
            ->route('dashboard.review.show', $id)
            ->with('success', 'Koreksi hasil OCR berhasil disimpan dan skor validasi diperbarui.');
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

        activity()
            ->performedOn($case)
            ->causedBy(auth()->user())
            ->withProperties([
                'processed_documents' => $processed,
                'failed_documents' => $failed,
                'reset_document_ids' => $documentIds,
            ])
            ->log('PA Management refreshed OCR and removed old OCR data');

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
            'manual_review' => OcrValidation::where('validation_status', 'MANUAL_REVIEW')->count(),
            'mismatch' => OcrValidation::where('validation_status', 'MISMATCH')->count(),
            'needs_review' => OcrValidation::where('validation_status', 'MANUAL_REVIEW')
                ->orWhere('validation_status', 'MISMATCH')
                ->count(),
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
                    return $score >= 95;
                })->count(),
                'partial' => $all->filter(function ($v) use ($field) {
                    $score = $v->comparison_results[$field] ?? 0;
                    return $score >= 80 && $score < 95;
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
        if (isset($results['nik']) && !($results['nik']['match'] ?? false)) {
            return 'MISMATCH';
        }

        if ($score >= 95) {
            return 'MATCH';
        }

        if ($score >= 80) {
            return 'PARTIAL_MATCH';
        }

        if ($score >= 60) {
            return 'MANUAL_REVIEW';
        }

        return 'MISMATCH';
    }
}
