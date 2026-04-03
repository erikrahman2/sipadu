<?php

namespace App\Console\Commands;

use App\Models\PublicSubmission;
use App\Models\PublicSubmissionDocument;
use Illuminate\Console\Command;

class CheckTrackingToken extends Command
{
    protected $signature = 'check:tracking {token}';
    protected $description = 'Check tracking token and document paths';

    public function handle()
    {
        $token = $this->argument('token');
        $sub = PublicSubmission::where('tracking_token', $token)->with('documents')->first();

        $this->line('=== Tracking Token Check ===');
        $this->line("Token: {$token}");

        if (!$sub) {
            $this->error('Submission NOT FOUND in database');
            return 1;
        }

        $this->info('Found Submission:');
        $this->line("  Tracking Token: {$sub->tracking_token}");
        $this->line("  Status: {$sub->status}");
        $this->line("  Petitioner: {$sub->petitioner_name}");
        $this->line("  Documents Count: {$sub->documents->count()}");
        $this->line("\nDocuments:");

        foreach ($sub->documents as $doc) {
            $url = 'storage/' . $doc->stored_path;
            $filePath = storage_path('app/public/' . $doc->stored_path);
            $fileExists = file_exists($filePath);
            
            $this->line("\n  Type: {$doc->document_type}");
            $this->line("  Label: " . (PublicSubmissionDocument::$typeLabels[$doc->document_type] ?? $doc->document_type));
            $this->line("  Stored Path: {$doc->stored_path}");
            $this->line("  Generated URL: {$url}");
            $this->line("  Full File Path: {$filePath}");
            $status = $fileExists ? 'YES' : 'NO';
            $this->line("  File Exists: {$status}");
            if ($fileExists) {
                $size = filesize($filePath);
                $this->line("  File Size: {$size} bytes");
            }
        }

        return 0;
    }
}
