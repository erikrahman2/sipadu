<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_id', 'uploaded_by', 'original_name', 'stored_name',
        'disk', 'path', 'mime_type', 'size_bytes', 'document_type',
        'status', 'checksum',
    ];

    // ── Boot: Neo4j Sync Events ──────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        // Document created → sync to Neo4j
        static::created(function ($model) {
            IntegrationQueue::create([
                'aggregate_type' => 'Document',
                'aggregate_id'   => $model->id,
                'event_type'     => 'created',
                'payload'        => [
                    'case_id'       => $model->case_id,
                    'document_type' => $model->document_type,
                    'status'        => $model->status,
                ],
                'available_at'   => now(),
            ]);
        });

        // Document updated → sync changes to Neo4j
        static::updated(function ($model) {
            IntegrationQueue::create([
                'aggregate_type' => 'Document',
                'aggregate_id'   => $model->id,
                'event_type'     => 'updated',
                'payload'        => [
                    'case_id'       => $model->case_id,
                    'document_type' => $model->document_type,
                    'status'        => $model->status,
                ],
                'available_at'   => now(),
            ]);
        });

        // Document deleted → remove from Neo4j
        static::deleting(function ($model) {
            if ($model->forceDeleting) {
                IntegrationQueue::create([
                    'aggregate_type' => 'Document',
                    'aggregate_id'   => $model->id,
                    'event_type'     => 'deleted',
                    'payload'        => [
                        'case_id'       => $model->case_id,
                        'document_type' => $model->document_type,
                    ],
                    'available_at'   => now(),
                ]);
            }
        });
    }

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function ocrResult()
    {
        return $this->hasOne(OcrResult::class);
    }

    public function ocrJob()
    {
        return $this->hasOne(OcrJob::class);
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'PROCESSED');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /** Ukuran file dalam format manusiawi. */
    public function humanFileSize(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
}
