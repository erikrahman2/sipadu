<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_home_sections', function (Blueprint $table) {
            $table->string('content_type', 100)->nullable()->after('id');
            $table->index('content_type');
        });

        // Populate content_type for existing home sections based on known patterns
        // These are the content types the public home page template uses
        $existing = DB::table('cms_home_sections')->get();
        foreach ($existing as $row) {
            $titleLower = strtolower($row->title ?? '');
            if (str_contains($titleLower, 'hero') || str_contains($titleLower, 'selamat datang')) {
                DB::table('cms_home_sections')->where('id', $row->id)->update(['content_type' => 'hero']);
            } elseif (str_contains($titleLower, 'metodologi')) {
                DB::table('cms_home_sections')->update(['content_type' => 'metodologi']);
            } elseif (str_contains($titleLower, 'statistik') || str_contains($titleLower, 'angka')) {
                DB::table('cms_home_sections')->where('id', $row->id)->update(['content_type' => 'stats']);
            } elseif (str_contains($titleLower, 'blog') || str_contains($titleLower, 'berita')) {
                DB::table('cms_home_sections')->where('id', $row->id)->update(['content_type' => 'blog_header']);
            } elseif (str_contains($titleLower, 'seo') || str_contains($titleLower, 'optimasi')) {
                DB::table('cms_home_sections')->where('id', $row->id)->update(['content_type' => 'seo']);
            } else {
                DB::table('cms_home_sections')->where('id', $row->id)->update(['content_type' => 'umum']);
            }
        }

    }

    public function down(): void
    {
        Schema::table('cms_home_sections', function (Blueprint $table) {
            $table->dropIndex(['content_type']);
            $table->dropColumn('content_type');
        });
    }
};
