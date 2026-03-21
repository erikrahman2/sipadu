<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GraphSyncLog extends Model
{
    protected $table = 'graph_sync_log';

    protected $fillable = [
        'queue_id', 'operation', 'label_or_rel',
        'neo4j_id', 'success', 'error', 'duration_ms',
    ];

    protected $casts = ['success' => 'boolean'];

    public function queueItem()
    {
        return $this->belongsTo(IntegrationQueue::class, 'queue_id');
    }
}
