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
        'public_submission_id', 'source_type',
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

        // ── Neo4j Sync Events ─────────────────────────────────────────────────

        // New case created → sync to Neo4j
        static::created(function ($model) {
            IntegrationQueue::create([
                'aggregate_type' => 'Case',
                'aggregate_id'   => $model->id,
                'event_type'     => 'created',
                'payload'        => [
                    'case_number'    => $model->case_number,
                    'tracking_token' => $model->tracking_token,
                    'status'         => $model->status,
                    'institution_id' => $model->institution_id,
                    'submitter_id'   => $model->submitter_id,
                ],
                'available_at'   => now(),
            ]);
        });

        // Case updated → sync changes to Neo4j
        static::updated(function ($model) {
            IntegrationQueue::create([
                'aggregate_type' => 'Case',
                'aggregate_id'   => $model->id,
                'event_type'     => 'updated',
                'payload'        => [
                    'case_number'                  => $model->case_number,
                    'status'                       => $model->status,
                    'institution_id'               => $model->institution_id,
                    'assigned_pa_user_id'          => $model->assigned_pa_user_id,
                    'assigned_disdukcapil_user_id' => $model->assigned_disdukcapil_user_id,
                ],
                'available_at'   => now(),
            ]);
        });

        // Case deleted/soft deleted → remove from Neo4j
        static::deleting(function ($model) {
            // Only create delete event if this is a permanent delete, not soft delete
            // For soft deletes, check if the model is actually being hard deleted
            if ($model->forceDeleting || !$model->softDelete()) {
                IntegrationQueue::create([
                    'aggregate_type' => 'Case',
                    'aggregate_id'   => $model->id,
                    'event_type'     => 'deleted',
                    'payload'        => [
                        'case_number' => $model->case_number,
                        'timestamp'   => now()->toIso8601String(),
                    ],
                    'available_at'   => now(),
                ]);
            }
        });

        // Case restored from soft delete → re-sync to Neo4j
        static::restored(function ($model) {
            IntegrationQueue::create([
                'aggregate_type' => 'Case',
                'aggregate_id'   => $model->id,
                'event_type'     => 'restored',
                'payload'        => [
                    'case_number' => $model->case_number,
                    'status'      => $model->status,
                ],
                'available_at'   => now(),
            ]);
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

    /**
     * Get the public submission that generated this case (inverse relationship).
     * Used when a case is created from a public submission.
     */
    public function sourcePublicSubmission()
    {
        return $this->belongsTo(PublicSubmission::class, 'public_submission_id');
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
