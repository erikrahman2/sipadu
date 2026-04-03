<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan tipe dokumen baru: AKTA_NIKAH, SURAT_PENGANTAR, OTHER
     * 
     * Catatan: MySQL ENUM tidak bisa langsung dimodifikasi, sehingga kita perlu
     * mengubah kolom menjadi VARCHAR dan memanualkan enumerasi di aplikasi,
     * atau membuat kolom tipe enum baru.
     * 
     * Alternatif: Ubah strategy ke TEXT/VARCHAR dan validasi di aplikasi.
     */
    public function up(): void
    {
        Schema::table('public_submission_documents', function (Blueprint $table) {
            // Ubah ENUM menjadi VARCHAR agar lebih fleksibel
            // Existing values: KTP, KK, AKTA_CERAI, PUTUSAN_PA, SURAT_NIKAH, FOTO_DIRI, LAINNYA
            $table->string('document_type')->change();
        });
    }

    public function down(): void
    {
        Schema::table('public_submission_documents', function (Blueprint $table) {
            // Restore ke ENUM jika perlu rollback
            $table->enum('document_type', [
                'KTP','KK','AKTA_CERAI','PUTUSAN_PA',
                'SURAT_NIKAH','FOTO_DIRI','LAINNYA',
            ])->change();
        });
    }
};
