<?php

namespace App\Console\Commands;

use App\Models\OcrValidation;
use Illuminate\Console\Command;

class ShowValidationResults extends Command
{
    protected $signature = 'ocr:show-results {--limit=10}';
    protected $description = 'Show recent OCR validation results';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->line('═════════════════════════════════════════════════════════════');
        $this->line('OCR VALIDATION RESULTS (After Aggressive Preprocessing)');
        $this->line('═════════════════════════════════════════════════════════════');
        $this->line('');
        
        $validations = OcrValidation::orderBy('id', 'desc')->limit($limit)->get();
        
        $this->line(
            sprintf("%-4s %-22s %-8s %-5s %-5s %-5s %s", 
                "ID", "Status", "Score", "NIK%", "Nama%", "Almt%", "Rev")
        );
        $this->line(str_repeat('─', 65));
        
        foreach ($validations as $v) {
            $fields = $v->field_scores ?? [];
            $this->line(
                sprintf("%-4d %-22s %.0f%% %-5.0f %-5.0f %-5.0f %s",
                    $v->id,
                    $v->validation_status,
                    $v->overall_match_score * 100,
                    ($fields['nik'] ?? 0) * 100,
                    ($fields['nama'] ?? 0) * 100,
                    ($fields['alamat'] ?? 0) * 100,
                    ($v->is_reviewed ? "[✓]" : "[ ]")
                )
            );
        }
        
        $this->line('');
        $this->line(str_repeat('═', 65));
        $this->info('✅ Jobs processed with aggressive preprocessing enabled');
        $this->line('   Algorithm: CLAHE 4-6, Upscaling 2-3x, Morphological Ops');
    }
}
