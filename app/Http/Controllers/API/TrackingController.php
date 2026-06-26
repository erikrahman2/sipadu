<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\Document;
use App\Models\PublicSubmission;
use App\Services\PublicSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
                'documents:id,case_id,document_type,size_bytes,original_name,created_at',
            ])
            ->where('tracking_token', $token)
            ->first();

            if ($case) {
                // Only show completed case documents after COMPLETED status
                $documents = $case->status === 'COMPLETED' 
                    ? $case->documents
                        ->whereIn('document_type', ['BAST', 'DIGITAL_COPY'])
                        ->map(fn($doc) => [
                            'id' => $doc->id,
                            'document_type' => $doc->document_type,
                            'original_name' => $doc->original_name,
                            'size_bytes' => $doc->size_bytes,
                            'created_at' => $doc->created_at->toDateTimeString(),
                        ])
                        ->values() // Convert to array of objects, not keyed
                    : [];

                return response()->json([
                    'type'           => 'case',
                    'tracking_token' => $case->tracking_token,
                    'case_number'    => $case->case_number,
                    'status'         => $case->status,
                    'status_label'   => config("workflow.states.{$case->status}", $case->status),
                    'institution'    => $case->institution?->name,
                    'submitted_at'   => $case->submitted_at?->toDateTimeString(),
                    'completed_at'   => $case->completed_at?->toDateTimeString(),
                    'documents'      => $documents,
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

    // ─────────────────────────────────────────────────────────────────────────
    // GET /tracking/{token}/download/{documentId} – public download endpoint
    // ─────────────────────────────────────────────────────────────────────────

    public function downloadDocument(string $token, int $documentId): mixed
    {
        // Verify token belongs to completed case & document exists
        $case = CaseModel::where('tracking_token', $token)
            ->where('status', 'COMPLETED')
            ->first();

        if (!$case) {
            return response()->json([
                'message' => 'Kasus tidak ditemukan atau belum selesai divalidasi.',
            ], 404);
        }

        $document = Document::where('case_id', $case->id)
            ->where('id', $documentId)
            ->whereIn('document_type', ['BAST', 'DIGITAL_COPY'])
            ->first();

        if (!$document) {
            return response()->json([
                'message' => 'Dokumen tidak ditemukan.',
            ], 404);
        }

        Log::channel('audit')->info('Tracking document downloaded', [
            'tracking_token' => $token,
            'case_id' => $case->id,
            'document_id' => $documentId,
            'document_type' => $document->document_type,
        ]);

        return Storage::disk($document->disk)
            ->download($document->path, $document->original_name);
    }
}
