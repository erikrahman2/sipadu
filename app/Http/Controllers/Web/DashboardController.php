<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\Document;
use App\Models\AuditLog;
use App\Models\IntegrationQueue;
use App\Models\OcrResult;
use App\Models\PublicSubmission;
use App\Services\DocumentTypeMapper;
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
        
        // Ambil data grafik untuk PA Assistant (7 bulan terakhir)
        $chartData = [];
        if ($user->hasRole('pa_assistant')) {
            $chartData = $this->buildChartData();
        }
        
        // Gunakan schema yang sama (CaseModel) untuk internal + public, bedanya hanya source_type.
        // Disdukcapil staff hanya lihat DISDUKCAPIL_VALIDATION cases
        // PA Management & Super Admin melihat SEMUA cases (untuk review)
        $recentItemsQuery = CaseModel::with('institution:id,name', 'submitter:id,name')
            ->selectRaw('id, case_number, tracking_token, petitioner_name, status, institution_id, submitter_id, source_type, created_at, updated_at, completed_at');

        if ($user->hasRole('disdukcapil_staff')) {
            $recentItemsQuery->where('status', 'DISDUKCAPIL_VALIDATION');
        } elseif (!$user->hasRole('pa_management') && !$user->hasRole('super_admin')) {
            // PA Assistant, PA Staff: filter by institution
            $recentItemsQuery->forUser($user);
        }
        // PA Management & Super Admin: no filter (see all cases)

        $recentItems = $recentItemsQuery->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        // Arsip terbaru untuk PA Staff (kasus selesai)
        $recentArchives = collect();
        $archiveCounts = ['completed' => 0, 'archived' => 0];
        if ($user->hasRole('pa_staff')) {
            $recentArchives = CaseModel::with('institution:id,name', 'submitter:id,name')
                ->whereIn('status', ['COMPLETED', 'ARCHIVED'])
                ->forUser($user)
                ->selectRaw('id, case_number, tracking_token, petitioner_name, spouse_name, status, source_type, completed_at, updated_at')
                ->orderByDesc('completed_at')
                ->limit(5)
                ->get();
            $archiveCounts['completed'] = CaseModel::forUser($user)->where('status', 'COMPLETED')->count();
            $archiveCounts['archived']  = CaseModel::forUser($user)->where('status', 'ARCHIVED')->count();
        }

        return view('dashboard.index', compact('user', 'stats', 'recentItems', 'chartData', 'recentArchives', 'archiveCounts'));
    }

    public function cases(Request $request): \Illuminate\Http\RedirectResponse|View
    {
        $user = auth()->user();

        // PA Management should use dedicated OCR review workspace.
        if ($user->hasRole('pa_management')) {
            return redirect()->route('dashboard.review.cases');
        }

        $type = $request->query('type', 'all'); // all, manual, public
        $status = $request->query('status');

        // Semua data dashboard mengikuti schema kasus yang sama.
        if ($user->hasRole('disdukcapil_staff')) {
            // Disdukcapil staff: ONLY see cases in DISDUKCAPIL_VALIDATION status
            $casesQuery = CaseModel::query()
                ->where('status', 'DISDUKCAPIL_VALIDATION')
                ->with('institution:id,name', 'submitter:id,name')
                ->selectRaw('id, case_number, tracking_token, petitioner_name, status, institution_id, submitter_id, spouse_name, divorce_date, source_type, public_submission_id, created_at, updated_at');
        } else {
            // Other roles: use forUser scope
            $casesQuery = CaseModel::query()
                ->with('institution:id,name', 'submitter:id,name')
                ->forUser($user)
                ->selectRaw('id, case_number, tracking_token, petitioner_name, status, institution_id, submitter_id, spouse_name, divorce_date, source_type, public_submission_id, created_at, updated_at');
        }

        if ($status) {
            $casesQuery->byStatus($status);
        }

        // Filter by type
        if ($type === 'manual') {
            $allItems = (clone $casesQuery)
                ->where('source_type', '!=', 'public')
                ->orderByDesc('created_at')
                ->paginate(15);
        } elseif ($type === 'public') {
            $allItems = (clone $casesQuery)
                ->where('source_type', 'public')
                ->orderByDesc('created_at')
                ->paginate(15);
        } else {
            $allItems = $casesQuery->orderByDesc('created_at')->paginate(15);
        }

        // Count statistics
        if ($user->hasRole('disdukcapil_staff')) {
            $baseQuery = CaseModel::where('status', 'DISDUKCAPIL_VALIDATION');
        } else {
            $baseQuery = CaseModel::forUser($user);
        }

        $counts = [
            'all' => (clone $baseQuery)->count(),
            'cases' => (clone $baseQuery)->where('source_type', 'internal')->count(),
            'public' => (clone $baseQuery)->where('source_type', 'public')->count(),
            'submitted' => (clone $baseQuery)->where('status', 'SUBMITTED')->count(),
            'approved' => (clone $baseQuery)->where('status', 'OCR_PROCESSED')->count(),
            'validation_pending' => (clone $baseQuery)->where('status', 'DISDUKCAPIL_VALIDATION')->count(),
            'validation_completed' => (clone $baseQuery)->where('status', 'COMPLETED')->count(),
            'validation_rejected' => (clone $baseQuery)->where('status', 'REJECTED')->count(),
        ];
        
        return view('dashboard.cases.index', ['cases' => $allItems, 'counts' => $counts, 'currentType' => $type]);
    }

    public function createCase(): View
    {
        $institutions = \App\Models\Institution::active()->get(['id', 'name', 'type']);
        $ceraiOptions = $this->ceraiOptions();
        return view('dashboard.cases.create', compact('institutions', 'ceraiOptions'));
    }

    public function storeCase(Request $request)
    {
        $maxSizeByte = 10240;
        $ceraiOptions = $this->ceraiOptions();
        $ceraiType = $request->input('cerai_type');
        $isGroupedFlow = is_string($ceraiType) && array_key_exists($ceraiType, $ceraiOptions);

        // Clean phone number
        $phoneWa = $request->input('phone_wa', '');
        $phoneWa = preg_replace('/^(\+62|0)/', '', $phoneWa);
        $phoneWa = preg_replace('/\s+/', '', $phoneWa);
        $phoneWa = preg_replace('/[-.]/', '', $phoneWa);
        $request->merge(['phone_wa' => $phoneWa]);

        $rules = [
            'suami_nik'      => 'required|digits:16',
            'suami_name'     => 'required|string|max:255',
            'suami_alamat'   => 'required|string|max:255',
            'suami_rt_rw'    => ['required', 'string', 'max:10', 'regex:/^\d{3}\/\d{3}$/'],
            'suami_kelurahan'=> 'required|string|max:255',
            'suami_kecamatan'=> 'required|string|max:255',
            'istri_nik'      => 'required|digits:16',
            'istri_name'     => 'required|string|max:255',
            'istri_alamat'   => 'required|string|max:255',
            'istri_rt_rw'    => ['required', 'string', 'max:10', 'regex:/^\d{3}\/\d{3}$/'],
            'istri_kelurahan'=> 'required|string|max:255',
            'istri_kecamatan'=> 'required|string|max:255',
            'phone_wa'       => ['required', 'string', 'max:20', 'regex:/^[0-9]{9,15}$/'],
            'institution_id' => 'required|exists:institutions,id',
            'cerai_type'     => 'nullable|in:' . implode(',', array_keys($ceraiOptions)),
            'divorce_date'   => 'nullable|date|before_or_equal:today',
            'verdict_number' => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:1000',
            'agreement'      => 'required|accepted',
            'documents'      => 'nullable|array',
            'remove_documents' => 'nullable|array',
            'remove_documents.*' => 'nullable|exists:documents,id',
        ];

        $messages = [
            'suami_nik.required'     => 'NIK suami wajib diisi.',
            'suami_nik.digits'       => 'NIK suami harus 16 digit angka.',
            'suami_name.required'    => 'Nama suami wajib diisi.',
            'suami_alamat.required'  => 'Alamat suami wajib diisi.',
            'suami_rt_rw.required'   => 'RT/RW suami wajib diisi.',
            'suami_rt_rw.regex'      => 'Format RT/RW suami harus 000/000.',
            'suami_kelurahan.required' => 'Kelurahan suami wajib diisi.',
            'suami_kecamatan.required' => 'Kecamatan suami wajib diisi.',
            'istri_nik.required'     => 'NIK istri wajib diisi.',
            'istri_nik.digits'       => 'NIK istri harus 16 digit angka.',
            'istri_name.required'    => 'Nama istri wajib diisi.',
            'istri_alamat.required'  => 'Alamat istri wajib diisi.',
            'istri_rt_rw.required'   => 'RT/RW istri wajib diisi.',
            'istri_rt_rw.regex'      => 'Format RT/RW istri harus 000/000.',
            'istri_kelurahan.required' => 'Kelurahan istri wajib diisi.',
            'istri_kecamatan.required' => 'Kecamatan istri wajib diisi.',
            'phone_wa.required'      => 'Nomor WhatsApp wajib diisi.',
            'phone_wa.regex'         => 'Format nomor WhatsApp tidak valid (gunakan angka saja, 9-15 digit).',
            'institution_id.required' => 'Institusi wajib dipilih.',
            'divorce_date.before_or_equal' => 'Tanggal perceraian tidak boleh di masa depan.',
            'agreement.required'     => 'Anda harus menyetujui pernyataan kebenaran data.',
            'agreement.accepted'     => 'Anda harus menyetujui pernyataan kebenaran data.',
            'documents.*.mimes'      => 'Format file harus JPG, PNG, atau PDF.',
            'documents.*.max'        => 'Ukuran file maksimal 10 MB.',
        ];

        if ($isGroupedFlow) {
            $rules['cerai_type'] = 'required|in:' . implode(',', array_keys($ceraiOptions));
            $requiredDocs = $this->documentsForCeraiType($ceraiType);

            // Gunakan nullable untuk documents - validasi menggunakan struktur documents[CERAI_TYPE][DOC_TYPE]
            $rules['documents'] = 'nullable|array';

            foreach ($requiredDocs as $documentType) {
                $rules['documents.' . $ceraiType . '.' . $documentType] = 'required|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte;
                $messages['documents.' . $ceraiType . '.' . $documentType . '.required'] = 'Dokumen ' . ($ceraiOptions[$ceraiType]['docs'][$documentType] ?? $documentType) . ' wajib diunggah.';
            }
        } else {
            $rules['documents'] = 'nullable|array';
            $rules['documents.cerai_normal.KTP_SUAMI'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte;
            $rules['documents.cerai_normal.KTP_ISTRI'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:' . $maxSizeByte;
            $messages['documents.cerai_normal.KTP_SUAMI.required'] = 'Dokumen KTP suami wajib diunggah.';
            $messages['documents.cerai_normal.KTP_ISTRI.required'] = 'Dokumen KTP istri wajib diunggah.';
        }

        $validated = $request->validate($rules, $messages);

        // === Validasi Business Logic ===
        $petitionerNik = $request->suami_nik;
        $spouseNik = $request->istri_nik;

        if (\App\Models\PublicSubmission::isSameNik($petitionerNik, $spouseNik)) {
            return back()->withErrors([
                'istri_nik' => 'NIK suami tidak boleh sama dengan NIK istri.',
            ])->withInput();
        }

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
                'suami_nik' => $message,
            ])->withInput();
        }

        if ($spouseNik && \App\Models\PublicSubmission::hasCoupleInDisdukcapil($petitionerNik, $spouseNik)) {
            return back()->withErrors([
                'istri_nik' => 'Pasangan dengan NIK ini sudah terdaftar dan sedang diproses di Disdukcapil. Tidak dapat membuat kasus baru dengan kombinasi NIK yang sama.',
            ])->withInput();
        }

        $user = auth()->user();

        // Normalize document type aliases - use ceraiType to get correct document types
        // Fallback to cerai_normal if ceraiType is not set
        $effectiveCeraiType = $ceraiType ?: 'cerai_normal';
        $selectedDocs = $ceraiOptions[$effectiveCeraiType]['docs'] ?? $ceraiOptions['cerai_normal']['docs'];
        $allowedDocumentTypes = array_keys($selectedDocs);

        // Validate documents with structure documents[CERAI_TYPE][DOC_TYPE]
        $docFiles = $request->file('documents.' . $effectiveCeraiType) ?: [];
        foreach (array_keys($docFiles) as $docTypeKey) {
            $normalizedType = DocumentTypeMapper::toCaseType($docTypeKey);
            if (!in_array($normalizedType, $allowedDocumentTypes, true)) {
                return back()->withErrors([
                    'documents' => "Jenis dokumen {$docTypeKey} tidak didukung.",
                ])->withInput();
            }
        }

        $case = DB::transaction(function () use ($request, $user, $petitionerNik, $spouseNik, $ceraiType) {
            $case = CaseModel::create([
                'submitter_id'      => $user->id,
                'petitioner_nik'    => $request->suami_nik,
                'petitioner_name'   => $request->suami_name,
                'petitioner_phone'  => $request->phone_wa,
                'petitioner_alamat' => $request->suami_alamat,
                'petitioner_rt_rw' => $request->suami_rt_rw,
                'petitioner_kelurahan' => $request->suami_kelurahan,
                'petitioner_kecamatan' => $request->suami_kecamatan,
                'cerai_type'        => $ceraiType ?: null,
                'institution_id'    => $request->institution_id,
                'spouse_nik'        => $request->istri_nik,
                'spouse_name'       => $request->istri_name,
                'spouse_alamat'     => $request->istri_alamat,
                'spouse_rt_rw'      => $request->istri_rt_rw,
                'spouse_kelurahan'  => $request->istri_kelurahan,
                'spouse_kecamatan'  => $request->istri_kecamatan,
                'divorce_date'      => $request->divorce_date,
                'verdict_number'    => $request->verdict_number,
                'notes'             => $request->notes,
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

            // Handle document uploads - structure: documents[CERAI_TYPE][DOC_TYPE]
            $docFiles = $request->file('documents.' . $ceraiType) ?: [];
            foreach ($docFiles as $docType => $file) {
                if (!$file || !$file->isValid()) continue;

                $normalizedDocType = DocumentTypeMapper::toCaseType($docType);

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

    /**
     * Create case from public submission
     * POST /dashboard/cases/from-public/{publicSubmissionId}
     */
    public function createFromPublicSubmission(PublicSubmission $submission)
    {
        $user = auth()->user();

        // Only PA Assistant and PA Management can create cases from public submissions
        if (!$user->hasAnyRole(['pa_assistant', 'pa_management'])) {
            abort(403, 'Unauthorized.');
        }

        // Check if already converted
        if ($submission->case_id) {
            return redirect()->route('dashboard.cases.show', $submission->case_id)
                ->with('info', 'Pengajuan publik ini sudah dikonversi menjadi kasus.');
        }

        try {
            $service = new \App\Services\PublicSubmissionToCaseService();
            $case = $service->convertToCase($submission, $user->id);

            return redirect()->route('dashboard.cases.show', $case->id)
                ->with('success', 'Pengajuan publik berhasil dikonversi menjadi kasus dengan nomor: ' . $case->case_number);
        } catch (\Throwable $e) {
            \Log::error('Error converting public submission to case', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal mengkonversi pengajuan publik. Silakan coba lagi atau hubungi administrator.');
        }
    }

    /**
     * Simpan case sebagai DRAFT dengan validasi relaksasi
     */
    public function saveDraftCase(Request $request)
    {
        $ceraiOptions = $this->ceraiOptions();
        $ceraiType = $request->input('cerai_type');
        $isGroupedFlow = is_string($ceraiType) && array_key_exists($ceraiType, $ceraiOptions);

        // Clean phone number
        $phoneWa = $request->input('phone_wa', '');
        $phoneWa = preg_replace('/^(\+62|0)/', '', $phoneWa);
        $phoneWa = preg_replace('/\s+/', '', $phoneWa);
        $phoneWa = preg_replace('/[-.]/', '', $phoneWa);
        $request->merge(['phone_wa' => $phoneWa]);

        // Validasi minimal - lebih relaksasi dari submit
        $rules = [
            'suami_nik'      => 'required|digits:16',
            'suami_name'     => 'required|string|max:255',
            'suami_alamat'   => 'required|string|max:255',
            'suami_rt_rw'    => ['required', 'string', 'max:10', 'regex:/^\d{3}\/\d{3}$/'],
            'suami_kelurahan'=> 'required|string|max:255',
            'suami_kecamatan'=> 'required|string|max:255',
            'istri_nik'      => 'required|digits:16',
            'istri_name'     => 'required|string|max:255',
            'istri_alamat'   => 'required|string|max:255',
            'istri_rt_rw'    => ['required', 'string', 'max:10', 'regex:/^\d{3}\/\d{3}$/'],
            'istri_kelurahan'=> 'required|string|max:255',
            'istri_kecamatan'=> 'required|string|max:255',
            'phone_wa'       => ['required', 'string', 'max:20', 'regex:/^[0-9]{9,15}$/'],
            'institution_id' => 'required|exists:institutions,id',
            'cerai_type'     => 'nullable|in:' . implode(',', array_keys($ceraiOptions)),
            'divorce_date'   => 'nullable|date|before_or_equal:today',
            'verdict_number' => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:1000',
            'documents'      => 'nullable|array',
            'remove_documents' => 'nullable|array',
            'remove_documents.*' => 'nullable|exists:documents,id',
        ];

        $messages = [
            'suami_nik.required'     => 'NIK suami wajib diisi.',
            'suami_nik.digits'       => 'NIK suami harus 16 digit angka.',
            'suami_name.required'    => 'Nama suami wajib diisi.',
            'suami_alamat.required'  => 'Alamat suami wajib diisi.',
            'suami_rt_rw.required'   => 'RT/RW suami wajib diisi.',
            'suami_rt_rw.regex'      => 'Format RT/RW suami harus 000/000.',
            'suami_kelurahan.required' => 'Kelurahan suami wajib diisi.',
            'suami_kecamatan.required' => 'Kecamatan suami wajib diisi.',
            'istri_nik.required'     => 'NIK istri wajib diisi.',
            'istri_nik.digits'       => 'NIK istri harus 16 digit angka.',
            'istri_name.required'    => 'Nama istri wajib diisi.',
            'istri_alamat.required'  => 'Alamat istri wajib diisi.',
            'istri_rt_rw.required'   => 'RT/RW istri wajib diisi.',
            'istri_rt_rw.regex'      => 'Format RT/RW istri harus 000/000.',
            'istri_kelurahan.required' => 'Kelurahan istri wajib diisi.',
            'istri_kecamatan.required' => 'Kecamatan istri wajib diisi.',
            'phone_wa.required'      => 'Nomor WhatsApp wajib diisi.',
            'phone_wa.regex'         => 'Format nomor WhatsApp tidak valid (gunakan angka saja, 9-15 digit).',
            'institution_id.required' => 'Institusi wajib dipilih.',
            'documents.*.mimes'      => 'Format file harus JPG, PNG, atau PDF.',
            'documents.*.max'        => 'Ukuran file maksimal 10 MB.',
        ];

        if ($isGroupedFlow) {
            $rules['cerai_type'] = 'required|in:' . implode(',', array_keys($ceraiOptions));
        }

        $request->validate($rules, $messages);

        $petitionerNik = $request->suami_nik;
        $spouseNik = $request->istri_nik;

        // Validasi: NIK pemohon ≠ NIK pasangan
        if (\App\Models\PublicSubmission::isSameNik($petitionerNik, $spouseNik)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => ['istri_nik' => ['NIK suami tidak boleh sama dengan NIK istri.']]], 422);
            }
            return back()->withErrors([
                'istri_nik' => 'NIK suami tidak boleh sama dengan NIK istri.',
            ])->withInput();
        }

        $user = auth()->user();

        // Simpan sebagai DRAFT tanpa auto-submit
        $case = DB::transaction(function () use ($request, $user, $petitionerNik, $spouseNik, $ceraiType) {
            $case = CaseModel::create([
                'submitter_id'      => $user->id,
                'petitioner_nik'    => $request->suami_nik,
                'petitioner_name'   => $request->suami_name,
                'petitioner_phone'  => $request->phone_wa,
                'petitioner_alamat' => $request->suami_alamat,
                'petitioner_rt_rw' => $request->suami_rt_rw,
                'petitioner_kelurahan' => $request->suami_kelurahan,
                'petitioner_kecamatan' => $request->suami_kecamatan,
                'cerai_type'        => $ceraiType ?: null,
                'institution_id'    => $request->institution_id,
                'spouse_nik'        => $request->istri_nik,
                'spouse_name'       => $request->istri_name,
                'spouse_alamat'     => $request->istri_alamat,
                'spouse_rt_rw'      => $request->istri_rt_rw,
                'spouse_kelurahan'  => $request->istri_kelurahan,
                'spouse_kecamatan'  => $request->istri_kecamatan,
                'divorce_date'      => $request->divorce_date,
                'verdict_number'    => $request->verdict_number,
                'notes'             => $request->notes,
                'status'            => 'DRAFT', // Tetap DRAFT, tidak auto-submit
            ]);

            // Create transition log
            \App\Models\CaseTransition::create([
                'case_id'         => $case->id,
                'from_state'      => 'NEW',
                'to_state'        => 'DRAFT',
                'transitioned_by' => $user->id,
                'reason'          => 'Draft dibuat dari form PA Assistant',
                'metadata'        => ['source' => 'dashboard.saveDraftCase'],
            ]);

            // Handle document uploads jika ada
            // Form mengirim documents[CERAI_TYPE][DOC_TYPE]
            $docFiles = $request->file('documents.' . $ceraiType) ?: [];
            foreach ($docFiles as $docType => $file) {
                if (!$file || !$file->isValid()) continue; // Skip jika file tidak ada

                $normalizedDocType = DocumentTypeMapper::toCaseType($docType);

                // Delete existing document of the same type (force delete to remove from DB completely)
                Document::where('case_id', $case->id)
                    ->where('document_type', $normalizedDocType)
                    ->forceDelete();

                $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("cases/{$case->id}", $storedName, 'public');
                $checksum = hash_file('sha256', $file->getPathname());

                \App\Models\Document::create([
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
                    'status'        => 'PENDING', // OCR tidak diproses untuk draft
                ]);
            }

            return $case;
        });

        // Sync to Neo4j
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
            \Log::warning('Neo4j sync failed after draft creation', [
                'case_id' => $case->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return redirect()->route('dashboard.cases.edit-draft', $case->id)
            ->with('success', 'Draft berhasil disimpan. Silakan lengkapi dan kirim pengajuan Anda kapan saja.');
    }

    /**
     * Edit draft case
     */
    public function editDraftCase(int $id): View|\Illuminate\Http\RedirectResponse
    {
        $case = CaseModel::with('documents')->findOrFail($id);
        
        // Hanya submitter atau admin yang bisa edit
        if ($case->submitter_id !== auth()->id() && !auth()->user()->hasRole('super_admin')) {
            return back()->withErrors('Anda tidak memiliki akses untuk edit draft ini');
        }

        // Hanya status DRAFT yang bisa diedit
        if ($case->status !== 'DRAFT') {
            return back()->withErrors('Hanya draft yang belum dikirim dapat diedit');
        }

        $institutions = \App\Models\Institution::active()->get(['id', 'name', 'type']);
        $ceraiOptions = $this->ceraiOptions();

        // Create array of uploaded document types for this case (normalized to form types)
        $uploadedDocTypes = $case->documents->map(function($doc) {
            // Normalize database type to form type
            $type = $doc->document_type;
            // If it's AKTA_NIKAH, SURAT_NIKAH, or AKTA_KAWIN, use AKTA_NIKAH
            if (in_array($type, ['AKTA_NIKAH', 'SURAT_NIKAH', 'AKTA_KAWIN'])) {
                return 'AKTA_NIKAH';
            }
            // SURAT_PENGANTAR -> OTHER
            if ($type === 'SURAT_PENGANTAR') {
                return 'SURAT_PENGANTAR';
            }
            // KTP, FOTO_DIRI, LAINNYA -> OTHER
            if (in_array($type, ['KTP', 'FOTO_DIRI', 'LAINNYA'])) {
                return 'OTHER';
            }
            return $type;
        })->toArray();

        return view('dashboard.cases.edit-draft', compact('case', 'institutions', 'ceraiOptions', 'uploadedDocTypes'));
    }

    /**
     * Update draft case
     */
    public function updateDraftCase(Request $request, int $id)
    {
        $case = CaseModel::with('documents')->findOrFail($id);

        // Hanya submitter atau admin yang bisa update
        if ($case->submitter_id !== auth()->id() && !auth()->user()->hasRole('super_admin')) {
            return back()->withErrors('Anda tidak memiliki akses');
        }

        // Hanya status DRAFT yang bisa diupdate
        if ($case->status !== 'DRAFT') {
            return back()->withErrors('Hanya draft yang belum dikirim dapat diubah');
        }

        $ceraiOptions = $this->ceraiOptions();
        $ceraiType = $request->input('cerai_type');
        $isGroupedFlow = is_string($ceraiType) && array_key_exists($ceraiType, $ceraiOptions);

        // Clean phone number
        $phoneWa = $request->input('phone_wa', '');
        $phoneWa = preg_replace('/^(\+62|0)/', '', $phoneWa);
        $phoneWa = preg_replace('/\s+/', '', $phoneWa);
        $phoneWa = preg_replace('/[-.]/', '', $phoneWa);
        $request->merge(['phone_wa' => $phoneWa]);

        // Validasi yang sama dengan saveDraft
        $rules = [
            'suami_nik'      => 'required|digits:16',
            'suami_name'     => 'required|string|max:255',
            'suami_alamat'   => 'required|string|max:255',
            'suami_rt_rw'    => ['required', 'string', 'max:10', 'regex:/^\d{3}\/\d{3}$/'],
            'suami_kelurahan'=> 'required|string|max:255',
            'suami_kecamatan'=> 'required|string|max:255',
            'istri_nik'      => 'required|digits:16',
            'istri_name'     => 'required|string|max:255',
            'istri_alamat'   => 'required|string|max:255',
            'istri_rt_rw'    => ['required', 'string', 'max:10', 'regex:/^\d{3}\/\d{3}$/'],
            'istri_kelurahan'=> 'required|string|max:255',
            'istri_kecamatan'=> 'required|string|max:255',
            'phone_wa'       => ['required', 'string', 'max:20', 'regex:/^[0-9]{9,15}$/'],
            'institution_id' => 'required|exists:institutions,id',
            'cerai_type'     => 'nullable|in:' . implode(',', array_keys($ceraiOptions)),
            'divorce_date'   => 'nullable|date|before_or_equal:today',
            'verdict_number' => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:1000',
            'documents'      => 'nullable|array',
            'remove_documents' => 'nullable|array',
            'remove_documents.*' => 'nullable|exists:documents,id',
        ];

        $messages = [
            'suami_nik.required'     => 'NIK suami wajib diisi.',
            'suami_nik.digits'       => 'NIK suami harus 16 digit angka.',
            'suami_name.required'    => 'Nama suami wajib diisi.',
            'suami_alamat.required'  => 'Alamat suami wajib diisi.',
            'suami_rt_rw.required'   => 'RT/RW suami wajib diisi.',
            'suami_rt_rw.regex'      => 'Format RT/RW suami harus 000/000.',
            'suami_kelurahan.required' => 'Kelurahan suami wajib diisi.',
            'suami_kecamatan.required' => 'Kecamatan suami wajib diisi.',
            'istri_nik.required'     => 'NIK istri wajib diisi.',
            'istri_nik.digits'       => 'NIK istri harus 16 digit angka.',
            'istri_name.required'    => 'Nama istri wajib diisi.',
            'istri_alamat.required'  => 'Alamat istri wajib diisi.',
            'istri_rt_rw.required'   => 'RT/RW istri wajib diisi.',
            'istri_rt_rw.regex'      => 'Format RT/RW istri harus 000/000.',
            'istri_kelurahan.required' => 'Kelurahan istri wajib diisi.',
            'istri_kecamatan.required' => 'Kecamatan istri wajib diisi.',
            'phone_wa.required'      => 'Nomor WhatsApp wajib diisi.',
            'phone_wa.regex'         => 'Format nomor WhatsApp tidak valid (gunakan angka saja, 9-15 digit).',
            'institution_id.required' => 'Institusi wajib dipilih.',
            'documents.*.mimes'      => 'Format file harus JPG, PNG, atau PDF.',
            'documents.*.max'        => 'Ukuran file maksimal 10 MB.',
        ];

        if ($isGroupedFlow) {
            $rules['cerai_type'] = 'required|in:' . implode(',', array_keys($ceraiOptions));
        }

        $request->validate($rules, $messages);

        $petitionerNik = $request->suami_nik;
        $spouseNik = $request->istri_nik;

        // Validasi: NIK pemohon (salah satu pasangan yang memohon) tidak boleh sama dengan NIK pasangan
        if (\App\Models\PublicSubmission::isSameNik($petitionerNik, $spouseNik)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => ['istri_nik' => ['NIK suami tidak boleh sama dengan NIK istri.']]], 422);
            }
            return back()->withErrors([
                'istri_nik' => 'NIK suami tidak boleh sama dengan NIK istri.',
            ])->withInput();
        }

        $user = auth()->user();

        DB::transaction(function () use ($request, $case, $user, $ceraiType) {
            // Update case data
            $case->update([
                'petitioner_nik'    => $request->suami_nik,
                'petitioner_name'   => $request->suami_name,
                'petitioner_phone'  => $request->phone_wa,
                'petitioner_alamat' => $request->suami_alamat,
                'petitioner_rt_rw' => $request->suami_rt_rw,
                'petitioner_kelurahan' => $request->suami_kelurahan,
                'petitioner_kecamatan' => $request->suami_kecamatan,
                'cerai_type'       => $ceraiType ?: null,
                'institution_id'    => $request->institution_id,
                'spouse_nik'        => $request->istri_nik,
                'spouse_name'       => $request->istri_name,
                'spouse_alamat'     => $request->istri_alamat,
                'spouse_rt_rw'      => $request->istri_rt_rw,
                'spouse_kelurahan'  => $request->istri_kelurahan,
                'spouse_kecamatan'  => $request->istri_kecamatan,
                'divorce_date'      => $request->divorce_date,
                'verdict_number'    => $request->verdict_number,
                'notes'             => $request->notes,
            ]);

            // Hapus dokumen yang ditandai untuk dihapus
            if ($request->filled('remove_documents')) {
                Document::whereIn('id', $request->remove_documents)
                    ->where('case_id', $case->id)
                    ->delete();
            }

            // Tambah dokumen baru - access files using structure documents[CERAI_TYPE][DOC_TYPE]
            $docFiles = $request->file('documents.' . $ceraiType) ?: [];
            $docTypes = ['KTP_SUAMI', 'KTP_ISTRI', 'KK', 'PUTUSAN_PA', 'AKTA_CERAI', 'AKTA_NIKAH',
                         'AKTA_KEMATIAN', 'SURAT_KETERANGAN_AHLI_WARIS', 'SURAT_PINDAH',
                         'SURAT_KETERANGAN_GHAIB', 'AKTA_KELAHIRAN_ANAK'];

            foreach ($docTypes as $docType) {
                $file = $docFiles[$docType] ?? null;
                if (!$file || !$file->isValid()) continue;

                $normalizedDocType = DocumentTypeMapper::toCaseType($docType);

                // Delete existing document of the same type (force delete to remove from DB completely)
                Document::where('case_id', $case->id)
                    ->where('document_type', $normalizedDocType)
                    ->forceDelete();

                $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("cases/{$case->id}", $storedName, 'public');
                $checksum = hash_file('sha256', $file->getPathname());

                $doc = \App\Models\Document::create([
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
                    'status'        => 'PENDING',
                ]);
                \Log::info('Document saved', ['doc_id' => $doc->id, 'type' => $docType]);
            }

            // Log update
            \App\Models\CaseTransition::create([
                'case_id'         => $case->id,
                'from_state'      => 'DRAFT',
                'to_state'        => 'DRAFT',
                'transitioned_by' => $user->id,
                'reason'          => 'Draft diperbarui',
                'metadata'        => ['source' => 'dashboard.updateDraftCase'],
            ]);
        });

        // Sync to Neo4j
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
            \Log::warning('Neo4j sync failed after draft update', [
                'case_id' => $case->id,
                'error'   => $e->getMessage(),
            ]);
        }

        // Check for AJAX request - Laravel's expectsJson() + check X-Requested-With
        $isAjax = $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'message' => 'Draft berhasil diperbarui',
                'redirect' => route('dashboard.cases.edit-draft', $case->id),
            ]);
        }

        return redirect()->route('dashboard.cases.edit-draft', $case->id)
            ->with('success', 'Draft berhasil diperbarui');
    }

    /**
     * Submit draft case - change status from DRAFT to SUBMITTED
     * Also handles file uploads for direct submission
     */
    public function submitDraftCase(Request $request, int $id)
    {
        \Log::info('=== SUBMIT DRAFT REQUEST START ===', [
            'expects_json' => $request->expectsJson(),
            'header_x_req' => $request->header('X-Requested-With'),
            'files_count' => count($request->allFiles()),
            'files_keys' => array_keys($request->allFiles()),
        ]);

        $isAjax = $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';

        $case = CaseModel::with('documents')->findOrFail($id);

        // Only owner or admin dapat submit
        if ($case->submitter_id !== auth()->id() && !auth()->user()->hasRole('super_admin')) {
            if ($isAjax) {
                return response()->json(['success' => false, 'errors' => ['Akses ditolak']], 403);
            }
            return back()->withErrors('Anda tidak memiliki akses');
        }

        // Only DRAFT status dapat disubmit
        if ($case->status !== 'DRAFT') {
            if ($isAjax) {
                return response()->json(['success' => false, 'errors' => ['Hanya draft dapat dikirim']], 400);
            }
            return back()->withErrors('Hanya draft dapat dikirim');
        }

        $ceraiOptions = $this->ceraiOptions();
        $ceraiType = $request->input('cerai_type') ?: $case->cerai_type;
        $isGroupedFlow = is_string($ceraiType) && array_key_exists($ceraiType, $ceraiOptions);

        // Get required documents for this cerai type
        $requiredDocs = $ceraiOptions[$ceraiType]['docs'] ?? $ceraiOptions['cerai_normal']['docs'];
        $requiredDocKeys = array_keys($requiredDocs);

        // Validasi form
        $rules = [
            'suami_nik'      => 'required|digits:16',
            'suami_name'     => 'required|string|max:255',
            'suami_alamat'   => 'required|string|max:255',
            'suami_rt_rw'    => ['required', 'string', 'max:10', 'regex:/^\d{3}\/\d{3}$/'],
            'suami_kelurahan'=> 'required|string|max:255',
            'suami_kecamatan'=> 'required|string|max:255',
            'istri_nik'      => 'required|digits:16',
            'istri_name'     => 'required|string|max:255',
            'istri_alamat'   => 'required|string|max:255',
            'istri_rt_rw'    => ['required', 'string', 'max:10', 'regex:/^\d{3}\/\d{3}$/'],
            'istri_kelurahan'=> 'required|string|max:255',
            'istri_kecamatan'=> 'required|string|max:255',
            'phone_wa'       => ['required', 'string', 'max:20', 'regex:/^[0-9]{9,15}$/'],
            'institution_id' => 'required|exists:institutions,id',
            'cerai_type'     => 'nullable|in:' . implode(',', array_keys($ceraiOptions)),
            'divorce_date'   => 'nullable|date|before_or_equal:today',
            'verdict_number' => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:1000',
            'documents'      => 'nullable|array',
            'remove_documents' => 'nullable|array',
            'remove_documents.*' => 'nullable|exists:documents,id',
        ];

        $messages = [
            'suami_nik.required'     => 'NIK suami wajib diisi.',
            'suami_nik.digits'       => 'NIK suami harus 16 digit angka.',
            'suami_name.required'    => 'Nama suami wajib diisi.',
            'suami_alamat.required'  => 'Alamat suami wajib diisi.',
            'suami_rt_rw.required'   => 'RT/RW suami wajib diisi.',
            'suami_rt_rw.regex'      => 'Format RT/RW suami harus 000/000.',
            'suami_kelurahan.required' => 'Kelurahan suami wajib diisi.',
            'suami_kecamatan.required' => 'Kecamatan suami wajib diisi.',
            'istri_nik.required'     => 'NIK istri wajib diisi.',
            'istri_nik.digits'       => 'NIK istri harus 16 digit angka.',
            'istri_name.required'    => 'Nama istri wajib diisi.',
            'istri_alamat.required'  => 'Alamat istri wajib diisi.',
            'istri_rt_rw.required'   => 'RT/RW istri wajib diisi.',
            'istri_rt_rw.regex'      => 'Format RT/RW istri harus 000/000.',
            'istri_kelurahan.required' => 'Kelurahan istri wajib diisi.',
            'istri_kecamatan.required' => 'Kecamatan istri wajib diisi.',
            'phone_wa.required'      => 'Nomor WhatsApp wajib diisi.',
            'phone_wa.regex'         => 'Format nomor WhatsApp tidak valid (gunakan angka saja, 9-15 digit).',
            'institution_id.required' => 'Institusi wajib dipilih.',
            'documents.*.mimes'      => 'Format file harus JPG, PNG, atau PDF.',
            'documents.*.max'       => 'Ukuran file maksimal 5 MB.',
        ];

        $request->validate($rules, $messages);

        \Log::info('=== SUBMIT DRAFT AFTER VALIDATION ===', [
            'doc_KTP_SUAMI' => $request->hasFile('documents.KTP_SUAMI'),
            'doc_KTP_ISTRI' => $request->hasFile('documents.KTP_ISTRI'),
            'doc_KK' => $request->hasFile('documents.KK'),
            'all_files' => array_keys($request->file('documents') ?: []),
        ]);

        $user = auth()->user();

        // Clean phone number
        $phoneWa = $request->input('phone_wa', '');
        $phoneWa = preg_replace('/^(\+62|0)/', '', $phoneWa);
        $phoneWa = preg_replace('/\s+/', '', $phoneWa);
        $phoneWa = preg_replace('/[-.]/', '', $phoneWa);

        try {
            DB::transaction(function () use ($request, $case, $user, $ceraiType, $phoneWa) {
                // Update case data
                $case->update([
                    'petitioner_nik'    => $request->suami_nik,
                    'petitioner_name'   => $request->suami_name,
                    'petitioner_phone'  => $phoneWa,
                    'petitioner_alamat' => $request->suami_alamat,
                    'petitioner_rt_rw' => $request->suami_rt_rw,
                    'petitioner_kelurahan' => $request->suami_kelurahan,
                    'petitioner_kecamatan' => $request->suami_kecamatan,
                    'cerai_type'       => $ceraiType ?: null,
                    'institution_id'    => $request->institution_id,
                    'spouse_nik'        => $request->istri_nik,
                    'spouse_name'       => $request->istri_name,
                    'spouse_alamat'     => $request->istri_alamat,
                    'spouse_rt_rw'      => $request->istri_rt_rw,
                    'spouse_kelurahan'  => $request->istri_kelurahan,
                    'spouse_kecamatan'  => $request->istri_kecamatan,
                    'divorce_date'      => $request->divorce_date,
                    'verdict_number'    => $request->verdict_number,
                    'notes'             => $request->notes,
                ]);

                // Remove documents marked for deletion (force delete to remove from DB completely)
                if ($request->filled('remove_documents')) {
                    Document::whereIn('id', $request->remove_documents)
                        ->where('case_id', $case->id)
                        ->forceDelete();
                }

                // Upload documents - access files from structure documents[CERAI_TYPE][DOC_TYPE]
                $docFiles = $request->file('documents.' . $ceraiType) ?: [];
                $docTypes = ['KTP_SUAMI', 'KTP_ISTRI', 'KK', 'PUTUSAN_PA', 'AKTA_CERAI', 'AKTA_NIKAH',
                             'AKTA_KEMATIAN', 'SURAT_KETERANGAN_AHLI_WARIS', 'SURAT_PINDAH',
                             'SURAT_KETERANGAN_GHAIB', 'AKTA_KELAHIRAN_ANAK'];

                $uploadedCount = 0;
                foreach ($docTypes as $docType) {
                    $file = $docFiles[$docType] ?? null;
                    if (!$file || !$file->isValid()) {
                        // If no new file, skip (keep existing document in DB)
                        continue;
                    }

                    $normalizedDocType = DocumentTypeMapper::toCaseType($docType);

                    // Delete existing document of the same type (force delete to remove from DB completely)
                    Document::where('case_id', $case->id)
                        ->where('document_type', $normalizedDocType)
                        ->forceDelete();

                    $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs("cases/{$case->id}", $storedName, 'public');
                    $checksum = hash_file('sha256', $file->getPathname());

                    \App\Models\Document::create([
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
                        'status'        => 'PENDING',
                    ]);
                    $uploadedCount++;
                    \Log::info('Document uploaded via submit', ['type' => $normalizedDocType]);
                }
                \Log::info('SubmitDraft uploaded documents count', ['count' => $uploadedCount, 'files_received' => array_keys($docFiles)]);

                // Check required documents
                $requiredDocTypes = array_keys($this->ceraiOptions()[$ceraiType]['docs'] ?? $this->ceraiOptions()['cerai_normal']['docs']);
                $uploadedDocsTypes = $case->fresh()->documents->pluck('document_type')->toArray();

                \Log::info('Document check', [
                    'required' => $requiredDocTypes,
                    'uploaded' => $uploadedDocsTypes,
                ]);

                // Handle OTHER type - if OTHER exists, it can count as any missing document
                $hasOther = in_array('OTHER', $uploadedDocsTypes);
                $missingDocs = array_diff($requiredDocTypes, $uploadedDocsTypes);
                if ($hasOther) {
                    $missingDocs = [];
                }

                if (!empty($missingDocs)) {
                    throw new \Exception('Dokumen yang diperlukan belum lengkap: ' . implode(', ', $missingDocs));
                }

                // Update status to SUBMITTED
                $case->update([
                    'status'       => 'SUBMITTED',
                    'submitted_at' => now(),
                ]);

                // Log transition
                \App\Models\CaseTransition::create([
                    'case_id'         => $case->id,
                    'from_state'      => 'DRAFT',
                    'to_state'        => 'SUBMITTED',
                    'transitioned_by' => $user->id,
                    'reason'          => 'Pengajuan langsung dikirim',
                    'metadata'        => ['source' => 'dashboard.submitDraftCase'],
                ]);

                // Fire DocumentUploaded event for OCR processing
                foreach ($case->fresh()->documents as $document) {
                    event(new \App\Events\DocumentUploaded($document));
                }

                // Outbox event
                \App\Models\IntegrationQueue::create([
                    'aggregate_type' => 'Case',
                    'aggregate_id'   => $case->id,
                    'event_type'     => 'submitted',
                    'payload'        => ['institution_id' => $case->institution_id, 'submitter_id' => $case->submitter_id],
                    'available_at'   => now(),
                ]);
            });
        } catch (\Exception $e) {
            \Log::error('Submit draft failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'case_id' => $id,
                'user_id' => auth()->id(),
            ]);
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => [$e->getMessage()],
                    'debug' => config('app.debug') ? $e->getTraceAsString() : null,
                ], 422);
            }
            return back()->withErrors($e->getMessage());
        }

        // Sync to Neo4j
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
            \Log::warning('Neo4j sync failed after draft submission', [
                'case_id' => $case->id,
                'error'   => $e->getMessage(),
            ]);
        }

        $redirectUrl = route('dashboard.cases.show', $case->id);

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan berhasil dikirim! Tracking token: ' . $case->tracking_token,
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)
            ->with('success', 'Pengajuan berhasil dikirim! Tracking token: ' . $case->tracking_token);
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
        if (!auth()->user()->hasAnyRole(['pa_management', 'super_admin', 'disdukcapil_staff'])) {
            $this->rebac->enforce(auth()->user(), 'view', 'Case', $id);
        }

        // PA Management and Super Admin should use dedicated OCR review page.
        if (auth()->user()->hasAnyRole(['pa_management', 'super_admin']) && $case->status !== 'DISDUKCAPIL_VALIDATION') {
            return redirect()->route('dashboard.review.show', $id);
        }

        // ✨ Prepare corrected OCR data from PA Management review
        $suami_ocr = $this->extractCorrectedOcrData($case, 'KTP_SUAMI');
        $istri_ocr = $this->extractCorrectedOcrData($case, 'KTP_ISTRI');

        return view('dashboard.cases.show', compact('case', 'suami_ocr', 'istri_ocr'));
    }

    /**
     * Extract corrected OCR data from OcrValidation for a specific document type
     * Returns the final validated/corrected values from PA Management review
     */
    private function extractCorrectedOcrData($case, string $documentType): array
    {
        $data = [
            'nik' => null,
            'nama' => null,
            'tempat_lahir' => null,
            'tgl_lahir' => null,
            'alamat' => null,
            'rt_rw' => null,
            'kelurahan' => null,
            'kecamatan' => null,
            'is_available' => false,
            'validation_status' => null,
        ];

        // Find document with this type
        $document = $case->documents->firstWhere('document_type', $documentType);
        if (!$document) {
            return $data;
        }

        // Get the latest OCR validation for this document
        $validation = $case->ocrValidations
            ->filter(fn($v) => $v->document_id === $document->id)
            ->first();

        if (!$validation) {
            return $data;
        }

        // Extract corrected OCR data (from PA Management review)
        // Prioritize reviewed/corrected values, fall back to OCR extracted
        $data = [
            'nik' => $validation->ocr_nik,
            'nama' => $validation->ocr_nama,
            'tempat_lahir' => $validation->ocr_tempat_lahir,
            'tgl_lahir' => $validation->ocr_tgl_lahir,
            'alamat' => $validation->ocr_alamat,
            'rt_rw' => $validation->ocr_rt_rw,
            'kelurahan' => $validation->ocr_kelurahan,
            'kecamatan' => $validation->ocr_kecamatan,
            'is_available' => true,
            'validation_status' => $validation->validation_status,
            'match_score' => $validation->overall_match_score,
            'is_reviewed' => $validation->is_reviewed,
            'reviewed_at' => $validation->reviewed_at,
        ];

        return $data;
    }

    public function upload(): View
    {
        $cases = CaseModel::forUser(auth()->user())
            ->whereIn('status', ['DRAFT', 'SUBMITTED'])
            ->get(['id', 'case_number']);
        return view('dashboard.upload', compact('cases'));
    }

    /**
     * Halaman Arsip untuk PA Staff
     * Menampilkan kasus yang sudah selesai (COMPLETED / ARCHIVED) untuk diarsipkan.
     */
    public function arsip(Request $request): View
    {
        $user = auth()->user();

        $q = $request->query('q');
        $year = $request->query('year');

        $query = CaseModel::with('institution:id,name', 'submitter:id,name')
            ->whereIn('status', ['COMPLETED', 'ARCHIVED'])
            ->selectRaw('id, case_number, tracking_token, petitioner_name, spouse_name, status, institution_id, submitter_id, source_type, divorce_date, completed_at, updated_at');

        if ($user->hasRole('disdukcapil_staff')) {
            $query->where('status', 'COMPLETED');
        } else {
            $query->forUser($user);
        }

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('case_number', 'like', "%{$q}%")
                    ->orWhere('petitioner_name', 'like', "%{$q}%")
                    ->orWhere('spouse_name', 'like', "%{$q}%")
                    ->orWhere('tracking_token', 'like', "%{$q}%");
            });
        }

        if ($year) {
            $query->whereYear('completed_at', $year);
        }

        $arsipItems = $query->orderByDesc('completed_at')->paginate(15)->withQueryString();

        $years = CaseModel::whereIn('status', ['COMPLETED', 'ARCHIVED'])
            ->whereNotNull('completed_at')
            ->selectRaw('DISTINCT YEAR(completed_at) as year')
            ->orderByDesc('year')
            ->pluck('year');

        $counts = [
            'completed' => CaseModel::forUser($user)->where('status', 'COMPLETED')->count(),
            'archived'  => CaseModel::forUser($user)->where('status', 'ARCHIVED')->count(),
        ];

        return view('dashboard.staff.arsip.index', [
            'arsipItems' => $arsipItems,
            'counts' => $counts,
            'years' => $years,
            'currentYear' => $year,
        ]);
    }

    public function tracking(): View
    {
        return view('dashboard.tracking');
    }

    /**
     * Halaman Aktivitas untuk PA Staff
     * Menampilkan log/aktivitas terbaru yang relevan dengan staff (case transitions,
     * dokumen baru, validasi, dsb).
     */
    public function aktivitas(Request $request): View
    {
        $user = auth()->user();
        $filter = $request->query('filter', 'all');

        $query = AuditLog::with('user:id,name')
            ->whereIn('action', [
                'case.created', 'case.updated', 'case.submitted',
                'case.approved', 'case.rejected', 'case.completed',
                'case.archived', 'case.restored',
                'document.uploaded', 'document.downloaded',
                'ocr.processed', 'ocr.validated',
                'public_submission.created', 'public_submission.reviewed',
                'public_submission.approved', 'public_submission.rejected',
            ]);

        // Filter by activity type (case, document, ocr, public_submission, system)
        if ($filter === 'case') {
            $query->where('action', 'like', 'case.%');
        } elseif ($filter === 'document') {
            $query->where('action', 'like', 'document.%');
        } elseif ($filter === 'ocr') {
            $query->where('action', 'like', 'ocr.%');
        } elseif ($filter === 'public_submission') {
            $query->where('action', 'like', 'public_submission.%');
        } elseif ($filter === 'system') {
            $query->whereNotIn('action', [
                'case.created', 'case.updated', 'case.submitted',
                'case.approved', 'case.rejected', 'case.completed',
                'case.archived', 'case.restored',
                'document.uploaded', 'document.downloaded',
                'ocr.processed', 'ocr.validated',
                'public_submission.created', 'public_submission.reviewed',
                'public_submission.approved', 'public_submission.rejected',
            ]);
        }

        $activities = $query->latest('created_at')->paginate(20)->withQueryString();

        $stats = [
            'today'    => AuditLog::whereDate('created_at', today())->count(),
            'week'     => AuditLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'month'    => AuditLog::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return view('dashboard.staff.aktivitas', [
            'activities' => $activities,
            'filter'     => $filter,
            'stats'      => $stats,
        ]);
    }

    /**
     * Alias: redirect terbaru ke aktivitas (teruntuk user lama)
     */
    public function aktivitasTerbaru(Request $request): View
    {
        return $this->aktivitas($request);
    }
    /**
     * Halaman Aktivitas untuk PA Staff.
     */
    public function staffAktivitas(Request $request): View
    {
        return $this->aktivitas($request);
    }

    /**
     * Halaman Arsip untuk PA Staff.
     */
    public function staffArsip(Request $request): View
    {
        $user = auth()->user();

        $year = $request->query('year');
        $statusFilter = $request->query('status');

        $query = CaseModel::with('institution:id,name', 'submitter:id,name')
            ->whereIn('status', ['COMPLETED', 'ARCHIVED'])
            ->forUser($user)
            ->selectRaw('id, case_number, tracking_token, petitioner_name, spouse_name, status, source_type, completed_at, updated_at')
            ->orderByDesc('completed_at');

        if ($year) {
            $query->whereYear('completed_at', $year);
        }
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $arsipItems = $query->paginate(20)->withQueryString();

        $years = CaseModel::whereIn('status', ['COMPLETED', 'ARCHIVED'])
            ->whereNotNull('completed_at')
            ->selectRaw('DISTINCT YEAR(completed_at) as year')
            ->orderByDesc('year')
            ->pluck('year');

        $counts = [
            'completed' => CaseModel::forUser($user)->where('status', 'COMPLETED')->count(),
            'archived'  => CaseModel::forUser($user)->where('status', 'ARCHIVED')->count(),
        ];

        return view('dashboard.staff.arsip.index', [
            'arsipItems' => $arsipItems,
            'counts'     => $counts,
            'years'      => $years,
            'currentYear'=> $year,
        ]);
    }

    /**
     * Detail item arsip
     */
    public function staffArsipShow(int $id): View
    {
        $user = auth()->user();

        $case = CaseModel::with([
            'institution:id,name,code,type',
            'submitter:id,name,email',
            'assignedPaUser:id,name',
            'assignedDisdukcapilUser:id,name',
            'documents' => function ($q) {
                $q->orderByDesc('created_at');
            },
            'transitions' => function ($q) {
                $q->with('actor:id,name')->orderBy('created_at');
            },
            'latestTransition',
        ])->findOrFail($id);

        if (! $user->hasRole('super_admin') && ! $user->hasRole('disdukcapil_staff')) {
            if ($case->institution_id !== $user->institution_id) {
                abort(403);
            }
        }

        return view('dashboard.staff.arsip.show', [
            'case' => $case,
        ]);
    }

    /**
     * Restore kasus dari arsip ke status aktif (SUBMITTED).
     */
    public function restoreArsip(int $id)
    {
        $user = auth()->user();
        $case = CaseModel::with('documents')->findOrFail($id);

        if ($case->status !== 'ARCHIVED') {
            return back()->with('error', 'Hanya kasus berstatus ARCHIVED yang bisa di-restore.');
        }

        if (!$user->hasRole('super_admin')) {
            if ($case->institution_id !== $user->institution_id) {
                abort(403);
            }
        }

        DB::transaction(function () use ($case, $user) {
            $case->update(['status' => 'SUBMITTED', 'completed_at' => null]);

            \App\Models\CaseTransition::create([
                'case_id'         => $case->id,
                'from_state'      => 'ARCHIVED',
                'to_state'        => 'SUBMITTED',
                'transitioned_by' => $user->id,
                'reason'          => 'Restore dari arsip oleh PA Staff',
                'metadata'        => ['source' => 'dashboard.restoreArsip'],
            ]);

            \App\Models\IntegrationQueue::create([
                'aggregate_type' => 'Case',
                'aggregate_id'   => $case->id,
                'event_type'     => 'restored',
                'payload'        => ['institution_id' => $case->institution_id, 'restored_by' => $user->id],
                'available_at'   => now(),
            ]);
        });

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'case.restored',
            'subject_type'   => CaseModel::class,
            'subject_id'     => $case->id,
            'metadata'       => ['case_number' => $case->case_number, 'source' => 'staff.arsip.restore'],
            'ip_address'     => request()->ip(),
        ]);

        return redirect()->route('dashboard.staff.arsip')
            ->with('success', 'Kasus berhasil di-restore dari arsip.');
    }

    /**
     * Download dokumen arsip (hanya untuk PA Staff/Admin yang punya akses).
     */
    public function staffArsipDownload(int $caseId, int $documentId)
    {
        $user = auth()->user();

        $case = CaseModel::findOrFail($caseId);

        if (! $user->hasRole('super_admin') && $case->institution_id !== $user->institution_id) {
            abort(403);
        }

        $document = Document::where('case_id', $caseId)
            ->where('id', $documentId)
            ->firstOrFail();

        $disk = \Storage::disk(config('ocr.storage.disk', 'public'));

        if (! $disk->exists($document->file_path)) {
            abort(404, 'File tidak ditemukan di storage.');
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action'  => 'document.downloaded',
            'subject_type' => Document::class,
            'subject_id'   => $document->id,
            'metadata'     => ['case_id' => $case->id, 'source' => 'staff.arsip'],
            'ip_address'   => request()->ip(),
        ]);

        return $disk->download($document->file_path, $document->original_name ?? basename($document->file_path));
    }

    /**
     * Halaman Kelola Blog untuk PA Staff.
     * Menampilkan daftar blog posts di cms_blog_posts dengan aksi sederhana.
     */
    public function staffKelolaBlog(Request $request): View
    {
        $query = \App\Models\CmsBlogPost::query();

        if ($status = $request->query('status')) {
            $query->where('status', strtoupper($status));
        }

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%")
                    ->orWhere('excerpt', 'like', "%{$q}%");
            });
        }

        $posts = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        $stats = [
            'draft'     => \App\Models\CmsBlogPost::where('status', 'DRAFT')->count(),
            'published' => \App\Models\CmsBlogPost::where('status', 'PUBLISHED')->count(),
            'archived'  => \App\Models\CmsBlogPost::where('status', 'ARCHIVED')->count(),
        ];

        return view('dashboard.staff.kelola-blog', [
            'posts' => $posts,
            'stats' => $stats,
            'statusFilter' => $status,
        ]);
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

    private function buildChartData(): array
    {
        // Get current PA Assistant user (dari context - untuk filter institution)
        $user = auth()->user();
        
        // Get data for the last 7 months
        $months = [];
        $labels = [];
        $totalData = [];
        $publicData = [];
        $internalData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startOfMonth = $date->clone()->startOfMonth();
            $endOfMonth = $date->clone()->endOfMonth();

            $months[] = $date;
            $labels[] = $date->format('M Y');

            // Public submissions (dari pengajuan publik) - filter by institution
            $publicCount = PublicSubmission::where('institution_id', $user->institution_id)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();
            $publicData[] = $publicCount;

            // Internal cases = source_type 'internal' created by PA Assistant
            $internalCount = CaseModel::forUser($user)
                ->where('source_type', 'internal')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();
            $internalData[] = $internalCount;

            // Total = Public + Internal
            $totalCount = $publicCount + $internalCount;
            $totalData[] = $totalCount;
        }

        $chartData = [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Inputan',
                    'data' => $totalData,
                    'color' => '#3b82f6', // Blue
                ],
                [
                    'label' => 'Inputan Publik',
                    'data' => $publicData,
                    'color' => '#a855f7', // Purple
                ],
                [
                    'label' => 'Inputan PA Assistant',
                    'data' => $internalData,
                    'color' => '#14b8a6', // Teal
                ],
            ],
        ];

        // Debug log
        \Log::info('Chart data built', [
            'labels' => $labels,
            'totalData' => $totalData,
            'publicData' => $publicData,
            'internalData' => $internalData,
            'user' => $user->name,
            'institution_id' => $user->institution_id,
        ]);

        return $chartData;
    }

    private function buildStats(\App\Models\User $user): array
    {
        // For Disdukcapil staff, base query is ONLY DISDUKCAPIL_VALIDATION cases
        if ($user->hasRole('disdukcapil_staff')) {
            $q = CaseModel::where('status', 'DISDUKCAPIL_VALIDATION');
        } elseif ($user->hasRole('pa_management') || $user->hasRole('super_admin')) {
            // PA Management & Super Admin: see ALL cases from ALL institutions (for review)
            $q = CaseModel::query();
        } else {
            $q = CaseModel::forUser($user);
        }

        // Count public submissions relevant to this user's institution
        // For non-disdukcapil: count public submissions with matching institution or all if PA management
        if ($user->hasRole('disdukcapil_staff')) {
            $publicPending = 0;
        } elseif ($user->hasRole('pa_management') || $user->hasRole('super_admin')) {
            $publicPending = PublicSubmission::whereIn('status', ['SUBMITTED', 'APPROVED'])->count();
        } else {
            $publicPending = PublicSubmission::whereIn('status', ['SUBMITTED', 'APPROVED'])->count();
        }

        // For PA Assistant: public submissions relevant to their institution, internal cases = source_type='internal'
        $publicSubmissionsCount = PublicSubmission::where('institution_id', $user->institution_id)->count();
        $internalCasesCount = (clone $q)->where('source_type', 'internal')->count();

        // Calculate match/mismatch for PA Management - ALL KTP_SUAMI + KTP_ISTRI documents (public + internal)
        $ocrValidations = \App\Models\OcrValidation::whereHas('document', function ($q) {
            $q->whereIn('document_type', ['KTP_SUAMI', 'KTP_ISTRI', 'KTP']);
        })->get();
        $matchCount = $ocrValidations->whereIn('validation_status', ['MATCH', 'SUCCESS'])->count();
        $partialCount = $ocrValidations->whereIn('validation_status', ['PARTIAL_MATCH', 'PARTIAL'])->count();
        $mismatchCount = $ocrValidations->whereIn('validation_status', ['MISMATCH', 'FAILED'])->count();

        // Calculate stats for Disdukcapil Staff - show assigned + all pending validation
        if ($user->hasRole('disdukcapil_staff')) {
            $disdukcapilValidation = (clone $q)->count();  // All DISDUKCAPIL_VALIDATION cases
            $disdukcapilCompleted = CaseModel::where('status', 'COMPLETED')->count();
            $disdukcapilRejected = CaseModel::where('status', 'REJECTED')->count();
        } elseif ($user->hasRole('pa_management') || $user->hasRole('super_admin')) {
            // PA Management & Super Admin: ALL cases (no institution filter)
            $allQ = CaseModel::query();
            $disdukcapilValidation = (clone $allQ)->where('status', 'DISDUKCAPIL_VALIDATION')->count();
            $disdukcapilCompleted = (clone $allQ)->where('status', 'COMPLETED')->count();
            $disdukcapilRejected = (clone $allQ)->where('status', 'REJECTED')->count();
        } else {
            $disdukcapilValidation = (clone $q)->where('status', 'DISDUKCAPIL_VALIDATION')->count();
            $disdukcapilCompleted = (clone $q)->where('status', 'COMPLETED')->count();
            $disdukcapilRejected = (clone $q)->where('status', 'REJECTED')->count();
        }

        return [
            'total'       => (clone $q)->count(),
            'draft'       => $user->hasRole('disdukcapil_staff') ? 0 : (clone $q)->byStatus('DRAFT')->count(),
            'in_progress' => $user->hasRole('disdukcapil_staff') ? (clone $q)->count() : ((clone $q)->whereNotIn('status', ['DRAFT','COMPLETED','ARCHIVED','REJECTED'])->count()),
            'completed'   => $user->hasRole('disdukcapil_staff') ? 0 : (clone $q)->byStatus('COMPLETED')->count(),
            'rejected'    => $user->hasRole('disdukcapil_staff') ? 0 : ((clone $q)->byStatus('REJECTED')->count()),
            'public_submissions' => $user->hasRole('disdukcapil_staff') ? 0 : $publicSubmissionsCount,
            'internal_cases' => $internalCasesCount,
            'ocr_match' => $matchCount,
            'ocr_partial' => $partialCount,
            'ocr_mismatch' => $mismatchCount,
            'validation_pending' => $disdukcapilValidation,
            'validation_completed' => $disdukcapilCompleted,
            'validation_rejected' => $disdukcapilRejected,
        ];
    }

    private function ceraiOptions(): array
    {
        $baseDocs = [
            'KTP_SUAMI'  => 'Upload KTP Suami',
            'KTP_ISTRI'  => 'Upload KTP Istri',
            'KK'         => 'Upload Kartu Keluarga (KK)',
            'PUTUSAN_PA' => 'Upload Putusan Pengadilan',
            'AKTA_CERAI' => 'Upload Akta Cerai',
            'AKTA_NIKAH' => 'Upload Akta Kawin / Buku Nikah',
        ];

        return [
            'cerai_normal' => [
                'label'       => 'Cerai Normal',
                'description' => 'Untuk pembaruan dokumen standar setelah putusan cerai.',
                'docs'        => $baseDocs,
            ],
            'cerai_mati' => [
                'label'       => 'Cerai Mati',
                'description' => 'Untuk pembaruan dokumen ketika pasangan meninggal dunia.',
                'docs'        => $baseDocs + [
                    'AKTA_KEMATIAN'                    => 'Upload Akta Kematian',
                    'SURAT_KETERANGAN_AHLI_WARIS'      => 'Upload Surat Keterangan Ahli Waris',
                ],
            ],
            'cerai_pindah' => [
                'label'       => 'Cerai Pindah',
                'description' => 'Untuk pembaruan dokumen ketika ada perubahan domisili disertai surat pindah.',
                'docs'        => $baseDocs + [
                    'SURAT_PINDAH' => 'Upload Surat Pindah',
                ],
            ],
            'cerai_ghaib' => [
                'label'       => 'Cerai Ghaib (Kehilangan)',
                'description' => 'Untuk pembaruan dokumen ketika pasangan tidak diketahui keberadaannya.',
                'docs'        => $baseDocs + [
                    'SURAT_KETERANGAN_GHAIB' => 'Upload Surat Keterangan Ghaib',
                ],
            ],
            'cerai_hak_asuh' => [
                'label'       => 'Cerai Terkait Hak Asuh Anak',
                'description' => 'Untuk pembaruan dokumen yang berkaitan dengan penetapan hak asuh anak.',
                'docs'        => $baseDocs + [
                    'AKTA_KELAHIRAN_ANAK' => 'Upload Akta Kelahiran Anak',
                ],
            ],
        ];
    }

    private function documentsForCeraiType(string $ceraiType): array
    {
        $options = $this->ceraiOptions();
        return array_keys($options[$ceraiType]['docs'] ?? $options['cerai_normal']['docs']);
    }
}
