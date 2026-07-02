<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CmsBlogPost extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'category_name',
        'excerpt',
        'content',
        'cover_image',
        'author_name',
        'status',
        'published_at',
        'source_url',
        'source_feed_id',
        'synced_at',
        'author_id',
        'updated_by',
    ];

    protected $casts = [
        'status'       => 'string',
        'published_at' => 'datetime',
        'synced_at'    => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->slug) {
                $model->slug = static::uniqueSlug($model->title);
            }
        });
        static::updating(function ($model) {
            if ($model->isDirty('title') && !$model->isDirty('slug')) {
                $model->slug = static::uniqueSlug($model->title, $model->id);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;
        $query = static::query()->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        while ($query->exists()) {
            $slug = $originalSlug . '-' . ++$count;
            $query = static::query()->where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
        }
        return $slug;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'PUBLISHED')
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('published_at');
    }

    public function scopeDraft($query)       { return $query->where('status', 'DRAFT'); }
    public function scopeArchived($query)    { return $query->where('status', 'ARCHIVED'); }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'PUBLISHED' && $this->published_at && $this->published_at->lte(now());
    }
}