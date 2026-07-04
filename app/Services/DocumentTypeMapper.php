<?php

namespace App\Services;

/**
 * Centralized mapping between public submission document types and case document types.
 */
class DocumentTypeMapper
{
    /**
     * Map public submission document types → case document types.
     * Key = public submission type, Value = case document type.
     */
    public static function toCaseType(string $publicType): string
    {
        return match ($publicType) {
            // Identical types
            'KTP_SUAMI',
            'KTP_ISTRI',
            'KK',
            'AKTA_CERAI',
            'AKTA_NIKAH',
            'PUTUSAN_PA',
            'AKTA_KEMATIAN',
            'SURAT_KETERANGAN_AHLI_WARIS',
            'SURAT_PINDAH',
            'SURAT_KETERANGAN_GHAIB',
            'AKTA_KELAHIRAN_ANAK',
            'SURAT_PENGANTAR',
            'OTHER' => $publicType,

            // Aliases (legacy / display labels → canonical types)
            'SURAT_NIKAH',
            'AKTA_KAWIN'  => 'AKTA_NIKAH',

            // Legacy types → OTHER
            'KTP',
            'FOTO_DIRI',
            'LAINNYA' => 'OTHER',

            default => 'OTHER',
        };
    }

    /**
     * Map case document types → display labels.
     * Used by public-facing forms.
     */
    public static function publicLabels(): array
    {
        return [
            'KTP_SUAMI'                    => 'KTP Suami',
            'KTP_ISTRI'                    => 'KTP Istri',
            'KK'                           => 'Kartu Keluarga (KK)',
            'AKTA_NIKAH'                   => 'Akta/Buku Nikah',
            'AKTA_CERAI'                   => 'Akta Perceraian',
            'PUTUSAN_PA'                   => 'Putusan Pengadilan Agama',
            'AKTA_KEMATIAN'                => 'Akta Kematian Pasangan',
            'SURAT_KETERANGAN_AHLI_WARIS' => 'Surat Keterangan Ahli Waris',
            'SURAT_PINDAH'                 => 'Surat Pindah',
            'SURAT_KETERANGAN_GHAIB'       => 'Surat Keterangan Ghaib',
            'AKTA_KELAHIRAN_ANAK'          => 'Akta Kelahiran Anak',
            'SURAT_PENGANTAR'              => 'Surat Pengantar',
            'OTHER'                        => 'Dokumen Lainnya',
        ];
    }
}
