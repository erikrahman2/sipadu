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
        Schema::table('cms_blog_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('cms_blog_posts', 'source_url')) {
                $table->string('source_url')->unique()->nullable()->after('category_name');
            }
            if (!Schema::hasColumn('cms_blog_posts', 'source_feed_id')) {
                $table->foreignId('source_feed_id')->nullable()->constrained('feeds')->onDelete('set null');
                $table->timestamp('synced_at')->nullable()->after('source_feed_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('cms_blog_posts', 'source_url')) {
            Schema::table('cms_blog_posts', function (Blueprint $table) {
                $table->dropForeign(['source_feed_id']);
                $table->dropColumn(['source_url', 'source_feed_id', 'synced_at']);
            });
        }
    }
};
