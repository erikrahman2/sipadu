<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Layan extends Model
{
    use HasFactory;

    protected $table = 'layans';

    protected $fillable = [
        'nama',
        'kategori',
        'deskripsi',
        'icon',
        'urutan',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
