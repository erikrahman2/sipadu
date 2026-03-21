<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PublicSubmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tracking_token',
        'nik',
        'petitioner_name',
        'nama_lengkap',        // ← NEW
        'tempat_lahir',        // ← NEW
        'tanggal_lahir',       // ← NEW
        'alamat',              // ← NEW
        'rt_rw',               // ← NEW
        'kelurahan',           // ← NEW
        'kecamatan',           // ← NEW
        'no_kk',               // ← NEW
        'phone_wa',
        'respondent_name',
        'respondent_nik',
        'divorce_date',
        'verdict_number',
        'notes',
        'status',
        'wa_sent_at',
        'wa_message_id',
        'wa_status',
        'wa_error',
        'case_id',
        'replaced_by',
        'is_active',
        'processed_by',
        'processed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'divorce_date'   => 'date',
        'tanggal_lahir'  => 'date',  // ← NEW
        'wa_sent_at'     => 'datetime',
        'processed_at'   => 'datetime',
        'is_active'      => 'boolean',
    ];

    // ── Limits ────────────────────────────────────────────────────────────────

    /** Maksimal pengajuan per NIK dalam rentang hari berikut. */
    public const MAX_SUBMISSIONS = 3;
    public const LIMIT_DAYS      = 7;  // 1 minggu (diubah dari 15 hari)

    // Status yang dianggap sudah sampai ke Disdukcapil
    public const DISDUKCAPIL_STATUSES = [
        'APPROVED',         // Sudah disetujui PA, case dibuat
        'COMPLETED',        // Sudah selesai penuh
    ];

    // Status yang membekukan NIK (sedang dalam proses PA/Disdukcapil)
    // NIK tidak bisa input baru selama ada data dengan status ini
    public const FROZEN_STATUSES = [
        'REVIEWING',        // Sedang ditinjau petugas
        'WAITING_OCR',      // Menunggu hasil OCR
        'APPROVED',         // Disetujui, case dibuat, sedang proses
    ];

    // Status Case yang membekukan NIK dari PublicSubmission
    public const CASE_FROZEN_STATUSES = [
        'PA_REVIEW',                // Sedang direview PA Management
        'DISDUKCAPIL_VALIDATION',   // Sedang validasi Disdukcapil
    ];

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->tracking_token)) {
                $model->tracking_token = 'PUB-' . strtoupper(Str::random(20));
            }
        });
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Hitung pengajuan aktif (non-rejected) dari NIK dalam N hari terakhir.
     */
    public static function countActiveByNik(string $nik): int
    {
        return static::withoutTrashed()
            ->where('nik', $nik)
            ->where('is_active', true)
            ->where('status', '!=', 'REJECTED')
            ->where('created_at', '>=', now()->subDays(static::LIMIT_DAYS))
            ->count();
    }

    /**
     * Scope: hanya data aktif/terbaru (belum digantikan).
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: data yang sudah sampai ke Disdukcapil.
     */
    public function scopeReachedDisdukcapil($query)
    {
        return $query->whereIn('status', static::DISDUKCAPIL_STATUSES);
    }

    /**
     * Replace data lama dengan data baru untuk NIK yang sama.
     * Data lama akan di-soft delete dan ditandai replaced_by.
     * 
     * @param string $nik NIK pemohon
     * @param int $newSubmissionId ID pengajuan baru yang menggantikan
     * @return int Jumlah data lama yang digantikan
     */
    public static function replaceOldSubmissions(string $nik, int $newSubmissionId): int
    {
        $oldSubmissions = static::withoutTrashed()
            ->where('nik', $nik)
            ->where('id', '!=', $newSubmissionId)
            ->where('is_active', true)
            ->get();

        foreach ($oldSubmissions as $old) {
            $old->update([
                'is_active' => false,
                'replaced_by' => $newSubmissionId,
            ]);
            $old->delete();  // soft delete
        }

        return $oldSubmissions->count();
    }

    /**
     * Validasi: Cek apakah pasangan NIK (petitioner + respondent) sudah ada
     * di data yang sudah sampai Disdukcapil.
     * 
     * @param string $petitionerNik NIK pemohon
     * @param string $respondentNik NIK pasangan
     * @return bool TRUE jika pasangan sudah ada di Disdukcapil (tidak boleh daftar lagi)
     */
    public static function hasCoupleInDisdukcapil(string $petitionerNik, string $respondentNik): bool
    {
        // Cek apakah pasangan ini (dalam urutan apapun) sudah ada di data Disdukcapil
        return static::withoutTrashed()
            ->active()
            ->reachedDisdukcapil()
            ->where(function ($query) use ($petitionerNik, $respondentNik) {
                // Cek kombinasi 1: petitioner=A, respondent=B
                $query->where(function ($q) use ($petitionerNik, $respondentNik) {
                    $q->where('nik', $petitionerNik)
                      ->where('respondent_nik', $respondentNik);
                })
                // Cek kombinasi 2: petitioner=B, respondent=A (kebalikan)
                ->orWhere(function ($q) use ($petitionerNik, $respondentNik) {
                    $q->where('nik', $respondentNik)
                      ->where('respondent_nik', $petitionerNik);
                });
            })
            ->exists();
    }

    /**
     * Validasi: Cek apakah NIK pemohon sama dengan NIK pasangan.
     * 
     * @param string $petitionerNik
     * @param string|null $respondentNik
     * @return bool TRUE jika sama (invalid)
     */
    public static function isSameNik(string $petitionerNik, ?string $respondentNik): bool
    {
        if (empty($respondentNik)) {
            return false;
        }
        return $petitionerNik === $respondentNik;
    }

    /**
     * Validasi: Cek apakah NIK sedang dibekukan (frozen) karena ada data
     * yang sedang dalam proses PA Management atau Disdukcapil.
     * 
     * @param string $nik NIK pemohon
     * @return bool TRUE jika NIK dibekukan (tidak bisa input baru)
     */
    public static function isNikFrozen(string $nik): bool
    {
        // Cek 1: Apakah ada PublicSubmission dengan NIK ini yang statusnya frozen?
        $hasFrozenSubmission = static::withoutTrashed()
            ->where('nik', $nik)
            ->where('is_active', true)
            ->whereIn('status', static::FROZEN_STATUSES)
            ->exists();

        if ($hasFrozenSubmission) {
            return true;
        }

        // Cek 2: Apakah ada Case yang dibuat dari NIK ini yang sedang proses PA/Disdukcapil?
        $hasFrozenCase = \App\Models\CaseModel::withoutTrashed()
            ->where('petitioner_nik', $nik)
            ->whereIn('status', static::CASE_FROZEN_STATUSES)
            ->exists();

        return $hasFrozenCase;
    }

    /**
     * Dapatkan data yang menyebabkan NIK dibekukan.
     * 
     * @param string $nik
     * @return array{type: string, status: string, token: string}|null
     */
    public static function getFrozenReason(string $nik): ?array
    {
        // Cek PublicSubmission
        $submission = static::withoutTrashed()
            ->where('nik', $nik)
            ->where('is_active', true)
            ->whereIn('status', static::FROZEN_STATUSES)
            ->first(['status', 'tracking_token']);

        if ($submission) {
            return [
                'type' => 'public_submission',
                'status' => $submission->status,
                'token' => $submission->tracking_token,
            ];
        }

        // Cek Case
        $case = \App\Models\CaseModel::withoutTrashed()
            ->where('petitioner_nik', $nik)
            ->whereIn('status', static::CASE_FROZEN_STATUSES)
            ->first(['status', 'tracking_token', 'case_number']);

        if ($case) {
            return [
                'type' => 'case',
                'status' => $case->status,
                'token' => $case->tracking_token,
                'case_number' => $case->case_number,
            ];
        }

        return null;
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function documents()
    {
        return $this->hasMany(PublicSubmissionDocument::class);
    }

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Label status dalam Bahasa Indonesia. */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'PENDING'      => 'Menunggu Verifikasi',
            'REVIEWING'    => 'Sedang Ditinjau',
            'WAITING_OCR'  => 'Proses Verifikasi Dokumen',
            'APPROVED'     => 'Disetujui — Kasus Dibuat',
            'REJECTED'     => 'Ditolak',
            'COMPLETED'    => 'Selesai',
            default        => $this->status,
        };
    }

    /** Warna badge Tailwind untuk status. */
    public function statusColor(): string
    {
        return match ($this->status) {
            'PENDING'      => 'yellow',
            'REVIEWING'    => 'blue',
            'WAITING_OCR'  => 'indigo',
            'APPROVED'     => 'green',
            'REJECTED'     => 'red',
            'COMPLETED'    => 'emerald',
            default        => 'gray',
        };
    }

    /**
     * Format nomor WA ke format internasional (62xxx).
     */
    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);  // hapus non-digit
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }
        return $phone;
    }
}
