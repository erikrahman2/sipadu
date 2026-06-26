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

    // ── Boot: Neo4j Sync Events ──────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        // Institution created → sync to Neo4j
        static::created(function ($model) {
            IntegrationQueue::create([
                'aggregate_type' => 'Institution',
                'aggregate_id'   => $model->id,
                'event_type'     => 'created',
                'payload'        => [
                    'code' => $model->code,
                    'name' => $model->name,
                    'type' => $model->type,
                ],
                'available_at'   => now(),
            ]);
        });

        // Institution updated → sync changes to Neo4j
        static::updated(function ($model) {
            IntegrationQueue::create([
                'aggregate_type' => 'Institution',
                'aggregate_id'   => $model->id,
                'event_type'     => 'updated',
                'payload'        => [
                    'code'   => $model->code,
                    'name'   => $model->name,
                    'type'   => $model->type,
                    'active' => $model->active,
                ],
                'available_at'   => now(),
            ]);
        });

        // Institution deleted → remove from Neo4j
        static::deleted(function ($model) {
            IntegrationQueue::create([
                'aggregate_type' => 'Institution',
                'aggregate_id'   => $model->id,
                'event_type'     => 'deleted',
                'payload'        => ['code' => $model->code],
                'available_at'   => now(),
            ]);
        });
    }

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
