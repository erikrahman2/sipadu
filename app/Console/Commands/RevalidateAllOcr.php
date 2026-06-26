<?php

namespace App\Console\Commands;

use App\Models\OcrValidation;
use App\Models\OcrResult;
use App\Services\OCRValidationService;
use Illuminate\Console\Command;

class RevalidateAllOcr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ocr:revalidate-all';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Re-validate ALL OCR results with improved logic';

    /**
     * Execute the console command.
     */
    public function handle(OCRValidationService $validationService)
    {
        $this->info("\n╔════════════════════════════════════════════════════════════════╗");
        $this->info("║     Re-validating All OCR Results with Improved Logic            ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝\n");

        // Clear all validations
        $this->info("Step 1: Clearing old validations...");
        $cleared = OcrValidation::truncate();
        $this->line("  ✓ Cleared all validations\n");

        // Get all OCR results
        $this->info("Step 2: Re-validating OCR results");
        $ocrResults = OcrResult::whereNotNull('document_id')->get();
        
        if ($ocrResults->isEmpty()) {
            $this->error('No OCR results found!');
            return 1;
        }

        $bar = $this->output->createProgressBar($ocrResults->count());
        $bar->start();

        $stats = [
            'excellent' => 0,
            'good' => 0,
            'poor' => 0,
            'total' => 0,
        ];

        foreach ($ocrResults as $result) {
            try {
                $validation = $validationService->compare($result);
                
                if ($validation->overall_match_score >= 90) {
                    $stats['excellent']++;
                } elseif ($validation->overall_match_score >= 75) {
                    $stats['good']++;
                } else {
                    $stats['poor']++;
                }
                $stats['total']++;
                
            } catch (\Exception $e) {
                $this->error("\nError for result {$result->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }

        $bar->finish();
        
        $this->info("\n\n═══════════════════════════════════════════════════════════════\n");
        $this->info("✓ Re-validation Complete!");
        $this->info(sprintf("  Excellent (90+%%): %d", $stats['excellent']));
        $this->info(sprintf("  Good (75-89%%):    %d", $stats['good']));
        $this->info(sprintf("  Poor (<75%%):      %d", $stats['poor']));
        $this->info(sprintf("  Total:             %d", $stats['total']));
        
        $avgScore = $stats['total'] > 0 
            ? OcrValidation::avg('overall_match_score') 
            : 0;
        $this->info(sprintf("  Average Score:     %.1f%%", $avgScore));

        return 0;
    }
}
