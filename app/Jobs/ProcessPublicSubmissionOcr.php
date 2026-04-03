<?php

namespace App\Jobs;

use App\Models\PublicSubmission;
use App\Models\PublicSubmissionDocument;
use App\Services\OCRService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Process OCR for public submission documents.
 * Public submissions have documents in PublicSubmissionDocument model,
 * separate from the official Document model used in cases.
 */
class ProcessPublicSubmissionOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int    $tries         = 3;
    public int    $timeout       = 120;
    public array  $backoff       = [10, 30, 60];
    public bool   $failOnTimeout = true;

    public function __construct(
        private readonly PublicSubmission $submission,
        private readonly PublicSubmissionDocument $document
    ) {
        $this->queue = 'ocr';
    }

    /**
     * Execute the job.
     */
    public function handle(OCRService $ocr): void
    {
        Log::channel('ocr')->info('ProcessPublicSubmissionOcr starting', [
            'submission_id' => $this->submission->id,
            'document_id' => $this->document->id,
            'document_type' => $this->document->document_type,
            'tracking_token' => $this->submission->tracking_token,
        ]);

        try {
            $filePath = Storage::disk('public')->path($this->document->stored_path);

            if (!file_exists($filePath)) {
                Log::channel('ocr')->error('Public submission document file not found', [
                    'document_id' => $this->document->id,
                    'path' => $filePath,
                ]);
                return;
            }

            // Send to OCR service and get results
            $ocrResults = $this->sendToOcrService($filePath);

            if ($ocrResults) {
                // Store OCR results in document
                $this->document->update([
                    'ocr_status' => 'PROCESSED',
                    'ocr_data' => json_encode($ocrResults),
                    'processed_at' => now(),
                ]);

                Log::channel('ocr')->info('ProcessPublicSubmissionOcr completed', [
                    'submission_id' => $this->submission->id,
                    'document_id' => $this->document->id,
                    'ocr_confidence' => $ocrResults['overall_confidence'] ?? null,
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('ocr')->error('ProcessPublicSubmissionOcr failed', [
                'submission_id' => $this->submission->id,
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send document to OCR service.
     */
    private function sendToOcrService(string $filePath): ?array
    {
        try {
            $serviceUrl = rtrim(config('ocr.service_url'), '/');
            $secretKey = config('ocr.secret_key');

            if (!$serviceUrl) {
                Log::warning('OCR service URL not configured');
                return null;
            }

            $response = \Illuminate\Support\Facades\Http::timeout(120)
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post("{$serviceUrl}/process", [
                    'secret' => $secretKey,
                    'document_type' => $this->document->document_type,
                    'submission_id' => $this->submission->id,
                    'token' => $this->submission->tracking_token,
                ]);

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::warning('OCR service returned error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to send document to OCR service', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
