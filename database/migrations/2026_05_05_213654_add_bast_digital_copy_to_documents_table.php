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
        Schema::table('documents', function (Blueprint $table) {
            // Change enum to include BAST and DIGITAL_COPY
            $table->enum('document_type', [
                'KTP', 'KK', 'AKTA_CERAI', 'PUTUSAN_PA',
                'AKTA_NIKAH', 'SURAT_PENGANTAR', 'OTHER',
                'KTP_SUAMI', 'KTP_ISTRI', 'BAST', 'DIGITAL_COPY'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Revert to original enum
            $table->enum('document_type', [
                'KTP', 'KK', 'AKTA_CERAI', 'PUTUSAN_PA',
                'AKTA_NIKAH', 'SURAT_PENGANTAR', 'OTHER'
            ])->change();
        });
    }
};
