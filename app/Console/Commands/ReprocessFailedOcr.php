<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\OcrResult;
use App\Models\OcrValidation;
use App\Jobs\OCRJob;
use App\Services\OCRValidationService;
use Illuminate\Console\Command;

class ReprocessFailedOcr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ocr:reprocess-failed
                            {--all : Reprocess ALL failed/partial records (excludes SUCCESS)}
                            {--full : Reprocess ALL documents including SUCCESS - use after OCR engine upgrade}
                            {--ktp : Reprocess only KTP documents (KTP_SUAMI, KTP_ISTRI, KTP)}
                            {--id=* : Specific document IDs to reprocess}
                            {--fix-missing : Auto-fix KTP docs that have OCR but missing validation}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Reprocess documents with OCR. Options: --all (failed/partial), --full (all), --ktp (KTP only), --id=X (specific), --fix-missing (auto-fix missing validations)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("\n╔════════════════════════════════════════════════════════════════╗");
        $this->info("║     Reprocessing OCR Documents                                ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝\n");

        // ── Mode: Fix Missing Validations ──────────────────────────
        if ($this->option('fix-missing')) {
            return $this->fixMissingValidations();
        }

        $documentIds = [];

        // Mode: Specific IDs
        if ($ids = $this->option('id')) {
            $documentIds = $ids;
            $this->info("Mode: Reprocessing specific document IDs: " . implode(', ', $documentIds));
        }
        // Mode: Full reprocess (including SUCCESS)
        elseif ($this->option('full')) {
            $allResults = OcrResult::all();
            $documentIds = $allResults->pluck('document_id')->unique()->values()->toArray();
            $this->info("Mode: FULL reprocess - ALL documents including SUCCESS (" . count($documentIds) . " documents)");
        }
        // Mode: KTP only
        elseif ($this->option('ktp')) {
            $ktpDocs = Document::whereIn('document_type', ['KTP_SUAMI', 'KTP_ISTRI', 'KTP'])->get();
            $documentIds = $ktpDocs->pluck('id')->toArray();
            $this->info("Mode: KTP only - Reprocessing " . count($documentIds) . " KTP documents");
        }
        // Mode: Failed/Partial only
        elseif ($this->option('all')) {
            $failedOcrRecords = OcrResult::whereIn('ocr_status', ['FAILED', 'PARTIAL'])->get();
            $documentIds = $failedOcrRecords->pluck('document_id')->unique()->values()->toArray();
            $this->info("Mode: Reprocessing failed/partial only (" . count($documentIds) . " documents)");
        }
        // Default: Failed/Partial only
        else {
            $failedOcrRecords = OcrResult::whereIn('ocr_status', ['FAILED', 'PARTIAL'])->get();
            $documentIds = $failedOcrRecords->pluck('document_id')->unique()->values()->toArray();
            $this->info("Mode: Reprocessing failed/partial only (" . count($documentIds) . " documents)");
        }

        if (empty($documentIds)) {
            $this->warn('No documents to reprocess!');
            return 0;
        }

        $this->info("");

        // Get documents with their types - MUST fetch BEFORE clearing results
        $docs = Document::whereIn('id', $documentIds)
            ->orderByRaw("FIELD(document_type, 'KTP_SUAMI', 'KTP_ISTRI', 'KTP', 'KK', 'default')")
            ->get();

        // Build status summary BEFORE clearing (from cached data)
        $statusSummary = [];
        $docStatuses = [];
        foreach ($docs as $doc) {
            $ocrResult = OcrResult::where('document_id', $doc->id)->first();
            $status = $ocrResult ? $ocrResult->ocr_status : 'NO_RESULT';
            $nik = $ocrResult ? ($ocrResult->nik ?: 'NO_NIK') : 'NO_RESULT';
            $nik = strlen($nik) > 16 ? substr($nik, 0, 16) . '...' : $nik;

            $statusKey = $status;
            if (!isset($statusSummary[$statusKey])) {
                $statusSummary[$statusKey] = 0;
            }
            $statusSummary[$statusKey]++;

            $docStatuses[$doc->id] = $status;

            $this->line("  #" . str_pad($doc->id, 3, '0', STR_PAD_LEFT) .
                       " | " . str_pad($doc->document_type, 10) .
                       " | Status: " . str_pad($status, 8) .
                       " | NIK: " . $nik);
        }

        $this->info("\nStatus Summary: " . json_encode($statusSummary));

        if ($this->option('full') || $this->option('ktp') || $this->option('id')) {
            // Auto-confirm for batch operations (full, ktp, id modes)
            $confirmed = true;
        } elseif (!$this->confirm("\nProceed with reprocessing " . count($docs) . " documents?")) {
            $this->info('Cancelled.');
            return 0;
        } else {
            $confirmed = true;
        }

        $this->info("\nStep 1: Clearing old OCR data...");
        $resultCleared = OcrResult::whereIn('document_id', $documentIds)->forceDelete();
        $validationCleared = OcrValidation::whereIn('document_id', $documentIds)->delete();
        $this->line("  ✓ Cleared " . $resultCleared . " OCR results");
        $this->line("  ✓ Cleared " . $validationCleared . " validations\n");

        $this->info("Step 2: Queuing documents for reprocessing (priority order: FAILED → PARTIAL → SUCCESS):");
        $queued = 0;
        $errors = [];

        // Priority queue: FAILED first, then PARTIAL, then SUCCESS
        $failedDocs = $docs->filter(fn($d) => ($docStatuses[$d->id] ?? '') === 'FAILED');
        $partialDocs = $docs->filter(fn($d) => ($docStatuses[$d->id] ?? '') === 'PARTIAL');
        $successDocs = $docs->filter(fn($d) => ($docStatuses[$d->id] ?? '') === 'SUCCESS');
        $noResultDocs = $docs->filter(fn($d) => ($docStatuses[$d->id] ?? 'NO_RESULT') === 'NO_RESULT');

        $priority = $failedDocs->concat($partialDocs)->concat($successDocs)->concat($noResultDocs);

        foreach ($priority as $doc) {
            try {
                OCRJob::dispatch($doc)->onQueue('ocr');
                $docStatus = $docStatuses[$doc->id] ?? 'UNKNOWN';
                $this->line("  ✓ #" . str_pad($doc->id, 3, '0', STR_PAD_LEFT) .
                           " | " . str_pad($doc->document_type, 10) .
                           " | (was: " . str_pad($docStatus, 8) . ") " .
                           "| Case #" . ($doc->case_id ?? '-') .
                           " | Submission #" . ($doc->public_submission_id ?? '-'));
                $queued++;
            } catch (\Exception $e) {
                $this->error("  ✗ #{$doc->id} - Error: {$e->getMessage()}");
                $errors[] = $doc->id;
            }
        }

        $this->info("\n╔════════════════════════════════════════════════════════════════╗");
        $this->info("║ Documents queued: " . str_pad($queued . "/" . count($docs), 48) . "║");
        $this->info("╚════════════════════════════════════════════════════════════════╝\n");

        if (!empty($errors)) {
            $this->warn("Errors: " . implode(', ', $errors));
        }

        $this->info("📋 Next steps:");
        $this->line("   1. Start queue worker:");
        $this->line("      php artisan queue:work --queue=ocr,default --tries=4\n");
        $this->line("   2. Monitor progress:");
        $this->line("      tail -f storage/logs/laravel.log");
        $this->line("      tail -f storage/logs/ocr/ocr-" . date('Y-m-d') . ".log\n");
        $this->line("   3. Check queue status:");
        $this->line("      php artisan queue:monitor ocr:ocr,default\n");
        $this->line("   4. Check failed jobs:");
        $this->line("      php artisan queue:failed\n");

        return $queued === count($docs) ? 0 : 1;
    }

    /**
     * Auto-fix KTP documents that have OCR results but missing validations.
     * This is a safety net to ensure no validation is accidentally skipped.
     */
    private function fixMissingValidations(): int
    {
        $this->info("╔════════════════════════════════════════════════════════════════╗");
        $this->info("║     Fixing Missing Validations                               ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝\n");

        // Find KTP documents with OCR but no validation
        $missing = Document::whereIn('document_type', ['KTP', 'KTP_SUAMI', 'KTP_ISTRI'])
            ->whereNotIn('id', OcrValidation::select('document_id'))
            ->whereIn('id', OcrResult::select('document_id'))
            ->with(['case', 'ocrResult'])
            ->get();

        if ($missing->isEmpty()) {
            $this->info("✓ All KTP documents with OCR have validations. Nothing to fix.");
            return 0;
        }

        $this->warn("Found {$missing->count()} KTP documents with OCR but missing validation:");
        foreach ($missing as $doc) {
            $ocr = $doc->ocrResult;
            $case = $doc->case;
            $this->line("  #{$doc->id} | {$doc->document_type} | Case: " . ($case?->case_number ?? 'none'));
            $this->line("    OCR Status: {$ocr?->ocr_status} | Confidence: " . number_format($ocr?->overall_confidence ?? 0, 4));
        }
        $this->info("");

        if (!$this->confirm("Fix {$missing->count()} missing validations?")) {
            $this->info('Cancelled.');
            return 0;
        }

        $validationService = app(OCRValidationService::class);
        $fixed = 0;
        $failed = [];

        $this->info("\nProcessing...");
        foreach ($missing as $doc) {
            $ocr = $doc->ocrResult;
            if (!$ocr) {
                $this->error("  ✗ #{$doc->id} - No OCR result found");
                $failed[] = $doc->id;
                continue;
            }

            try {
                $validation = $validationService->compare($ocr);
                if ($validation) {
                    $ocr->update(['has_validation' => true]);
                    $this->line("  ✓ #{$doc->id} ({$doc->document_type}): {$validation->validation_status} (" . number_format($validation->overall_match_score, 1) . "%)");
                    $fixed++;
                } else {
                    $this->warn("  ⚠ #{$doc->id} - Validation returned null (no input data)");
                    $failed[] = $doc->id;
                }
            } catch (\Throwable $e) {
                $this->error("  ✗ #{$doc->id} - Error: {$e->getMessage()}");
                $failed[] = $doc->id;
            }
        }

        $this->info("\n╔════════════════════════════════════════════════════════════════╗");
        $this->info("║  Fixed: {$fixed}/{$missing->count()}                                        ║");
        if (!empty($failed)) {
            $this->warn("║  Failed: " . count($failed) . "                                               ║");
        }
        $this->info("╚════════════════════════════════════════════════════════════════╝");

        return empty($failed) ? 0 : 1;
    }
}
