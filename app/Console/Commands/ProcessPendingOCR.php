<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\OcrResult;
use App\Services\OCRService;
use Illuminate\Console\Command;

class ProcessPendingOCR extends Command
{
    protected $signature = 'ocr:process-pending {--case-ids=13,14,15,16,17,18,19,20}';
    protected $description = 'Queue pending OCR documents for given cases';

    public function __construct(private readonly OCRService $ocr)
    {
        parent::__construct();
    }

    public function handle()
    {
        $caseIds = explode(',', $this->option('case-ids'));
        
        $documents = Document::whereIn('case_id', $caseIds)
            ->whereDoesntHave('ocrResult')  // No OCR result yet
            ->get();

        $count = $documents->count();
        
        if ($count === 0) {
            $this->info('No pending documents to process.');
            return 0;
        }

        $this->info("Queueing $count documents for OCR processing...");
        
        foreach ($documents as $doc) {
            try {
                $this->ocr->dispatch($doc);
                $this->line("✓ Doc #{$doc->id} queued");
            } catch (\Exception $e) {
                $this->error("✗ Doc #{$doc->id}: {$e->getMessage()}");
            }
        }

        $this->info("Done! Queued $count documents.");
        $this->info("Run: php artisan queue:work --queue=ocr,default");
        
        return 0;
    }
}
