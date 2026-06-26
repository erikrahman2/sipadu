<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Jobs\OCRJob;
use Illuminate\Console\Command;

class ReprocessFailedOcr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ocr:reprocess-failed {--all : Reprocess ALL failed/partial records}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Reprocess documents with failed OCR extraction. Use --all for all failed/partial records.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("\n╔════════════════════════════════════════════════════════════════╗");
        $this->info("║     Reprocessing Failed OCR Documents                          ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝\n");

        // Get list of document IDs to process
        if ($this->option('all')) {
            // Get ALL failed/partial OCR records dynamically
            $failedOcrRecords = \App\Models\OcrResult::whereIn('ocr_status', ['FAILED', 'PARTIAL'])->get();
            $failedDocIds = $failedOcrRecords->pluck('document_id')->unique()->values()->toArray();
            $this->info("Mode: Reprocessing ALL failed/partial records (" . count($failedDocIds) . " documents)\n");
        } else {
            // Original hardcoded list for backward compatibility
            $failedDocIds = [54, 58, 61, 64];
            $this->info("Mode: Reprocessing specific documents (hardcoded list)\n");
        }
        
        $docs = Document::whereIn('id', $failedDocIds)->get();

        if ($docs->isEmpty()) {
            $this->error('No documents found!');
            return 1;
        }

        $this->info("Step 1: Clearing old OCR data...");
        $resultCleared = \App\Models\OcrResult::whereIn('document_id', $docs->pluck('id'))->delete();
        $validationCleared = \App\Models\OcrValidation::whereIn('document_id', $docs->pluck('id'))->delete();
        $this->line("  ✓ Cleared " . $resultCleared . " OCR results");
        $this->line("  ✓ Cleared " . $validationCleared . " validations\n");

        $this->info("Step 2: Queuing documents for reprocessing:");
        $queued = 0;
        foreach ($docs as $doc) {
            try {
                OCRJob::dispatch($doc)->onQueue('ocr');
                $this->line("  ✓ #" . str_pad($doc->id, 3, '0', STR_PAD_LEFT) . " | " . str_pad($doc->document_type, 10) . " | Case #" . $doc->case_id);
                $queued++;
            } catch (\Exception $e) {
                $this->error("  ✗ #{$doc->id} - Error: {$e->getMessage()}");
            }
        }

        $this->info("\n╔════════════════════════════════════════════════════════════════╗");
        $this->info("║ Documents queued: " . str_pad($queued . "/" . $docs->count(), 48) . "║");
        $this->info("╚════════════════════════════════════════════════════════════════╝\n");

        $this->info("📋 Next steps:");
        $this->line("   1. Start queue worker:");
        $this->line("      php artisan queue:work --queue=ocr,default\n");
        $this->line("   2. Monitor progress:");
        $this->line("      tail -f storage/logs/laravel.log\n");
        $this->line("   3. Check remaining jobs:");
        $this->line("      php artisan queue:failed\n");

        return $queued === $docs->count() ? 0 : 1;
    }
}
