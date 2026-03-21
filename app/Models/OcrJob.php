<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OcrJob extends Model
{
    protected $fillable = [
        'document_id', 'status', 'attempts', 'max_attempts',
        'error_message', 'result_payload', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'result_payload' => 'array',
        'started_at'     => 'datetime',
        'finished_at'    => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
