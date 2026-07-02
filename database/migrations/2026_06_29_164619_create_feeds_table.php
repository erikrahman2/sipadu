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
        Schema::create('feeds', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('url', 500);
            $table->string('source_type', 50); // official_site, manual_entry
            $table->json('headers')->nullable();
            $table->string('title_selector')->nullable();
            $table->string('link_selector')->nullable();
            $table->string('date_selector')->nullable();
            $table->string('excerpt_selector')->nullable();
            $table->string('content_selector')->nullable();
            $table->string('category_selector')->nullable();
            $table->integer('sync_interval_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feeds');
    }
};
