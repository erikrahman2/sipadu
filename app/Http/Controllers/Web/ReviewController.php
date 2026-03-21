<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\OcrValidation;
use Illuminate\Http\Request;

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
        $fields = ['nik', 'nama', 'tempat_lahir', 'tgl_lahir', 'alamat', 'rt_rw', 'kelurahan', 'kecamatan', 'no_kk'];
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
}
