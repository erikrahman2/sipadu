<?php

namespace App\Console\Commands;

use App\Jobs\OCRJob as OCRJobClass;
use App\Models\OcrJob as OcrJobModel;
use App\Models\OcrResult;
use App\Models\OcrValidation;
use Illuminate\Console\Command;

class ResetOcrJobs extends Command
{
    protected $signature = 'ocr:reset {--ids=19,20,21,22,23 : Comma-separated OCR job IDs}';
    protected $description = 'Reset and re-queue OCR jobs for reprocessing with new preprocessing';

    public function handle()
    {
        $ids = array_map('intval', explode(',', $this->option('ids')));
        
        $this->info("=== OCR RESET & REPROCESS ===");
        
        foreach ($ids as $jobId) {
            $job = OcrJobModel::find($jobId);
            if (!$job) {
                $this->line("❌ Job {$jobId} not found");
                continue;
            }
            
            $doc = $job->document;
            if (!$doc) {
                $this->line("❌ Document for Job {$jobId} not found");
                continue;
            }
            
            $this->line("\n📋 Job {$jobId} (Doc: {$doc->id} - {$doc->document_type})");
            
            // Reset job status
            $job->update([
                'status' => 'QUEUED',
                'attempts' => 0,
                'error_message' => null,
                'started_at' => null,
                'finished_at' => null,
                'result_payload' => null,
            ]);
            
            // Delete old results for fresh extraction
            OcrResult::where('document_id', $doc->id)->delete();
            OcrValidation::where('document_id', $doc->id)->delete();
            
            $this->line("   ✓ Reset job & deleted old validations");
            
            // Re-dispatch the job
            dispatch(new OCRJobClass($doc))->onQueue('ocr');
            $this->line("   ✓ Dispatched for re-processing (aggressive preprocessing)");
        }
        
        $this->info("\n✅ Reset complete! All jobs re-queued for processing.");
        $this->line("   Run: php artisan queue:work --queue=ocr --max-time=600");
    }
}
