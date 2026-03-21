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

    public int    $tries         = 3;
    public int    $timeout       = 120;
    public array  $backoff       = [10, 30, 60];
    public bool   $failOnTimeout = true;

    public function __construct(private readonly Document $document) {}

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
        $this->document->ocrJob?->update([
            'status'        => 'FAILED',
            'error_message' => $exception->getMessage(),
            'finished_at'   => now(),
        ]);
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
