<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_about_sections', function (Blueprint $table) {
            $table->string('section_key', 100)->unique()->after('id');
            $table->string('title', 200)->after('section_key');
            $table->longText('content')->after('title');
            $table->string('image_path', 255)->nullable()->after('content');
            $table->unsignedInteger('display_order')->default(0)->after('image_path');
            $table->boolean('is_active')->default(true)->after('display_order');
            $table->unsignedBigInteger('updated_by')->nullable()->after('is_active');

            $table->index('is_active');
            $table->index('display_order');

            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cms_about_sections', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['display_order']);
            $table->dropColumn([
                'section_key', 'title', 'content', 'image_path',
                'display_order', 'is_active', 'updated_by',
            ]);
        });
    }
};
