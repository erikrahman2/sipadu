<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrValidation extends Model
{
    protected $fillable = [
        'ocr_result_id',
        'case_id',
        'public_submission_id',
        'document_id',
        
        // Input snapshot
        'input_nik',
        'input_nama',
        'input_tempat_lahir',
        'input_tgl_lahir',
        'input_alamat',
        'input_rt_rw',
        'input_kelurahan',
        'input_kecamatan',
        'input_no_kk',
        
        // OCR snapshot
        'ocr_nik',
        'ocr_nama',
        'ocr_tempat_lahir',
        'ocr_tgl_lahir',
        'ocr_alamat',
        'ocr_rt_rw',
        'ocr_kelurahan',
        'ocr_kecamatan',
        'ocr_no_kk',
        
        // Results
        'comparison_results',
        'overall_match_score',
        'fields_matched',
        'fields_total',
        'validation_status',
        'is_reviewed',
        'review_action',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'comparison_results' => 'array',
        'overall_match_score' => 'decimal:2',
        'input_tgl_lahir' => 'date',
        'is_reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────────────────

    public function ocrResult(): BelongsTo
    {
        return $this->belongsTo(OcrResult::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function publicSubmission(): BelongsTo
    {
        return $this->belongsTo(PublicSubmission::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper Methods
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get badge class for validation status
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->validation_status) {
            'MATCH' => 'success',
            'PARTIAL_MATCH' => 'warning',
            'MISMATCH' => 'danger',
            'MANUAL_REVIEW' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match($this->validation_status) {
            'MATCH' => 'Match',
            'PARTIAL_MATCH' => 'Partial Match',
            'MISMATCH' => 'Mismatch',
            'MANUAL_REVIEW' => 'Manual Review',
            default => 'Unknown',
        };
    }

    /**
     * Get list of mismatched fields
     */
    public function getMismatchedFields(): array
    {
        return collect($this->comparison_results ?? [])
            ->filter(fn($comparison) => !($comparison['match'] ?? false))
            ->keys()
            ->toArray();
    }

    /**
     * Check if this validation needs review
     */
    public function needsReview(): bool
    {
        return !$this->is_reviewed && in_array($this->validation_status, ['PARTIAL_MATCH', 'MANUAL_REVIEW', 'MISMATCH']);
    }

    /**
     * Check if NIK matches
     */
    public function isNikMatched(): bool
    {
        $nikComparison = $this->comparison_results['nik'] ?? null;
        return $nikComparison ? ($nikComparison['match'] ?? false) : false;
    }

    /**
     * Get overall match percentage (formatted)
     */
    public function getMatchPercentageAttribute(): string
    {
        return number_format($this->overall_match_score, 1) . '%';
    }

    /**
     * Get OCR timing information
     */
    public function getOcrTimingInfo(): array
    {
        $ocrResult = $this->ocrResult;
        $document = $this->document;

        if (!$ocrResult || !$document) {
            return [
                'started_at' => null,
                'completed_at' => null,
                'duration_seconds' => null,
                'duration_human' => 'N/A',
            ];
        }

        $startedAt = $document->created_at;
        $completedAt = $ocrResult->created_at;
        $duration = $completedAt->diffInSeconds($startedAt);

        return [
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'duration_seconds' => $duration,
            'duration_human' => $this->formatDuration($duration),
        ];
    }

    /**
     * Format duration in human-readable format
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} detik";
        }

        $minutes = intval($seconds / 60);
        $secs = $seconds % 60;

        if ($minutes < 2) {
            return "{$minutes} menit {$secs} detik";
        }

        return "{$minutes} menit";
    }
}
