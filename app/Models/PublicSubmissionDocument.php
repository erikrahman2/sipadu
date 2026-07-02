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
        'KTP_SUAMI'        => 'KTP Suami',
        'KTP_ISTRI'        => 'KTP Istri',
        'KTP'              => 'KTP (Legacy)',
        'KK'               => 'Kartu Keluarga (KK)',
        'AKTA_KAWIN'       => 'Akta Kawin',
        'PUTUSAN_PA'       => 'Berkas Putusan Cerai',
        'AKTA_NIKAH'       => 'Buku Nikah',
        'AKTA_CERAI'       => 'Akta Perceraian',
        'AKTA_KEMATIAN'    => 'Akta Kematian',
        'SURAT_KETERANGAN_AHLI_WARIS' => 'Surat Keterangan Ahli Waris',
        'SURAT_PINDAH'     => 'Surat Pindah',
        'SURAT_KETERANGAN_GHAIB' => 'Surat Keterangan Ghaib',
        'AKTA_KELAHIRAN_ANAK' => 'Akta Kelahiran Anak',
        'SURAT_PENGANTAR'  => 'Surat Pengantar',
        'OTHER'            => 'Dokumen Lainnya',
        'LAINNYA'          => 'Dokumen Lainnya (Legacy)',
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
