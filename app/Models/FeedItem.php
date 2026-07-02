<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedItem extends Model
{
    protected $fillable = [
        'feed_id',
        'source_url',
        'title',
        'category',
        'excerpt',
        'content_html',
        'source_identifier',
        'original_published_at',
        'imported_at',
    ];

    protected $casts = [
        'original_published_at' => 'datetime',
        'imported_at' => 'datetime',
    ];

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }
}
