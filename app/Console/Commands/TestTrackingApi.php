<?php

namespace App\Console\Commands;

use App\Models\PublicSubmission;
use App\Services\PublicSubmissionService;
use Illuminate\Console\Command;

class TestTrackingApi extends Command
{
    protected $signature = 'test:tracking-api {token}';
    protected $description = 'Simulate API response for tracking endpoint';

    public function handle(PublicSubmissionService $service)
    {
        $token = $this->argument('token');
        
        $submission = $service->findByToken($token);
        if (!$submission) {
            $this->error("Submission not found: {$token}");
            return 1;
        }

        $response = array_merge(['type' => 'public_submission'], $service->formatTracking($submission));
        
        $this->info('=== API Response ===');
        $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        return 0;
    }
}
