<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    protected $fillable = [
        'name',
        'url',
        'source_type',
        'headers',
        'title_selector',
        'link_selector',
        'date_selector',
        'excerpt_selector',
        'content_selector',
        'category_selector',
        'sync_interval_minutes',
        'is_active',
        'last_synced_at',
        'last_fetched_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'last_fetched_at' => 'datetime',
    ];

    /**
     * Items fetched from this feed.
     */
    public function items(): HasMany
    {
        return $this->hasMany(FeedItem::class);
    }

    /**
     * Touch the last_synced_at timestamp.
     */
    public function touchLastSync(): void
    {
        $this->update(['last_synced_at' => now()]);
    }

    /**
     * Touch the last_fetched_at timestamp.
     */
    public function touchLastFetched(): void
    {
        $this->update(['last_fetched_at' => now()]);
    }
}
