<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_submissions', function (Blueprint $table) {
            // Tambah field untuk Data Suami
            $table->string('nik_suami', 16)->nullable()->after('tracking_token');
            $table->string('nama_suami')->nullable()->after('nik_suami');
            $table->string('alamat_suami', 255)->nullable()->after('nama_suami');
            $table->string('rt_rw_suami', 10)->nullable()->after('alamat_suami');
            $table->string('kelurahan_suami', 100)->nullable()->after('rt_rw_suami');
            $table->string('kecamatan_suami', 100)->nullable()->after('kelurahan_suami');

            // Tambah field untuk Data Istri
            $table->string('nik_istri', 16)->nullable()->after('kecamatan_suami');
            $table->string('nama_istri')->nullable()->after('nik_istri');
            $table->string('alamat_istri', 255)->nullable()->after('nama_istri');
            $table->string('rt_rw_istri', 10)->nullable()->after('alamat_istri');
            $table->string('kelurahan_istri', 100)->nullable()->after('rt_rw_istri');
            $table->string('kecamatan_istri', 100)->nullable()->after('kelurahan_istri');

            // Tambah institution_id jika belum ada
            if (!Schema::hasColumn('public_submissions', 'institution_id')) {
                $table->foreignId('institution_id')
                      ->nullable()
                      ->after('kecamatan_istri')
                      ->constrained('institutions')
                      ->nullOnDelete();
            }

            // Create index untuk nik_suami dan nik_istri untuk rate-limit
            $table->index(['nik_suami', 'created_at'], 'public_sub_nik_suami_created_at_idx');
            $table->index(['nik_istri', 'created_at'], 'public_sub_nik_istri_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('public_submissions', function (Blueprint $table) {
            // Drop indexes
            if (Schema::hasTable('public_submissions')) {
                try {
                    $table->dropIndex('public_sub_nik_suami_created_at_idx');
                } catch (\Exception $e) {
                    // Index mungkin sudah tidak ada
                }
                try {
                    $table->dropIndex('public_sub_nik_istri_created_at_idx');
                } catch (\Exception $e) {
                    // Index mungkin sudah tidak ada
                }
            }

            // Drop columns - Suami
            $table->dropColumn([
                'nik_suami',
                'nama_suami',
                'alamat_suami',
                'rt_rw_suami',
                'kelurahan_suami',
                'kecamatan_suami',
                'nik_istri',
                'nama_istri',
                'alamat_istri',
                'rt_rw_istri',
                'kelurahan_istri',
                'kecamatan_istri',
            ]);
        });
    }
};
