<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowRawOcr extends Command
{
    protected $signature = 'ocr:show-raw {--job-ids=19,20,21,22,23}';
    protected $description = 'Show raw OCR extraction data without validation';

    public function handle()
    {
        $jobIds = array_map('intval', explode(',', $this->option('job-ids')));
        
        $this->line('═══════════════════════════════════════════════════');
        $this->line('RAW OCR EXTRACTION (After Aggressive Preprocessing)');
        $this->line('═══════════════════════════════════════════════════');
        $this->line('');
        
        foreach ($jobIds as $jobId) {
            $job = DB::table('ocr_jobs')->where('id', $jobId)->first();
            if (!$job) continue;
            
            $result = DB::table('ocr_results')->where('document_id', $job->document_id)->first();
            if (!$result) continue;
            
            $doc = DB::table('documents')->where('id', $job->document_id)->first();
            
            $this->line("📄 JOB {$jobId} - Doc {$job->document_id} ({$doc->document_type})");
            $this->line(str_repeat('─', 50));
            
            $this->line(sprintf("  NIK:       %-20s (Conf: %.1f%%)", 
                $result->nik ?: "[EMPTY]",
                ($result->confidence_nik ?? 0) * 100
            ));
            $this->line(sprintf("  Nama:      %-20s (Conf: %.1f%%)", 
                $result->nama ?: "[EMPTY]",
                ($result->confidence_nama ?? 0) * 100
            ));
            $this->line(sprintf("  Alamat:    %-20s (Conf: %.1f%%)", 
                substr($result->alamat ?: "[EMPTY]", 0, 20),
                ($result->confidence_alamat ?? 0) * 100
            ));
            $this->line(sprintf("  RT/RW:     %-20s", $result->rt_rw ?: "[EMPTY]"));
            $this->line(sprintf("  Kelurahan: %-20s", $result->kelurahan ?: "[EMPTY]"));
            
            $conf = json_decode($result->confidence, true) ?? [];
            $overall = count($conf) > 0 ? array_sum($conf) / count($conf) : 0;
            $this->line(sprintf("  Overall:   %.1f%%\n", $overall * 100));
        }
        
        $this->info('✅ Raw extraction data shown');
    }
}
