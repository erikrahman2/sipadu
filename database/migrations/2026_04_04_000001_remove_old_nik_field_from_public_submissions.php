<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_submissions', function (Blueprint $table) {
            // Jika masih ada field lama 'nik', drop dan ganti dengan field baru yang nullable
            if (Schema::hasColumn('public_submissions', 'nik')) {
                // Drop index lama
                try {
                    $table->dropIndex('public_submissions_nik_index');
                } catch (\Exception $e) {
                    // Index mungkin sudah tidak ada atau punya nama lain
                }

                // DROP dan recreate sebagai nullable (untuk backward compatibility)
                // karena nik_suami dan nik_istri sekarang adalah field utama
                $table->dropColumn('nik');
            }

            // Tambah field 'nik' dengan nullable untuk kompatibilitas (alias dari nik_suami)
            $table->string('nik', 16)->nullable()->after('tracking_token');
        });

        // Add a formula/trigger untuk set nik = nik_suami jika ada
        // Atau kita set via application code saat insert
    }

    public function down(): void
    {
        Schema::table('public_submissions', function (Blueprint $table) {
            // Restore field nik sebagai required
            if (Schema::hasColumn('public_submissions', 'nik')) {
                $table->dropColumn('nik');
            }
            $table->string('nik', 16)->index();
        });
    }
};
