<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsHomeSection extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'content',
        'image_path',
        'cta_label',
        'cta_url',
        'secondary_cta_url',
        'display_order',
        'is_active',
        'content_type',
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
