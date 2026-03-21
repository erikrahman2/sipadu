<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OcrResult extends Model
{
    protected $fillable = [
        'document_id', 'case_id', 'nik', 'no_kk', 'nama',
        'tgl_lahir', 'tempat_lahir', 'jenis_kelamin', 'alamat',
        'rt_rw', 'kelurahan', 'kecamatan', 'kabupaten', 'provinsi',
        'raw_text', 'confidence_scores', 'overall_confidence',
        'is_validated', 'validation_errors', 'ocr_status',
        'engine_version', 'processing_time_ms',
    ];

    protected $casts = [
        'raw_text'          => 'array',
        'confidence_scores' => 'array',
        'validation_errors' => 'array',
        'is_validated'      => 'boolean',
        'overall_confidence' => 'float',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function validation()
    {
        return $this->hasOne(OcrValidation::class, 'ocr_result_id');
    }

    public function isHighConfidence(float $threshold = 0.85): bool
    {
        return ($this->overall_confidence ?? 0) >= $threshold;
    }

    public function toValidatedArray(): array
    {
        return [
            'nik'    => $this->nik,
            'no_kk'  => $this->no_kk,
            'nama'   => $this->nama,
            'tgl_lahir' => $this->tgl_lahir,
            'confidence' => $this->confidence_scores,
        ];
    }
}
