<?php

namespace App\Notifications;

use App\Models\CaseModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly CaseModel $case) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $stateLabel = config("workflow.states.{$this->case->status}", $this->case->status);

        return (new MailMessage)
            ->subject("[PA Disdukcapil] Status Kasus Diperbarui: {$stateLabel}")
            ->greeting("Yth. {$notifiable->name},")
            ->line("Status permohonan dokumen kependudukan Anda telah diperbarui.")
            ->line("**No. Kasus:** {$this->case->case_number}")
            ->line("**Status Baru:** {$stateLabel}")
            ->line("**Tanggal:** " . now()->format('d/m/Y H:i'))
            ->action('Pantau Status', route('dashboard.tracking') . '?token=' . $this->case->tracking_token)
            ->line("Token Tracking: `{$this->case->tracking_token}`")
            ->salutation('Hormat kami,')
            ->salutation('Tim Sistem PA – Disdukcapil');
    }
}
