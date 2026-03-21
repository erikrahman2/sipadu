<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\Document;
use App\Models\AuditLog;
use App\Models\IntegrationQueue;
use App\Models\OcrResult;
use App\Models\PublicSubmission;
use App\Services\ReBACService;
use App\Services\GraphService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ReBACService $rebac,
        private readonly GraphService $graph
    ) {}

    public function index(): \Illuminate\Http\RedirectResponse|\Illuminate\View\View
    {
        $user = auth()->user();

        // Super admin tidak memiliki akses ke dashboard kasus
        // Selalu redirect ke panel admin mereka
        if ($user->hasRole('super_admin')) {
            $message = session()->has('error') 
                ? 'Halaman tersebut hanya untuk petugas lapangan. Gunakan menu Administrasi di bawah.'
                : null;
            
            return redirect()->route('dashboard.admin.users')
                ->with('info', $message);
        }

        $stats = $this->buildStats($user);
        
        // Gabungkan cases dan public submissions untuk recent items
        $recentCases = CaseModel::with('institution:id,name', 'submitter:id,name')
            ->forUser($user)
            ->selectRaw('id, case_number, tracking_token, petitioner_name, status, institution_id, submitter_id, created_at, updated_at, \'case\' as source_type')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();
        
        $recentSubmissions = PublicSubmission::with('processor:id,name')
            ->whereIn('status', ['PENDING', 'REVIEWING', 'APPROVED'])
            ->selectRaw('id, tracking_token, petitioner_name as applicant_name, status, processed_by, created_at, updated_at, \'public\' as source_type')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();
        
        // Merge dan sort by updated_at
        $recentItems = $recentCases->concat($recentSubmissions)
            ->sortByDesc('updated_at')
            ->take(8);
        
        return view('dashboard.index', compact('user', 'stats', 'recentItems'));
    }

    public function cases(Request $request): \Illuminate\Http\RedirectResponse|View
    {
        $user = auth()->user();

        // PA Management should use dedicated OCR review workspace.
        if ($user->hasRole('pa_management')) {
            return redirect()->route('dashboard.review.cases');
        }

        $type = $request->query('type', 'all'); // all, cases, public
        $status = $request->query('status');
        
        // Ambil kasus manual
        $casesQuery = CaseModel::query()
            ->with('institution:id,name', 'submitter:id,name')
            ->forUser($user)
            ->selectRaw('id, case_number, tracking_token, petitioner_name, status, institution_id, submitter_id, spouse_name, divorce_date, created_at, updated_at, \'case\' as source_type');
        
        if ($status) {
            $casesQuery->byStatus($status);
        }
        
        // Ambil pengajuan publik yang belum jadi kasus
        $submissionsQuery = PublicSubmission::query()
            ->with('processor:id,name')
            ->whereIn('status', ['PENDING', 'REVIEWING'])
            ->selectRaw('id, tracking_token, NULL as case_number, petitioner_name as applicant_name, status, processed_by, respondent_name as spouse_name, divorce_date, created_at, updated_at, \'public\' as source_type');
        
        if ($status && in_array($status, ['PENDING', 'REVIEWING'])) {
            $submissionsQuery->where('status', $status);
        }
        
        // Filter by type
        if ($type === 'cases') {
            $allItems = $casesQuery->orderByDesc('created_at')->paginate(15);
        } elseif ($type === 'public') {
            $allItems = $submissionsQuery->orderByDesc('created_at')->paginate(15);
        } else {
            // Gabungkan keduanya
            $cases = $casesQuery->get();
            $submissions = $submissionsQuery->get();
            
            $allItems = $cases->concat($submissions)
                ->sortByDesc('created_at')
                ->values();
            
            // Manual pagination
            $currentPage = $request->query('page', 1);
            $perPage = 15;
            $total = $allItems->count();
            $items = $allItems->forPage($currentPage, $perPage);
            
            $allItems = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }
        
        // Count statistics
        $counts = [
            'all' => CaseModel::forUser($user)->count() + PublicSubmission::whereIn('status', ['PENDING', 'REVIEWING'])->count(),
            'cases' => CaseModel::forUser($user)->count(),
            'public' => PublicSubmission::whereIn('status', ['PENDING', 'REVIEWING'])->count(),
            'pending' => PublicSubmission::where('status', 'PENDING')->count(),
            'reviewing' => PublicSubmission::where('status', 'REVIEWING')->count(),
        ];
        
        return view('dashboard.cases.index', ['cases' => $allItems, 'counts' => $counts, 'currentType' => $type]);
    }

    public function createCase(): View
    {
        $institutions = \App\Models\Institution::active()->get(['id', 'name', 'type']);
        return view('dashboard.cases.create', compact('institutions'));
    }

    public function storeCase(Request $request)
    {
        $request->validate([
            'nik'            => 'required|digits:16',
            'petitioner_name'=> 'required|string|max:255',
            'phone_wa'       => ['required', 'string', 'max:20', 'regex:/^[0-9]{9,15}$/'],
            'institution_id' => 'required|exists:institutions,id',
            'spouse_nik'     => 'nullable|digits:16',
            'spouse_name'    => 'nullable|string|max:255',
            'divorce_date'   => 'nullable|date|before_or_equal:today',
            'verdict_number' => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:1000',
            'agreement'      => 'required|accepted',
            'documents'      => 'nullable|array',
            'documents.KTP'  => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'documents.*'    => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'nik.required'           => 'NIK pemohon wajib diisi.',
            'nik.digits'             => 'NIK harus 16 digit angka.',
            'petitioner_name.required' => 'Nama pemohon wajib diisi.',
            'phone_wa.required'      => 'Nomor WhatsApp wajib diisi.',
            'phone_wa.regex'         => 'Format nomor WhatsApp tidak valid (gunakan angka saja, 9-15 digit).',
            'institution_id.required' => 'Institusi wajib dipilih.',
            'spouse_nik.digits'      => 'NIK pasangan harus 16 digit angka.',
            'divorce_date.before_or_equal' => 'Tanggal perceraian tidak boleh di masa depan.',
            'agreement.required'     => 'Anda harus menyetujui pernyataan kebenaran data.',
            'agreement.accepted'     => 'Anda harus menyetujui pernyataan kebenaran data.',
            'documents.KTP.required' => 'Dokumen KTP Pemohon wajib diunggah.',
            'documents.KTP.mimes'    => 'Format file KTP harus JPG, PNG, atau PDF.',
            'documents.KTP.max'      => 'Ukuran file KTP maksimal 5 MB.',
            'documents.*.mimes'      => 'Format file harus JPG, PNG, atau PDF.',
            'documents.*.max'        => 'Ukuran file maksimal 5 MB.',
        ]);

        // === Validasi Business Logic (menggunakan PublicSubmission methods) ===
        $petitionerNik = $request->nik;
        $spouseNik = $request->spouse_nik;

        // Validasi 1: NIK pemohon ≠ NIK pasangan
        if (\App\Models\PublicSubmission::isSameNik($petitionerNik, $spouseNik)) {
            return back()->withErrors([
                'spouse_nik' => 'NIK pemohon tidak boleh sama dengan NIK pasangan.',
            ])->withInput();
        }

        // Validasi 2: Periksa apakah NIK dibekukan (sudah dalam proses)
        if (\App\Models\PublicSubmission::isNikFrozen($petitionerNik)) {
            $reason = \App\Models\PublicSubmission::getFrozenReason($petitionerNik);
            
            $statusLabel = match($reason['status']) {
                'REVIEWING' => 'sedang ditinjau petugas',
                'WAITING_OCR' => 'sedang menunggu proses OCR',
                'APPROVED' => 'sudah disetujui dan menunggu validasi akhir',
                'PA_REVIEW' => 'sedang direview oleh Pengadilan Agama',
                'DISDUKCAPIL_VALIDATION' => 'sedang divalidasi oleh Disdukcapil',
                default => 'dalam proses',
            };

            $message = "⚠️ PERINGATAN: NIK ini {$statusLabel}. ";
            
            if ($reason['type'] === 'public_submission') {
                $message .= "Token pengajuan publik: {$reason['token']}.";
            } else {
                $message .= "Nomor kasus: {$reason['case_number']}.";
            }
            
            $message .= " Harap koordinasi dengan pihak terkait sebelum membuat kasus baru atau pastikan tidak ada duplikasi data.";

            return back()->withErrors([
                'nik' => $message,
            ])->withInput();
        }

        // Validasi 3: Cek apakah pasangan NIK sudah terdaftar di Disdukcapil
        if ($spouseNik && \App\Models\PublicSubmission::hasCoupleInDisdukcapil($petitionerNik, $spouseNik)) {
            return back()->withErrors([
                'spouse_nik' => 'Pasangan dengan NIK ini sudah terdaftar dan sedang diproses di Disdukcapil. Tidak dapat membuat kasus baru dengan kombinasi NIK yang sama.',
            ])->withInput();
        }

        $user = auth()->user();

        // Normalize legacy/non-enum document keys from form uploads.
        $documentTypeAliases = [
            'SURAT_NIKAH' => 'AKTA_NIKAH',
            'FOTO_DIRI'   => 'OTHER',
            'LAINNYA'     => 'OTHER',
        ];
        $allowedDocumentTypes = [
            'KTP',
            'KK',
            'AKTA_CERAI',
            'PUTUSAN_PA',
            'AKTA_NIKAH',
            'SURAT_PENGANTAR',
            'OTHER',
        ];

        if ($request->hasFile('documents')) {
            foreach (array_keys($request->file('documents')) as $docTypeKey) {
                $normalizedType = $documentTypeAliases[$docTypeKey] ?? $docTypeKey;
                if (!in_array($normalizedType, $allowedDocumentTypes, true)) {
                    return back()->withErrors([
                        'documents' => "Jenis dokumen {$docTypeKey} tidak didukung.",
                    ])->withInput();
                }
            }
        }

        $case = DB::transaction(function () use ($request, $user, $petitionerNik, $spouseNik) {
            $case = CaseModel::create([
                'submitter_id'      => $user->id,
                'petitioner_nik'    => $request->nik,
                'petitioner_name'   => $request->petitioner_name,
                'petitioner_phone'  => $request->phone_wa,
                'institution_id'    => $request->institution_id,
                'spouse_nik'        => $request->spouse_nik,
                'spouse_name'       => $request->spouse_name,
                'divorce_date'      => $request->divorce_date,
                'verdict_number'    => $request->verdict_number,
                'notes'             => $request->notes,
                // Auto-submit once data and documents are posted from this form.
                'status'            => 'SUBMITTED',
                'submitted_at'      => now(),
            ]);

            \App\Models\CaseTransition::create([
                'case_id'         => $case->id,
                'from_state'      => 'DRAFT',
                'to_state'        => 'SUBMITTED',
                'transitioned_by' => $user->id,
                'reason'          => 'Auto-submit setelah pengajuan dan upload dokumen.',
                'metadata'        => ['source' => 'dashboard.storeCase'],
            ]);

            // Replace data lama dengan NIK yang sama (hanya DRAFT)
            $replaced = CaseModel::replaceOldCases($petitionerNik, $spouseNik, $case->id);
            if ($replaced > 0) {
                \Log::info("Case: Replaced {$replaced} old DRAFT case(s)", [
                    'new_case_id' => $case->id,
                    'petitioner_nik' => $petitionerNik,
                    'spouse_nik' => $spouseNik,
                ]);
            }

            // Handle document uploads
            if ($request->hasFile('documents')) {
                $documentTypeAliases = [
                    'SURAT_NIKAH' => 'AKTA_NIKAH',
                    'FOTO_DIRI'   => 'OTHER',
                    'LAINNYA'     => 'OTHER',
                ];

                foreach ($request->file('documents') as $docType => $file) {
                    $normalizedDocType = $documentTypeAliases[$docType] ?? $docType;

                    $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs("cases/{$case->id}", $storedName, 'public');
                    $checksum = hash_file('sha256', $file->getPathname());
                    
                    $document = \App\Models\Document::create([
                        'case_id'       => $case->id,
                        'uploaded_by'   => $user->id,
                        'original_name' => $file->getClientOriginalName(),
                        'stored_name'   => $storedName,
                        'disk'          => 'public',
                        'path'          => $path,
                        'mime_type'     => $file->getMimeType(),
                        'size_bytes'    => $file->getSize(),
                        'document_type' => $normalizedDocType,
                        'checksum'      => $checksum,
                        // status default: PENDING (from migration)
                    ]);
                    
                    // Dispatch DocumentUploaded event for OCR processing
                    event(new \App\Events\DocumentUploaded($document));
                }
            }

            // Outbox event
            \App\Models\IntegrationQueue::create([
                'aggregate_type' => 'Case',
                'aggregate_id'   => $case->id,
                'event_type'     => 'created',
                'payload'        => ['institution_id' => $case->institution_id, 'submitter_id' => $user->id],
                'available_at'   => now(),
            ]);

            return $case;
        });

        // Sync to Neo4j immediately for ReBAC (prevent 403 on redirect)
        try {
            $this->graph->upsertCase([
                'id'               => $case->id,
                'case_number'      => $case->case_number,
                'tracking_token'   => $case->tracking_token,
                'status'           => $case->status,
                'submitter_id'     => $case->submitter_id,
                'institution_id'   => $case->institution_id,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Neo4j sync failed after case creation', [
                'case_id' => $case->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return redirect()->route('dashboard.cases.show', $case->id)
            ->with('success', 'Kasus berhasil dibuat dengan tracking token: ' . $case->tracking_token);
    }

    public function showCase(int $id): \Illuminate\Http\RedirectResponse|View
    {
        $case = CaseModel::with([
            'submitter:id,name,email',
            'institution:id,name,type',
            'documents.ocrResult',
            'transitions.actor:id,name',
            'ocrValidations' => function ($query) {
                $query->with('document', 'ocrResult', 'reviewer')
                      ->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        // ReBAC: PA Management dan Super Admin bisa lihat semua case
        if (!auth()->user()->hasAnyRole(['pa_management', 'super_admin'])) {
            $this->rebac->enforce(auth()->user(), 'view', 'Case', $id);
        }

        // PA Management and Super Admin should use dedicated OCR review page.
        if (auth()->user()->hasAnyRole(['pa_management', 'super_admin'])) {
            return redirect()->route('dashboard.review.show', $id);
        }

        return view('dashboard.cases.show', compact('case'));
    }

    public function upload(): View
    {
        $cases = CaseModel::forUser(auth()->user())
            ->whereIn('status', ['DRAFT', 'SUBMITTED'])
            ->get(['id', 'case_number']);
        return view('dashboard.upload', compact('cases'));
    }

    public function tracking(): View
    {
        return view('dashboard.tracking');
    }

    public function ocrResult(int $id): View
    {
        $document = Document::with('ocrResult')->findOrFail($id);
        
        // ReBAC: PA Management dan Super Admin bisa lihat semua document
        if (!auth()->user()->hasAnyRole(['pa_management', 'super_admin'])) {
            $this->rebac->enforce(auth()->user(), 'view', 'Document', $id);
        }
        
        return view('dashboard.ocr-result', compact('document'));
    }

    // ── Admin ─────────────────────────────────────────────────────────────────

    public function users(): View
    {
        $users = \App\Models\User::with('roles', 'institution:id,name')->paginate(20);
        return view('dashboard.admin.users', compact('users'));
    }

    public function syncStatus(): View
    {
        $stats = [
            'pending'    => IntegrationQueue::where('status', 'PENDING')->count(),
            'processing' => IntegrationQueue::where('status', 'PROCESSING')->count(),
            'success'    => IntegrationQueue::where('status', 'SUCCESS')->count(),
            'failed'     => IntegrationQueue::where('status', 'FAILED')->count(),
        ];
        $recent = \App\Models\GraphSyncLog::latest()->limit(50)->get();
        return view('dashboard.admin.sync', compact('stats', 'recent'));
    }

    public function audit(): View
    {
        $logs = AuditLog::with('user:id,name')->latest()->paginate(50);
        return view('dashboard.admin.audit', compact('logs'));
    }

    public function logs(Request $request): View
    {
        $query = \App\Models\AccessLog::with('user:id,name');

        $filterPath   = $request->query('path');
        $filterStatus = $request->query('status');

        if ($filterPath) {
            $query->where('path', 'like', '%' . $filterPath . '%');
        }
        if ($filterStatus === 'ok') {
            $query->whereBetween('status_code', [200, 299]);
        } elseif ($filterStatus === 'error') {
            $query->where('status_code', '>=', 400);
        } elseif ($filterStatus === 'slow') {
            $query->where('response_time_ms', '>', 1000);
        }

        $logs          = $query->latest()->paginate(50);
        $total         = \App\Models\AccessLog::count();
        $successCount  = \App\Models\AccessLog::whereBetween('status_code', [200, 299])->count();
        $errorCount    = \App\Models\AccessLog::where('status_code', '>=', 400)->count();
        $avgResponseMs = (int) (\App\Models\AccessLog::avg('response_time_ms') ?? 0);

        return view('dashboard.admin.logs', compact('logs', 'total', 'successCount', 'errorCount', 'avgResponseMs'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildStats(\App\Models\User $user): array
    {
        $q = CaseModel::forUser($user);
        $publicPending = PublicSubmission::whereIn('status', ['PENDING', 'REVIEWING'])->count();
        
        return [
            'total'       => (clone $q)->count() + $publicPending,
            'draft'       => (clone $q)->byStatus('DRAFT')->count(),
            'in_progress' => (clone $q)->whereNotIn('status', ['DRAFT','COMPLETED','ARCHIVED','REJECTED'])->count() + $publicPending,
            'completed'   => (clone $q)->byStatus('COMPLETED')->count(),
            'rejected'    => (clone $q)->byStatus('REJECTED')->count() + PublicSubmission::where('status', 'REJECTED')->count(),
        ];
    }
}
