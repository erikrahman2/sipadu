<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('public_submissions', function (Blueprint $table) {
            // Field detail pemohon untuk validasi OCR
            $table->string('nama_lengkap')->nullable()->after('petitioner_name')
                  ->comment('Nama lengkap sesuai KTP (untuk validasi OCR)');
            $table->string('tempat_lahir', 100)->nullable()->after('nama_lengkap')
                  ->comment('Tempat lahir pemohon');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir')
                  ->comment('Tanggal lahir pemohon');
            $table->text('alamat')->nullable()->after('tanggal_lahir')
                  ->comment('Alamat lengkap pemohon');
            $table->string('rt_rw', 10)->nullable()->after('alamat')
                  ->comment('RT/RW pemohon');
            $table->string('kelurahan', 100)->nullable()->after('rt_rw')
                  ->comment('Kelurahan/Desa pemohon');
            $table->string('kecamatan', 100)->nullable()->after('kelurahan')
                  ->comment('Kecamatan pemohon');
            $table->string('no_kk', 16)->nullable()->after('kecamatan')
                  ->comment('Nomor Kartu Keluarga')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('public_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'nama_lengkap',
                'tempat_lahir',
                'tanggal_lahir',
                'alamat',
                'rt_rw',
                'kelurahan',
                'kecamatan',
                'no_kk',
            ]);
        });
    }
};
