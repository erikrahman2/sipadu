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

    public $tries = 3;
    public $timeout = 30;

    public function __construct(
        public PublicSubmission $submission
    ) {
    }

    public function handle(WhatsAppService $wa): void
    {
        $trackingUrl = route('public.tracking.token', $this->submission->tracking_token);
        $message     = $wa->templatePengajuanDiterima(
            $this->submission->petitioner_name,
            $this->submission->tracking_token,
            $trackingUrl
        );

        $result = $wa->send($this->submission->phone_wa, $message);

        $this->submission->update([
            'wa_sent_at'   => now(),
            'wa_message_id'=> $result['message_id'],
            'wa_status'    => $result['success'] ? 'sent' : 'failed',
            'wa_error'     => $result['error'],
        ]);

        if (! $result['success']) {
            Log::warning('[PublicSubmission] WA gagal dikirim ke ' . $this->submission->phone_wa, [
                'submission_id' => $this->submission->id,
                'error'         => $result['error'],
            ]);
        }
    }
}
