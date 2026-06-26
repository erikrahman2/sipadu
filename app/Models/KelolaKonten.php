<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelolaKonten extends Model
{
    protected $table = 'kelola_konten';

    protected $fillable = [
        'slot_name',
        'title',
        'content',
        'image_path',
        'is_active',
        'display_order',
        'updated_by',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('created_at');
    }

    /**
     * Quick accessor: get all kontens grouped by slot_name
     */
    public static function getBySlot(string $slot)
    {
        $row = static::where('slot_name', $slot)->first();
        return $row ? $row->content : null;
    }
}
