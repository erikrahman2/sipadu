<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\Institution;
use App\Services\AuditService;
use App\Services\ReBACService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CaseController extends Controller
{
    public function __construct(
        private readonly ReBACService $rebac,
        private readonly AuditService $audit
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // GET /cases
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user  = auth()->user();
        $query = CaseModel::with(['submitter:id,name', 'institution:id,name,type'])
            ->forUser($user);

        if ($request->status) {
            $query->byStatus($request->status);
        }

        $cases = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($cases);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /cases
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'institution_id'   => 'required|exists:institutions,id',
            'petitioner_nik'   => 'required|string|size:16',
            'petitioner_name'  => 'required|string|max:255',
            'petitioner_phone' => 'nullable|string|max:20',
            'spouse_nik'       => 'nullable|string|size:16',
            'spouse_name'      => 'nullable|string|max:255',
            'divorce_date'     => 'nullable|date',
            'verdict_number'   => 'nullable|string|max:100',
            'notes'            => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validasi business logic menggunakan PublicSubmission methods
        $petitionerNik = $request->petitioner_nik;
        $spouseNik = $request->spouse_nik;

        // Validasi 1: NIK pemohon ≠ NIK pasangan
        if (\App\Models\PublicSubmission::isSameNik($petitionerNik, $spouseNik)) {
            return response()->json([
                'message' => 'NIK pemohon tidak boleh sama dengan NIK pasangan.',
            ], 422);
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
            
            $message .= " Harap koordinasi dengan pihak terkait sebelum membuat kasus baru.";

            return response()->json([
                'message' => $message,
                'warning' => true,
                'frozen_info' => $reason,
            ], 422);
        }

        // Validasi 3: Cek apakah pasangan NIK sudah terdaftar di Disdukcapil
        if ($spouseNik && \App\Models\PublicSubmission::hasCoupleInDisdukcapil($petitionerNik, $spouseNik)) {
            return response()->json([
                'message' => 'Pasangan dengan NIK ini sudah terdaftar dan sedang diproses di Disdukcapil. Tidak dapat membuat kasus baru dengan kombinasi NIK yang sama.',
            ], 422);
        }

        $user = auth()->user();

        $case = DB::transaction(function () use ($request, $user, $petitionerNik, $spouseNik) {
            $case = CaseModel::create([
                'submitter_id'     => $user->id,
                'institution_id'   => $request->institution_id,
                'petitioner_nik'   => $request->petitioner_nik,
                'petitioner_name'  => $request->petitioner_name,
                'petitioner_phone' => $request->petitioner_phone,
                'spouse_nik'       => $request->spouse_nik,
                'spouse_name'      => $request->spouse_name,
                'divorce_date'     => $request->divorce_date,
                'verdict_number'   => $request->verdict_number,
                'notes'            => $request->notes,
                'status'           => 'DRAFT',
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

        $this->audit->log($user, 'case.created', CaseModel::class, $case->id, null, $case->toArray());

        return response()->json([
            'message'        => 'Kasus berhasil dibuat.',
            'case'           => $case->only(['id', 'case_number', 'tracking_token', 'status']),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /cases/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $case = CaseModel::with([
            'submitter:id,name,email',
            'institution:id,name,type',
            'documents',
            'transitions.actor:id,name',
            'ocrResults',
        ])->findOrFail($id);

        $this->rebac->enforce(auth()->user(), 'view', 'Case', $id);

        return response()->json($case);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PATCH /cases/{id}/assign
    // ─────────────────────────────────────────────────────────────────────────

    public function assign(Request $request, int $id): JsonResponse
    {
        $case = CaseModel::findOrFail($id);
        $user = auth()->user();
        $this->rebac->enforce($user, 'approve', 'Case', $id);

        $request->validate([
            'pa_user_id'          => 'nullable|exists:users,id',
            'disdukcapil_user_id' => 'nullable|exists:users,id',
        ]);

        $old = $case->only(['assigned_pa_user_id', 'assigned_disdukcapil_user_id']);
        $case->update(array_filter([
            'assigned_pa_user_id'          => $request->pa_user_id,
            'assigned_disdukcapil_user_id' => $request->disdukcapil_user_id,
        ]));

        $this->audit->log($user, 'case.assigned', CaseModel::class, $id, $old, $case->fresh()->toArray());

        return response()->json(['message' => 'Assignment berhasil diperbarui.', 'case' => $case->fresh()]);
    }
}
