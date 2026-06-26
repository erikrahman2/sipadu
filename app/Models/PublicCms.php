<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicCms extends Model
{
    protected $table = 'public_cms';

    protected $fillable = [
        'section_key',
        'title',
        'content',
        'image_path',
        'subtitle',
        'cta_label',
        'cta_url',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('section_key', $key);
    }

    public static function getByKey(string $key, ?Model $fallback = null): ?Model
    {
        $record = static::where('section_key', $key)->first();
        return $record ?: $fallback;
    }
}
