<?php

namespace App\Services;

use App\Models\Document;
use App\Models\OcrJob;
use App\Models\OcrResult;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OCRService
{
    private string $serviceUrl;
    private string $secretKey;
    private int    $timeout;

    public function __construct()
    {
        $this->serviceUrl = rtrim(config('ocr.service_url'), '/');
        $this->secretKey  = config('ocr.secret_key');
        $this->timeout    = config('ocr.timeout', 60);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Dispatch OCR job for a document.
     */
    public function dispatch(Document $document): OcrJob
    {
        $job = OcrJob::updateOrCreate(
            ['document_id' => $document->id],
            ['status' => 'QUEUED', 'attempts' => 0, 'error_message' => null]
        );

        dispatch(new \App\Jobs\OCRJob($document))->onQueue('ocr');

        Log::channel('ocr')->info('OCR job dispatched', [
            'document_id' => $document->id,
            'job_id'      => $job->id,
        ]);

        return $job;
    }

    /**
     * Process OCR synchronously (called by Job worker).
     */
    public function process(Document $document): OcrResult
    {
        $job = OcrJob::firstOrCreate(['document_id' => $document->id]);
        $job->update(['status' => 'PROCESSING', 'started_at' => now(), 'attempts' => $job->attempts + 1]);

        $startTime = microtime(true);

        try {
            $filePath = Storage::disk($document->disk)->path($document->path);
            $payload  = $this->callMicroservice($filePath, $document->mime_type);

            $result = $this->persistResult($document, $payload, $startTime);
            
            // ✨ AUTO-VALIDATION: Compare OCR result with input data
            if ($document->case_id || $document->public_submission_id) {
                try {
                    $validationService = app(OCRValidationService::class);
                    $validation = $validationService->compare($result);
                    
                    $result->update(['has_validation' => true]);
                    
                    Log::channel('ocr')->info('OCR validation completed', [
                        'document_id'        => $document->id,
                        'validation_id'      => $validation->id,
                        'validation_status'  => $validation->validation_status,
                        'match_score'        => $validation->overall_match_score,
                    ]);
                } catch (\Throwable $validationError) {
                    Log::channel('ocr')->warning('OCR validation failed', [
                        'document_id' => $document->id,
                        'error'       => $validationError->getMessage(),
                    ]);
                    // Don't fail the whole OCR process if validation fails
                }
            }

            $job->update(['status' => 'DONE', 'finished_at' => now(), 'result_payload' => $payload]);
            $document->update(['status' => 'PROCESSED']);

            Log::channel('ocr')->info('OCR completed', [
                'document_id'       => $document->id,
                'overall_confidence' => $result->overall_confidence,
                'ocr_status'        => $result->ocr_status,
            ]);

            return $result;

        } catch (\Throwable $e) {
            $backoff = config('ocr.retry.backoff_seconds')[$job->attempts - 1] ?? 60;
            $job->update([
                'status'        => 'FAILED',
                'finished_at'   => now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::channel('ocr')->error('OCR failed', [
                'document_id' => $document->id,
                'error'       => $e->getMessage(),
                'attempt'     => $job->attempts,
            ]);

            throw $e;
        }
    }

    /**
     * Get result for a document.
     */
    public function getResult(int $documentId): ?OcrResult
    {
        return OcrResult::where('document_id', $documentId)->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function callMicroservice(string $filePath, string $mimeType): array
    {
        $response = Http::withHeaders([
            'X-OCR-Secret' => $this->secretKey,
        ])
        ->timeout($this->timeout)
        ->attach('file', fopen($filePath, 'r'), basename($filePath), ['Content-Type' => $mimeType])
        ->post("{$this->serviceUrl}/ocr/process");

        if ($response->status() === 401) {
            $fallbackSecret = (string) config('ocr.fallback_secret_key', 'change_me');
            if ($fallbackSecret !== '' && $fallbackSecret !== $this->secretKey) {
                Log::channel('ocr')->warning('Primary OCR secret rejected, retrying with fallback secret', [
                    'service_url' => $this->serviceUrl,
                ]);

                $response = Http::withHeaders([
                    'X-OCR-Secret' => $fallbackSecret,
                ])
                ->timeout($this->timeout)
                ->attach('file', fopen($filePath, 'r'), basename($filePath), ['Content-Type' => $mimeType])
                ->post("{$this->serviceUrl}/ocr/process");
            }
        }

        if (!$response->successful()) {
            throw new \RuntimeException(
                "OCR microservice error [{$response->status()}]: " . $response->body()
            );
        }

        return $response->json();
    }

    private function persistResult(Document $document, array $payload, float $startTime): OcrResult
    {
        $confidence = $payload['confidence'] ?? [];
        $overall    = count($confidence) > 0
            ? round(array_sum($confidence) / count($confidence), 4)
            : 0.0;

        $ocrStatus = $this->determineOcrStatus($overall, $payload);
        $errors    = $this->validateFields($payload);

        $processingMs = (int) round((microtime(true) - $startTime) * 1000);

        $normalizedGender = $this->normalizeGender($payload['jenis_kelamin'] ?? null);

        return OcrResult::updateOrCreate(
            ['document_id' => $document->id],
            [
                'case_id'           => $document->case_id,
            'nik'               => $this->limitString($payload['nik'] ?? null, 16),
            'no_kk'             => $this->limitString($payload['kk'] ?? null, 16),
            'nama'              => $this->limitString($payload['nama'] ?? null, 255),
            'tgl_lahir'         => $this->limitString($payload['tgl_lahir'] ?? null, 20),
            'tempat_lahir'      => $this->limitString($payload['tempat_lahir'] ?? null, 255),
            'jenis_kelamin'     => $normalizedGender,
            'alamat'            => $this->limitString($payload['alamat'] ?? null, 255),
            'rt_rw'             => $this->limitString($payload['rt_rw'] ?? null, 10),
            'kelurahan'         => $this->limitString($payload['kelurahan'] ?? null, 255),
            'kecamatan'         => $this->limitString($payload['kecamatan'] ?? null, 255),
            'kabupaten'         => $this->limitString($payload['kabupaten'] ?? null, 255),
            'provinsi'          => $this->limitString($payload['provinsi'] ?? null, 255),
                'raw_text'          => $payload['raw_text']     ?? [],
                'confidence_scores' => $confidence,
                'overall_confidence' => $overall,
                'is_validated'      => empty($errors),
                'validation_errors' => $errors,
                'ocr_status'        => $ocrStatus,
                'engine_version'    => $payload['engine_version'] ?? null,
                'processing_time_ms' => $processingMs,
            ]
        );
    }

    private function limitString($value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $maxLength);
    }

    private function normalizeGender($value): ?string
    {
        $raw = strtoupper((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        if (str_contains($raw, 'LAKI')) {
            return 'LAKI-LAKI';
        }

        if (str_contains($raw, 'PEREMPUAN')) {
            return 'PEREMPUAN';
        }

        return $this->limitString($raw, 10);
    }

    private function determineOcrStatus(float $overall, array $payload): string
    {
        $minThreshold = config('ocr.confidence.default', 0.75);
        $nik = (string) ($payload['nik'] ?? '');
        $hasValidNik = $nik !== '' && preg_match('/^\d{16}$/', $nik) === 1;

        // For KTP workflow, valid NIK is the primary success gate; KK is optional.
        if ($overall >= config('ocr.confidence.nik', 0.85) && $hasValidNik) {
            return 'SUCCESS';
        }
        if ($overall >= $minThreshold) {
            return 'PARTIAL';
        }
        return 'FAILED';
    }

    private function validateFields(array $data): array
    {
        $errors   = [];
        $patterns = config('ocr.patterns', []);

        foreach ($patterns as $field => $pattern) {
            $val = $data[$field] ?? null;
            if ($val && !preg_match($pattern, $val)) {
                $errors[$field] = "Format tidak valid: {$val}";
            }
        }

        return $errors;
    }
}
