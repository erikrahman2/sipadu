<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    protected $table = 'access_logs';

    protected $fillable = [
        'user_id', 'ip_address', 'method', 'path',
        'status_code', 'response_time_ms', 'user_agent', 'request_headers',
    ];

    protected $casts = ['request_headers' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
