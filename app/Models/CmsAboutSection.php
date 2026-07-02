<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsAboutSection extends Model
{
    protected $fillable = [
        'content_type',
        'title',
        'subtitle',
        'content',
        'image_path',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'display_order' => 'integer',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}
