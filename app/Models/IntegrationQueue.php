<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationQueue extends Model
{
    protected $table = 'integration_queue';

    protected $fillable = [
        'aggregate_type', 'aggregate_id', 'event_type',
        'payload', 'status', 'attempts', 'max_attempts',
        'backoff_seconds', 'error', 'available_at', 'processed_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'available_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING')
                     ->where('available_at', '<=', now());
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'FAILED')
                     ->whereColumn('attempts', '<', 'max_attempts');
    }

    public function markProcessing(): void
    {
        $this->update(['status' => 'PROCESSING', 'attempts' => $this->attempts + 1]);
    }

    public function markSuccess(): void
    {
        $this->update(['status' => 'SUCCESS', 'processed_at' => now()]);
    }

    public function markFailed(string $error): void
    {
        $backoff = $this->backoff_seconds * (2 ** ($this->attempts - 1));
        $this->update([
            'status'      => 'FAILED',
            'error'       => $error,
            'available_at' => now()->addSeconds($backoff),
        ]);
    }
}
