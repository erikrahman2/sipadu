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
