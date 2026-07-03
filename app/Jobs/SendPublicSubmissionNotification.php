<?php

namespace App\Jobs;

use App\Models\PublicSubmission;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPublicSubmissionNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public int $maxExceptions = 2;

    public function __construct(
        public PublicSubmission $submission
    ) {
    }

    public function handle(WhatsAppService $wa): void
    {
        try {
            $trackingUrl = route('public.tracking.token', $this->submission->tracking_token);
            $message     = $wa->templatePengajuanDiterima(
                $this->submission->petitioner_name,
                $this->submission->tracking_token,
                $trackingUrl
            );

            $result = $wa->send($this->submission->phone_wa, $message);

            $this->submission->updateQuietly([
                'wa_sent_at'    => now(),
                'wa_message_id' => $result['message_id'] ?? null,
                'wa_status'     => $result['success'] ? 'sent' : 'failed',
                'wa_error'      => $result['error'] ?? null,
            ]);

            if (! $result['success']) {
                Log::warning('[PublicSubmission] WA gagal dikirim ke ' . $this->submission->phone_wa, [
                    'submission_id' => $this->submission->id,
                    'error'         => $result['error'],
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[PublicSubmission] WA notification failed', [
                'submission_id' => $this->submission->id,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[PublicSubmission] WA notification permanently failed', [
            'submission_id' => $this->submission->id,
            'error'         => $exception->getMessage(),
        ]);

        $this->submission->updateQuietly([
            'wa_status' => 'failed',
            'wa_error'  => 'Notification failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
        ]);
    }
}
