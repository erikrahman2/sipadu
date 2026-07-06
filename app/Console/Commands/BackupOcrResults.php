<?php

namespace App\Console\Commands;

use App\Models\OcrResult;
use App\Models\OcrValidation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupOcrResults extends Command
{
    protected $signature = 'ocr:backup
                            {--path= : Custom backup path}
                            {--compress : Compress backup with gzip}';

    protected $description = 'Backup OCR results to JSON and CSV files';

    public function handle(): int
    {
        $this->info("\n╔════════════════════════════════════════════════════════════════╗");
        $this->info("║     OCR Results Backup                                        ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝\n");

        $timestamp = now()->format('Y-m-d_His');
        $customPath = $this->option('path');
        $backupDir = $customPath ?? storage_path('backups/ocr/' . $timestamp);

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $this->info("Backup destination: {$backupDir}\n");

        // Get all OCR results with validation
        $results = OcrResult::with('validation')->get();
        $total = $results->count();

        if ($total === 0) {
            $this->warn("No OCR results to backup!");
            return 0;
        }

        $this->info("Backing up {$total} OCR results...\n");

        // Progress bar
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // ===================
        // JSON BACKUP
        // ===================
        $jsonFile = $backupDir . '/ocr_results_' . $timestamp . '.json';
        $jsonData = [
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'total_records' => $total,
                'version' => '1.0',
            ],
            'statistics' => [
                'total' => $total,
                'success' => $results->where('ocr_status', 'SUCCESS')->count(),
                'partial' => $results->where('ocr_status', 'PARTIAL')->count(),
                'failed' => $results->where('ocr_status', 'FAILED')->count(),
                'validated' => $results->where('is_validated', true)->count(),
                'avg_confidence' => round($results->avg('overall_confidence'), 4),
            ],
            'results' => [],
        ];

        // ===================
        // CSV BACKUP
        // ===================
        $csvFile = $backupDir . '/ocr_results_' . $timestamp . '.csv';
        $csvHeaders = [
            'id', 'document_id', 'case_id', 'nik', 'no_kk', 'nama',
            'tgl_lahir', 'tempat_lahir', 'jenis_kelamin',
            'alamat', 'rt_rw', 'kelurahan', 'kecamatan', 'kabupaten', 'provinsi',
            'ocr_status', 'overall_confidence', 'is_validated',
            'validation_status', 'overall_match_score',
            'processing_time_ms', 'engine_version', 'created_at',
        ];

        $csvHandle = fopen($csvFile, 'w');
        fputcsv($csvHandle, $csvHeaders);

        foreach ($results as $result) {
            $validation = $result->validation;

            // JSON data
            $jsonData['results'][] = [
                'id' => $result->id,
                'document_id' => $result->document_id,
                'case_id' => $result->case_id,
                'nik' => $result->nik,
                'no_kk' => $result->no_kk,
                'nama' => $result->nama,
                'tgl_lahir' => $result->tgl_lahir,
                'tempat_lahir' => $result->tempat_lahir,
                'jenis_kelamin' => $result->jenis_kelamin,
                'alamat' => $result->alamat,
                'rt_rw' => $result->rt_rw,
                'kelurahan' => $result->kelurahan,
                'kecamatan' => $result->kecamatan,
                'kabupaten' => $result->kabupaten,
                'provinsi' => $result->provinsi,
                'raw_text' => $result->raw_text,
                'confidence_scores' => $result->confidence_scores,
                'overall_confidence' => $result->overall_confidence,
                'ocr_status' => $result->ocr_status,
                'validation' => $validation ? [
                    'validation_status' => $validation->validation_status,
                    'overall_match_score' => $validation->overall_match_score,
                    'nik_match_score' => $validation->nik_match_score,
                    'nama_match_score' => $validation->nama_match_score,
                    'alamat_match_score' => $validation->alamat_match_score,
                ] : null,
                'processing_time_ms' => $result->processing_time_ms,
                'engine_version' => $result->engine_version,
                'created_at' => $result->created_at?->toIso8601String(),
                'updated_at' => $result->updated_at?->toIso8601String(),
            ];

            // CSV data
            fputcsv($csvHandle, [
                $result->id,
                $result->document_id,
                $result->case_id,
                $result->nik,
                $result->no_kk,
                $result->nama,
                $result->tgl_lahir,
                $result->tempat_lahir,
                $result->jenis_kelamin,
                $result->alamat,
                $result->rt_rw,
                $result->kelurahan,
                $result->kecamatan,
                $result->kabupaten,
                $result->provinsi,
                $result->ocr_status,
                $result->overall_confidence,
                $result->is_validated,
                $validation?->validation_status,
                $validation?->overall_match_score,
                $result->processing_time_ms,
                $result->engine_version,
                $result->created_at,
            ]);

            $bar->advance();
        }

        fclose($csvHandle);
        $bar->finish();

        // Save JSON
        file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("\n\n");

        // ===================
        // STATISTICS
        // ===================
        $this->info("╔════════════════════════════════════════════════════════════════╗");
        $this->info("║  Backup Complete!                                            ║");
        $this->info("╠════════════════════════════════════════════════════════════════╣");
        $this->info("║  Total Records: " . str_pad($total, 47) . "║");
        $this->info("║  JSON File:    " . str_pad(basename($jsonFile), 47) . "║");
        $this->info("║  CSV File:     " . str_pad(basename($csvFile), 47) . "║");
        $this->info("║  Location:     " . str_pad($backupDir, 47) . "║");
        $this->info("╠════════════════════════════════════════════════════════════════╣");

        $stats = $jsonData['statistics'];
        $this->info("║  Statistics:");
        $this->info("║    - Success:  " . str_pad($stats['success'], 47) . "║");
        $this->info("║    - Partial:  " . str_pad($stats['partial'], 47) . "║");
        $this->info("║    - Failed:   " . str_pad($stats['failed'], 47) . "║");
        $this->info("║    - Avg Conf: " . str_pad($stats['avg_confidence'] . '%', 47) . "║");
        $this->info("╚════════════════════════════════════════════════════════════════╝\n");

        // File sizes
        $jsonSize = filesize($jsonFile);
        $csvSize = filesize($csvFile);
        $this->line("File sizes:");
        $this->line("  JSON: " . $this->formatBytes($jsonSize));
        $this->line("  CSV:  " . $this->formatBytes($csvSize));
        $this->line("");

        // Create latest symlinks
        $latestJson = dirname($jsonFile) . '/ocr_results_latest.json';
        $latestCsv = dirname($jsonFile) . '/ocr_results_latest.csv';

        copy($jsonFile, $latestJson);
        copy($csvFile, $latestCsv);

        $this->info("Latest backup symlinks created:");
        $this->line("  {$latestJson}");
        $this->line("  {$latestCsv}");

        return 0;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
