<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->string('petitioner_alamat')->nullable()->after('petitioner_phone');
            $table->string('petitioner_rt_rw', 10)->nullable()->after('petitioner_alamat');
            $table->string('petitioner_kelurahan')->nullable()->after('petitioner_rt_rw');
            $table->string('petitioner_kecamatan')->nullable()->after('petitioner_kelurahan');

            $table->string('spouse_alamat')->nullable()->after('spouse_name');
            $table->string('spouse_rt_rw', 10)->nullable()->after('spouse_alamat');
            $table->string('spouse_kelurahan')->nullable()->after('spouse_rt_rw');
            $table->string('spouse_kecamatan')->nullable()->after('spouse_kelurahan');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE documents MODIFY COLUMN document_type ENUM('KTP','KTP_SUAMI','KTP_ISTRI','KK','AKTA_CERAI','PUTUSAN_PA','AKTA_NIKAH','SURAT_PENGANTAR','OTHER') NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn([
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

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE documents MODIFY COLUMN document_type ENUM('KTP','KK','AKTA_CERAI','PUTUSAN_PA','AKTA_NIKAH','SURAT_PENGANTAR','OTHER') NOT NULL");
        }
    }
};
