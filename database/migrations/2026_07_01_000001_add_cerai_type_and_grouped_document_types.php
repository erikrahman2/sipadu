<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_submissions', function (Blueprint $table) {
            $table->string('cerai_type', 50)->nullable()->after('petitioner_name');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->enum('document_type', [
                'KTP',
                'KK',
                'AKTA_CERAI',
                'PUTUSAN_PA',
                'AKTA_NIKAH',
                'SURAT_PENGANTAR',
                'OTHER',
                'KTP_SUAMI',
                'KTP_ISTRI',
                'BAST',
                'DIGITAL_COPY',
                'AKTA_KEMATIAN',
                'SURAT_KETERANGAN_AHLI_WARIS',
                'SURAT_PINDAH',
                'SURAT_KETERANGAN_GHAIB',
                'AKTA_KELAHIRAN_ANAK',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('document_type', [
                'KTP',
                'KK',
                'AKTA_CERAI',
                'PUTUSAN_PA',
                'AKTA_NIKAH',
                'SURAT_PENGANTAR',
                'OTHER',
                'KTP_SUAMI',
                'KTP_ISTRI',
                'BAST',
                'DIGITAL_COPY',
            ])->change();
        });

        Schema::table('public_submissions', function (Blueprint $table) {
            $table->dropColumn('cerai_type');
        });
    }
};
