<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\Document;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DisdukcapilController extends Controller
{
    public function __construct(
        private readonly WorkflowService $workflow
    ) {
        // Middleware already applied at route level
    }

    /**
     * Show list of cases in DISDUKCAPIL_VALIDATION status
     * GET /dashboard/disdukcapil/cases
     */
    public function index()
    {
        $cases = CaseModel::where('status', 'DISDUKCAPIL_VALIDATION')
            ->with(['publicSubmission', 'documents', 'assignedDisdukcapilUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('dashboard.disdukcapil.index', compact('cases'));
    }

    /**
     * Show case details page
     * GET /dashboard/disdukcapil/cases/{id}
     */
    public function show(int $id)
    {
        $case = CaseModel::with(['publicSubmission', 'documents', 'ocrValidations'])->findOrFail($id);
        
        // Only DISDUKCAPIL_VALIDATION cases
        if ($case->status !== 'DISDUKCAPIL_VALIDATION') {
            abort(404, 'Case tidak dalam status DISDUKCAPIL_VALIDATION');
        }

        return view('dashboard.disdukcapil.show', compact('case'));
    }

    /**
     * Show Disdukcapil validation process page
     * GET /dashboard/disdukcapil/cases/{id}/process
     */
    public function showProcess(int $id)
    {
        $case = CaseModel::with(['publicSubmission', 'documents', 'ocrValidations'])->findOrFail($id);
        
        // Only DISDUKCAPIL_VALIDATION cases can be processed
        if ($case->status !== 'DISDUKCAPIL_VALIDATION') {
            return redirect()->route('dashboard.index')
                ->with('error', 'Case bukan dalam status DISDUKCAPIL_VALIDATION');
        }

        // Check if BAST and digital docs already uploaded
        $bastDoc = Document::where('case_id', $id)
            ->where('document_type', 'BAST')
            ->first();
        
        $digitalDocs = Document::where('case_id', $id)
            ->where('document_type', 'DIGITAL_COPY')
            ->get();

        return view('dashboard.disdukcapil.process', [
            'case' => $case,
            'bastDoc' => $bastDoc,
            'digitalDocs' => $digitalDocs,
        ]);
    }

    /**
     * Submit Disdukcapil validation process
     * POST /dashboard/disdukcapil/cases/{id}/process
     */
    public function submitProcess(int $id, Request $request)
    {
        $case = CaseModel::findOrFail($id);
        $user = auth()->user();

        if ($case->status !== 'DISDUKCAPIL_VALIDATION') {
            $message = 'Case status tidak valid untuk submission';
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }

        $validated = $request->validate([
            'bast_file'       => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'digital_files.*' => 'nullable|file|mimes:pdf,jpg,png|max:10240',
            'notes'           => 'nullable|string|max:1000',
        ]);

        try {
            // Set assigned_disdukcapil_user_id if not already set
            if (!$case->assigned_disdukcapil_user_id) {
                $case->update(['assigned_disdukcapil_user_id' => $user->id]);
            }

            // Handle BAST upload
            if ($request->hasFile('bast_file')) {
                $this->uploadBastDocument($case, $request->file('bast_file'), $user);
            }

            // Handle digital docs upload
            if ($request->hasFile('digital_files')) {
                $this->uploadDigitalDocuments($case, $request->file('digital_files'), $user);
            }

            // Transition case to PA_DECISION (or COMPLETED)
            $case = $this->workflow->transition(
                $case,
                'COMPLETED',
                $user,
                $validated['notes'] ?? 'Proses validasi Disdukcapil selesai - dikirim ke PA Management',
                [
                    'action' => 'disdukcapil_validation_complete',
                    'bast_uploaded' => $request->hasFile('bast_file'),
                    'digital_count' => count($request->file('digital_files') ?? []),
                ]
            );

            Log::channel('workflow')->info('Disdukcapil validation completed', [
                'case_id' => $case->id,
                'processed_by' => $user->id,
            ]);

            $successMessage = 'Proses validasi Disdukcapil selesai. Data dikirim ke PA Management.';

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['message' => $successMessage], 200);
            }

            return redirect()->route('dashboard.disdukcapil.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error('Disdukcapil process error', [
                'case_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $errorMessage = 'Gagal memproses: ' . $e->getMessage();
            
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['message' => $errorMessage], 500);
            }

            return redirect()->back()
                ->with('error', $errorMessage);
        }
    }

    /**
     * Upload BAST document
     */
    private function uploadBastDocument(CaseModel $case, $file, $user)
    {
        // Delete existing BAST if any
        $existing = Document::where('case_id', $case->id)
            ->where('document_type', 'BAST')
            ->first();

        if ($existing) {
            Storage::disk('local')->delete($existing->path);
            $existing->delete();
        }

        $filename = 'BAST_' . $case->case_number . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('cases/' . $case->id . '/bast', $filename, 'local');

        Document::create([
            'case_id'       => $case->id,
            'uploaded_by'   => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_name'   => $filename,
            'disk'          => 'local',
            'path'          => $path,
            'mime_type'     => $file->getMimeType(),
            'size_bytes'    => $file->getSize(),
            'document_type' => 'BAST',
            'status'        => 'VALIDATED',
            'checksum'      => hash_file('sha256', $file->getRealPath()),
        ]);

        Log::info('BAST document uploaded', [
            'case_id' => $case->id,
            'path' => $path,
        ]);
    }

    /**
     * Upload digital documents
     */
    private function uploadDigitalDocuments(CaseModel $case, array $files, $user)
    {
        foreach ($files as $file) {
            $filename = 'DIGITAL_' . $case->case_number . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('cases/' . $case->id . '/digital', $filename, 'local');

            Document::create([
                'case_id'       => $case->id,
                'uploaded_by'   => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => $filename,
                'disk'          => 'local',
                'path'          => $path,
                'mime_type'     => $file->getMimeType(),
                'size_bytes'    => $file->getSize(),
                'document_type' => 'DIGITAL_COPY',
                'status'        => 'VALIDATED',
                'checksum'      => hash_file('sha256', $file->getRealPath()),
            ]);
        }

        Log::info('Digital documents uploaded', [
            'case_id' => $case->id,
            'count' => count($files),
        ]);
    }

    /**
     * Kirim kasus kembali ke PA Management (Reject)
     * POST /dashboard/disdukcapil/cases/{id}/send-to-pa
     */
    public function sendToPa(int $id, Request $request)
    {
        $case = CaseModel::findOrFail($id);
        $user = auth()->user();

        if ($case->status !== 'DISDUKCAPIL_VALIDATION') {
            $message = 'Case tidak dalam status DISDUKCAPIL_VALIDATION';
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }

        $validated = $request->validate([
            'reject_reason' => 'required|string|max:1000',
        ]);

        try {
            // Transition case to REJECTED status
            // PA Assistant dapat mengajukan ulang dari status REJECTED -> DRAFT -> SUBMITTED
            $case = $this->workflow->transition(
                $case,
                'REJECTED',
                $user,
                'Ditolak oleh Disdukcapil: ' . $validated['reject_reason'],
                [
                    'action' => 'disdukcapil_rejected',
                    'reject_reason' => $validated['reject_reason'],
                    'rejected_by' => $user->id,
                ]
            );

            Log::channel('workflow')->info('Disdukcapil rejected case - sent back to PA', [
                'case_id' => $case->id,
                'rejected_by' => $user->id,
                'reason' => $validated['reject_reason'],
            ]);

            $successMessage = 'Kasus berhasil dikembalikan ke PA Management. PA Assistant dapat mengajukan ulang setelah perbaikan.';

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => $successMessage,
                    'case_id' => $case->id,
                    'status' => $case->status,
                ]);
            }

            return redirect()->route('dashboard.disdukcapil.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error('Disdukcapil reject error', [
                'case_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $errorMessage = 'Gagal mengirim kasus kembali: ' . $e->getMessage();

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['message' => $errorMessage], 500);
            }

            return redirect()->back()
                ->with('error', $errorMessage);
        }
    }

    /**
     * Download document from case
     * GET /dashboard/disdukcapil/cases/{id}/documents/{docId}/download
     */
    public function downloadDocument(int $caseId, int $docId)
    {
        $case = CaseModel::findOrFail($caseId);
        $document = Document::where('case_id', $caseId)
            ->where('id', $docId)
            ->firstOrFail();

        $disk = Storage::disk($document->disk ?? 'local');
        $path = $document->path ?? $document->stored_path ?? '';

        if (!$disk->exists($path)) {
            abort(404, 'File tidak ditemukan');
        }

        return $disk->download($path, $document->original_name ?? 'document');
    }

    /**
     * Get completed cases for archive view
     * GET /dashboard/disdukcapil/archive
     */
    public function archive()
    {
        $cases = CaseModel::where('status', 'COMPLETED')
            ->whereHas('documents', function ($q) {
                $q->whereIn('document_type', ['BAST', 'DIGITAL_COPY']);
            })
            ->with(['publicSubmission', 'documents', 'assignedDisdukcapilUser'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('dashboard.disdukcapil.archive', compact('cases'));
    }
}
