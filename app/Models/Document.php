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
}
