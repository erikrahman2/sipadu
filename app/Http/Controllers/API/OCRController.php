<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\OcrResult;
use App\Services\AuditService;
use App\Services\OCRService;
use App\Services\ReBACService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OCRController extends Controller
{
    public function __construct(
        private readonly OCRService   $ocr,
        private readonly ReBACService $rebac,
        private readonly AuditService $audit
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // POST /ocr/process
    // ─────────────────────────────────────────────────────────────────────────

    public function process(Request $request): JsonResponse
    {
        $request->validate([
            'document_id' => 'required|exists:documents,id',
        ]);

        $document = Document::findOrFail($request->document_id);
        $user     = auth()->user();

        $this->rebac->enforce($user, 'process_ocr', 'Document', $document->id);

        if (!in_array($document->mime_type, config('ocr.accepted_mimes', []))) {
            return response()->json(['message' => 'Tipe file tidak didukung untuk OCR.'], 422);
        }

        $job = $this->ocr->dispatch($document);

        $this->audit->log($user, 'ocr.dispatched', Document::class, $document->id);

        return response()->json([
            'message'     => 'OCR job telah dimasukkan ke antrian.',
            'job_id'      => $job->id,
            'document_id' => $document->id,
            'status'      => $job->status,
        ], 202);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /ocr/result/{id}    (id = document_id)
    // ─────────────────────────────────────────────────────────────────────────

    public function result(int $id): JsonResponse
    {
        $document = Document::findOrFail($id);
        $this->rebac->enforce(auth()->user(), 'view', 'Document', $id);

        $result = $this->ocr->getResult($id);

        if (!$result) {
            return response()->json(['message' => 'Hasil OCR belum tersedia.'], 404);
        }

        return response()->json([
            'document_id'       => $document->id,
            'ocr_status'        => $result->ocr_status,
            'overall_confidence' => $result->overall_confidence,
            'nik'               => $result->nik,
            'kk'                => $result->no_kk,
            'nama'              => $result->nama,
            'tgl_lahir'         => $result->tgl_lahir,
            'confidence'        => $result->confidence_scores,
            'validation_errors' => $result->validation_errors,
            'is_validated'      => $result->is_validated,
            'processing_time_ms' => $result->processing_time_ms,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /ocr/job/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function jobStatus(int $id): JsonResponse
    {
        $job = \App\Models\OcrJob::where('document_id', $id)->firstOrFail();
        $this->rebac->enforce(auth()->user(), 'view', 'Document', $id);

        return response()->json([
            'document_id' => $id,
            'status'      => $job->status,
            'attempts'    => $job->attempts,
            'started_at'  => $job->started_at,
            'finished_at' => $job->finished_at,
            'error'       => $job->error_message,
        ]);
    }
}
