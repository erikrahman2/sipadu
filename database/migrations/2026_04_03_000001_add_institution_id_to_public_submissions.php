<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_submissions', function (Blueprint $table) {
            // Tambahkan kolom institution_id setelah phone_wa
            $table->foreignId('institution_id')
                  ->nullable()
                  ->after('phone_wa')
                  ->constrained('institutions')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('public_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('institution_id');
        });
    }
};
