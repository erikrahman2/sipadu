<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CaseModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cases';

    protected $fillable = [
        'case_number', 'tracking_token', 'submitter_id',
        'petitioner_nik', 'petitioner_name', 'petitioner_phone',
        'petitioner_alamat', 'petitioner_rt_rw', 'petitioner_kelurahan', 'petitioner_kecamatan',
        'institution_id', 'spouse_nik', 'spouse_name',
        'spouse_alamat', 'spouse_rt_rw', 'spouse_kelurahan', 'spouse_kecamatan',
        'divorce_date', 'verdict_number', 'status', 'notes',
        'assigned_pa_user_id', 'assigned_disdukcapil_user_id',
        'submitted_at', 'completed_at',
    ];

    protected $casts = [
        'divorce_date'  => 'date',
        'submitted_at'  => 'datetime',
        'completed_at'  => 'datetime',
    ];

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->tracking_token)) {
                $prefix = config('workflow.tracking_token_prefix', 'TRK');
                $model->tracking_token = $prefix . strtoupper(Str::random(
                    config('workflow.tracking_token_length', 32)
                ));
            }
            if (empty($model->case_number)) {
                $model->case_number = 'CASE-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
            }
        });
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitter_id');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function assignedPaUser()
    {
        return $this->belongsTo(User::class, 'assigned_pa_user_id');
    }

    public function assignedDisdukcapilUser()
    {
        return $this->belongsTo(User::class, 'assigned_disdukcapil_user_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'case_id');
    }

    public function transitions()
    {
        return $this->hasMany(CaseTransition::class, 'case_id')->orderBy('created_at');
    }

    public function latestTransition()
    {
        return $this->hasOne(CaseTransition::class, 'case_id')->latestOfMany();
    }

    public function ocrResults()
    {
        return $this->hasMany(OcrResult::class, 'case_id');
    }

    public function ocrValidations()
    {
        return $this->hasMany(OcrValidation::class, 'case_id');
    }

    public function publicSubmission()
    {
        return $this->hasOne(PublicSubmission::class, 'case_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function canTransitionTo(string $newState): bool
    {
        $allowed = config("workflow.transitions.{$this->status}", []);
        return in_array($newState, $allowed, true);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['COMPLETED', 'ARCHIVED']);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('institution_id', $user->institution_id);
    }

    // ── Data Replacement Logic ────────────────────────────────────────────────

    /**
     * Replace old Cases with same petitioner_nik or spouse_nik.
     * Data lama otomatis di-soft delete agar hanya data terbaru yang aktif.
     * 
     * @param string|null $petitionerNik NIK pemohon
     * @param string|null $spouseNik NIK pasangan
     * @param int $newCaseId ID case baru yang menggantikan
     * @return int Jumlah case lama yang digantikan
     */
    public static function replaceOldCases(?string $petitionerNik, ?string $spouseNik, int $newCaseId): int
    {
        $count = 0;

        // Replace berdasarkan petitioner_nik
        if ($petitionerNik) {
            $oldByPetitioner = static::withoutTrashed()
                ->where('petitioner_nik', $petitionerNik)
                ->where('id', '!=', $newCaseId)
                ->where('status', 'DRAFT') // Hanya replace yang masih DRAFT
                ->get();

            foreach ($oldByPetitioner as $old) {
                $old->delete();  // soft delete
                $count++;
            }
        }

        // Replace berdasarkan spouse_nik
        if ($spouseNik) {
            $oldBySpouse = static::withoutTrashed()
                ->where('spouse_nik', $spouseNik)
                ->where('id', '!=', $newCaseId)
                ->where('status', 'DRAFT') // Hanya replace yang masih DRAFT
                ->get();

            foreach ($oldBySpouse as $old) {
                // Skip if already deleted in previous loop
                if (!$old->trashed()) {
                    $old->delete();  // soft delete
                    $count++;
                }
            }
        }

        return $count;
    }
}
