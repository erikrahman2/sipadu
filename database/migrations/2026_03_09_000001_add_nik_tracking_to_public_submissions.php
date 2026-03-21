<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi: Tracking NIK & Validasi Pasangan
 * 
 * Aturan bisnis:
 * 1. Jika ada 2+ pengajuan dengan NIK sama → gunakan data terbaru, data lama diganti
 * 2. Limit pendaftaran: 3x per minggu (7 hari) per NIK
 * 3. Jika sudah di Disdukcapil, 2 NIK pasangan tidak boleh ditemukan lagi dalam data yang sama
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_submissions', function (Blueprint $table) {
            // Kolom untuk tracking penggantian data lama
            $table->foreignId('replaced_by')
                  ->nullable()
                  ->after('case_id')
                  ->comment('ID pengajuan baru yang menggantikan pengajuan ini')
                  ->constrained('public_submissions')
                  ->nullOnDelete();

            // Flag untuk marking data aktif/terbaru per NIK
            $table->boolean('is_active')
                  ->default(true)
                  ->after('replaced_by')
                  ->index()
                  ->comment('TRUE = data terbaru/aktif, FALSE = sudah diganti');

            // Index untuk cek duplikasi pasangan NIK (petitioner + respondent)
            // Untuk validasi: pasangan yang sama tidak boleh ada di data yang sudah masuk Disdukcapil
            $table->index(['nik', 'respondent_nik', 'status'], 'pub_sub_couple_nik_status_idx');
            
            // Index untuk cek data aktif per NIK
            $table->index(['nik', 'is_active', 'created_at'], 'pub_sub_nik_active_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('public_submissions', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['replaced_by']);
            
            // Drop indexes
            $table->dropIndex('pub_sub_couple_nik_status_idx');
            $table->dropIndex('pub_sub_nik_active_created_idx');
            $table->dropIndex(['is_active']);
            
            // Drop columns
            $table->dropColumn(['replaced_by', 'is_active']);
        });
    }
};
