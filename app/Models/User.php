<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name', 'nik', 'email', 'phone', 'password',
        'status', 'institution_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // ── Boot: Neo4j Sync Events ──────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        // User created → sync to Neo4j
        static::created(function ($model) {
            IntegrationQueue::create([
                'aggregate_type' => 'User',
                'aggregate_id'   => $model->id,
                'event_type'     => 'created',
                'payload'        => [
                    'name'            => $model->name,
                    'email'           => $model->email,
                    'institution_id'  => $model->institution_id,
                ],
                'available_at'   => now(),
            ]);
        });

        // User updated → sync changes to Neo4j
        static::updated(function ($model) {
            IntegrationQueue::create([
                'aggregate_type' => 'User',
                'aggregate_id'   => $model->id,
                'event_type'     => 'updated',
                'payload'        => [
                    'name'           => $model->name,
                    'email'          => $model->email,
                    'institution_id' => $model->institution_id,
                ],
                'available_at'   => now(),
            ]);
        });

        // User deleted → remove from Neo4j
        static::deleting(function ($model) {
            if ($model->forceDeleting) {
                IntegrationQueue::create([
                    'aggregate_type' => 'User',
                    'aggregate_id'   => $model->id,
                    'event_type'     => 'deleted',
                    'payload'        => ['email' => $model->email],
                    'available_at'   => now(),
                ]);
            }
        });
    }

    // ── JWT ──────────────────────────────────────────────────────────────────

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'roles'          => $this->getRoleNames(),
            'institution_id' => $this->institution_id,
        ];
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function cases()
    {
        return $this->hasMany(CaseModel::class, 'submitter_id');
    }

    public function assignedPaCases()
    {
        return $this->hasMany(CaseModel::class, 'assigned_pa_user_id');
    }

    public function assignedDisdukcapilCases()
    {
        return $this->hasMany(CaseModel::class, 'assigned_disdukcapil_user_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
