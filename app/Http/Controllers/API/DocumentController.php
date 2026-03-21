<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\Document;
use App\Services\AuditService;
use App\Services\OCRService;
use App\Services\ReBACService;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function __construct(
        private readonly OCRService      $ocr,
        private readonly ReBACService    $rebac,
        private readonly WorkflowService $workflow,
        private readonly AuditService    $audit
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // POST /documents/upload
    // ─────────────────────────────────────────────────────────────────────────

    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'case_id'       => 'required|exists:cases,id',
            'document_type' => 'required|in:KTP,KK,AKTA_CERAI,PUTUSAN_PA,AKTA_NIKAH,SURAT_PENGANTAR,OTHER',
            'file'          => 'required|file|mimes:jpeg,png,pdf,tiff|max:' . (config('ocr.max_file_size_mb', 10) * 1024),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $case = CaseModel::findOrFail($request->case_id);
        $user = auth()->user();

        // ReBAC check
        $this->rebac->enforce($user, 'view', 'Case', $case->id);

        $file       = $request->file('file');
        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path       = $file->storeAs("documents/{$case->id}", $storedName, config('documents.disk', 'local'));
        $checksum   = hash_file('sha256', $file->getPathname());

        $document = DB::transaction(function () use ($case, $file, $storedName, $path, $checksum, $request, $user) {
            $doc = Document::create([
                'case_id'       => $case->id,
                'uploaded_by'   => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => $storedName,
                'disk'          => config('documents.disk', 'local'),
                'path'          => $path,
                'mime_type'     => $file->getMimeType(),
                'size_bytes'    => $file->getSize(),
                'document_type' => $request->document_type,
                'checksum'      => $checksum,
            ]);

            // Outbox event
            \App\Models\IntegrationQueue::create([
                'aggregate_type' => 'Document',
                'aggregate_id'   => $doc->id,
                'event_type'     => 'created',
                'payload'        => ['case_id' => $case->id, 'type' => $request->document_type],
                'available_at'   => now(),
            ]);

            return $doc;
        });
        
        // Dispatch DocumentUploaded event for OCR processing
        event(new \App\Events\DocumentUploaded($document));

        $this->audit->log($user, 'document.upload', Document::class, $document->id, null, [
            'document_type' => $document->document_type,
            'case_id'       => $case->id,
        ]);

        return response()->json([
            'message'  => 'Dokumen berhasil diupload.',
            'document' => $document->only(['id', 'original_name', 'document_type', 'status', 'size_bytes']),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /documents/download/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function download(int $id): mixed
    {
        $document = Document::findOrFail($id);
        $user     = auth()->user();

        $this->rebac->enforce($user, 'download', 'Document', $id);

        $this->audit->log($user, 'document.download', Document::class, $id);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /documents/{id}
    // ─────────────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $document = Document::with(['uploader:id,name', 'ocrResult'])->findOrFail($id);
        $this->rebac->enforce(auth()->user(), 'view', 'Document', $id);

        return response()->json($document);
    }
}
