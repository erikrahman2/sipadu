<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Services\AuditService;
use App\Services\OCRService;
use App\Services\ReBACService;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function __construct(
        private readonly WorkflowService $workflow,
        private readonly ReBACService    $rebac,
        private readonly AuditService    $audit
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // POST /review/pa
    // ─────────────────────────────────────────────────────────────────────────

    public function paReview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'case_id'  => 'required|exists:cases,id',
            'decision' => 'required|in:approve,reject',
            'notes'    => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $case = CaseModel::findOrFail($request->case_id);
        $user = auth()->user();

        $this->rebac->enforce($user, 'approve', 'Case', $case->id);

        if ($case->status !== 'PA_REVIEW') {
            return response()->json([
                'message' => "Kasus tidak dalam status PA_REVIEW (status saat ini: {$case->status}).",
            ], 409);
        }

        if ($request->decision === 'approve') {
            $case = $this->workflow->sendToDisdukcapil($case, $user, $request->notes);
        } else {
            $case = $this->workflow->reject($case, $user, $request->notes ?? 'Ditolak oleh PA');
        }

        return response()->json([
            'message'    => 'Review PA berhasil.',
            'case_id'    => $case->id,
            'new_status' => $case->status,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /review/disdukcapil
    // ─────────────────────────────────────────────────────────────────────────

    public function disdukcapilReview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'case_id'  => 'required|exists:cases,id',
            'decision' => 'required|in:validate,reject',
            'notes'    => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $case = CaseModel::findOrFail($request->case_id);
        $user = auth()->user();

        $this->rebac->enforce($user, 'validate', 'Case', $case->id);

        if ($case->status !== 'DISDUKCAPIL_VALIDATION') {
            return response()->json([
                'message' => "Kasus tidak dalam status DISDUKCAPIL_VALIDATION.",
            ], 409);
        }

        if ($request->decision === 'validate') {
            $case = $this->workflow->complete($case, $user);
        } else {
            $case = $this->workflow->reject($case, $user, $request->notes ?? 'Ditolak oleh Disdukcapil');
        }

        return response()->json([
            'message'    => 'Validasi Disdukcapil selesai.',
            'case_id'    => $case->id,
            'new_status' => $case->status,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /review/submit/{case}
    // ─────────────────────────────────────────────────────────────────────────

    public function submitCase(int $caseId): JsonResponse
    {
        $case = CaseModel::findOrFail($caseId);
        $user = auth()->user();

        $this->rebac->enforce($user, 'edit', 'Case', $case->id);

        if ($case->status !== 'DRAFT') {
            return response()->json(['message' => 'Hanya kasus DRAFT yang bisa disubmit.'], 409);
        }

        $case = $this->workflow->submit($case, $user);

        return response()->json([
            'message'    => 'Kasus berhasil disubmit.',
            'case_id'    => $case->id,
            'new_status' => $case->status,
        ]);
    }
}
