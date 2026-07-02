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
        Schema::table('layans', function (Blueprint $table) {
            $table->string('kategori', 100)->nullable()->default('lainnya')->after('nama');
            $table->string('icon')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layans', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });
    }
};
