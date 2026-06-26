<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_blog_posts', function (Blueprint $table) {
            $table->string('title', 200)->after('id');
            $table->string('slug', 200)->unique()->after('title');
            $table->string('excerpt', 500)->nullable()->after('slug');
            $table->longText('content')->after('excerpt');
            $table->string('cover_image', 255)->nullable()->after('content');
            $table->string('author_name', 100)->nullable()->after('cover_image');
            $table->enum('status', ['DRAFT', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT')->after('author_name');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->unsignedBigInteger('author_id')->nullable()->after('published_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('author_id');

            $table->index('status');
            $table->index('published_at');

            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cms_blog_posts', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
            $table->dropForeign(['updated_by']);
            $table->dropIndex(['status']);
            $table->dropIndex(['published_at']);
            $table->dropColumn([
                'title', 'slug', 'excerpt', 'content', 'cover_image',
                'author_name', 'status', 'published_at', 'author_id', 'updated_by',
            ]);
        });
    }
};
