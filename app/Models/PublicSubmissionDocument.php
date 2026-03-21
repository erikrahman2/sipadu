<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicSubmissionDocument extends Model
{
    protected $fillable = [
        'public_submission_id',
        'document_type',
        'original_filename',
        'stored_path',
        'file_size',
        'mime_type',
    ];

    /** Label untuk jenis dokumen. */
    public static array $typeLabels = [
        'KTP'         => 'KTP (Kartu Tanda Penduduk)',
        'KK'          => 'Kartu Keluarga (KK)',
        'AKTA_CERAI'  => 'Akta Perceraian',
        'PUTUSAN_PA'  => 'Putusan Pengadilan Agama',
        'SURAT_NIKAH' => 'Buku Nikah',
        'FOTO_DIRI'   => 'Foto Diri (Selfie KTP)',
        'LAINNYA'     => 'Dokumen Lainnya',
    ];

    public function submission()
    {
        return $this->belongsTo(PublicSubmission::class, 'public_submission_id');
    }

    /** Ukuran file dalam format manusiawi. */
    public function humanFileSize(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
}
