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
        Schema::table('cases', function (Blueprint $table) {
            $table->string('petitioner_nik', 16)->nullable()->after('submitter_id');
            $table->string('petitioner_name')->nullable()->after('petitioner_nik');
            $table->string('petitioner_phone', 20)->nullable()->after('petitioner_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn(['petitioner_nik', 'petitioner_name', 'petitioner_phone']);
        });
    }
};
