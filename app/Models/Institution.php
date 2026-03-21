<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'type', 'address', 'phone', 'email', 'active',
    ];

    protected $casts = ['active' => 'boolean'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function cases()
    {
        return $this->hasMany(CaseModel::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
