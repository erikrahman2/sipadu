<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            // Tambah reference ke public submission
            if (!Schema::hasColumn('cases', 'public_submission_id')) {
                $table->foreignId('public_submission_id')
                      ->nullable()
                      ->after('submitter_id')
                      ->constrained('public_submissions')
                      ->nullOnDelete();
                $table->index('public_submission_id');
            }

            // Tambah source untuk membedakan: internal (PA Assistant) vs public (pengajuan publik)
            if (!Schema::hasColumn('cases', 'source_type')) {
                $table->enum('source_type', ['internal', 'public'])
                      ->default('internal')
                      ->after('public_submission_id');
                $table->index('source_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropForeignKeyConstraints();
            $table->dropColumn([
                'public_submission_id',
                'source_type',
                'petitioner_alamat',
                'petitioner_rt_rw',
                'petitioner_kelurahan',
                'petitioner_kecamatan',
                'spouse_alamat',
                'spouse_rt_rw',
                'spouse_kelurahan',
                'spouse_kecamatan',
            ]);
        });
    }
};
