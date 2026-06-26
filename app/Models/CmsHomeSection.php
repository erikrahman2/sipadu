<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsHomeSection extends Model
{
    protected $fillable = [
        'section_key',
        'title',
        'subtitle',
        'content',
        'image_path',
        'cta_label',
        'cta_url',
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
