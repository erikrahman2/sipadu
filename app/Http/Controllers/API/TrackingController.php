<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\PublicSubmission;
use App\Services\PublicSubmissionService;
use Illuminate\Http\JsonResponse;

class TrackingController extends Controller
{
    public function __construct(private readonly PublicSubmissionService $pubService)
    {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /tracking/{token}   – public endpoint, no auth required
    // Mendukung dua jenis token:
    //   • TRK-xxx  → kasus resmi (CaseModel)
    //   • PUB-xxx  → pengajuan publik (PublicSubmission)
    // ─────────────────────────────────────────────────────────────────────────

    public function track(string $token): JsonResponse
    {
        // 1. Coba cocokkan dengan kasus resmi (TRK-)
        if (str_starts_with($token, 'TRK')) {
            $case = CaseModel::with([
                'transitions:id,case_id,from_state,to_state,created_at',
                'institution:id,name,type',
            ])
            ->where('tracking_token', $token)
            ->first();

            if ($case) {
                return response()->json([
                    'type'           => 'case',
                    'tracking_token' => $case->tracking_token,
                    'case_number'    => $case->case_number,
                    'status'         => $case->status,
                    'status_label'   => config("workflow.states.{$case->status}", $case->status),
                    'institution'    => $case->institution?->name,
                    'submitted_at'   => $case->submitted_at?->toDateTimeString(),
                    'completed_at'   => $case->completed_at?->toDateTimeString(),
                    'timeline'       => $case->transitions->map(fn($t) => [
                        'from' => $t->from_state,
                        'to'   => $t->to_state,
                        'date' => $t->created_at->toDateTimeString(),
                    ]),
                ]);
            }
        }

        // 2. Coba cocokkan dengan pengajuan publik (PUB-)
        $submission = $this->pubService->findByToken($token);

        if ($submission) {
            return response()->json(
                array_merge(['type' => 'public_submission'],
                    $this->pubService->formatTracking($submission)
                )
            );
        }

        return response()->json(['message' => 'Token tidak ditemukan.'], 404);
    }
}
