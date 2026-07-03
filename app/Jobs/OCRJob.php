<?php

namespace App\Jobs;

use App\Models\CaseModel;
use App\Models\Document;
use App\Services\OCRService;
use App\Services\WorkflowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OCRJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int    $tries         = 1;
    public int    $timeout       = 600;
    public array  $backoff       = [];

    // ─ Only these document types can be OCR processed
    protected const ALLOWED_DOCUMENT_TYPES = ['KTP', 'KTP_SUAMI', 'KTP_ISTRI'];

    public function __construct(private readonly Document $document)
    {
        // Skip non-KTP documents
        if (!in_array($document->document_type, self::ALLOWED_DOCUMENT_TYPES)) {
            Log::channel('ocr')->info('OCRJob skipped - non-KTP document', [
                'document_id' => $document->id,
                'document_type' => $document->document_type,
            ]);
            throw new \Exception("OCR hanya untuk KTP. Document type: {$document->document_type} tidak didukung.");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function handle(OCRService $ocr, WorkflowService $workflow): void
    {
        Log::channel('ocr')->info('OCRJob starting', ['document_id' => $this->document->id]);

        try {
            $result = $ocr->process($this->document);

            // Auto-advance workflow to OCR_PROCESSED
            $case = $this->document->case;
            if ($case && $case->status === 'SUBMITTED') {
                $systemUser = \App\Models\User::where('email', 'system@internal')->first();
                if ($systemUser && $case->canTransitionTo('OCR_PROCESSED')) {
                    $workflow->markOcrProcessed($case, $systemUser);
                }
            }

            Log::channel('ocr')->info('OCRJob completed', [
                'document_id'       => $this->document->id,
                'ocr_status'        => $result->ocr_status,
                'overall_confidence' => $result->overall_confidence,
            ]);

        } catch (\Throwable $e) {
            Log::channel('ocr')->error('OCRJob failed', [
                'document_id' => $this->document->id,
                'error'       => $e->getMessage(),
                'attempt'     => $this->attempts(),
            ]);

            throw $e;  // Let Laravel retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->document->update(['status' => 'REJECTED']);

        Log::channel('ocr')->critical('OCRJob permanently failed', [
            'document_id' => $this->document->id,
            'error'       => $exception->getMessage(),
        ]);
    }

    public function middleware(): array
    {
        return [new RateLimited('ocr')];
    }
}
