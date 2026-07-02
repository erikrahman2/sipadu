<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 1) cms_home_sections: keep as-is (no section_key)
     *    - content_type column already exists and serves the matching purpose
     *
     * 2) cms_about_sections: migrate section_key -> content_type
     *    - Map existing section_key values to content_type
     *    - Drop section_key column
     *    - The about page template will match by content_type instead
     */
    public function up(): void
    {
        // Home sections: just drop section_key
        Schema::table('cms_home_sections', function (Blueprint $table) {
            $table->dropUnique(['section_key']);
            $table->dropColumn('section_key');
        });

        // About sections: migrate section_key -> content_type first
        Schema::table('cms_about_sections', function (Blueprint $table) {
            $table->string('content_type', 100)->nullable()->after('section_key');
        });

        DB::table('cms_about_sections')->update([
            'content_type' => DB::raw("
                CASE section_key
                    WHEN 'tentang_sipadu' THEN 'tentang_sipadu'
                    WHEN 'institusi_kerja_sama' THEN 'institusi_kerja_sama'
                    WHEN 'institusi_kerja-sama' THEN 'institusi_kerja_sama'
                    WHEN 'institusi_pendukung' THEN 'institusi_pendukung'
                    WHEN 'visi_misi' THEN 'visi_misi'
                    WHEN 'fitur_keunggulan' THEN 'fitur_keunggulan'
                    ELSE section_key
                END
            "),
        ]);

        Schema::table('cms_about_sections', function (Blueprint $table) {
            $table->dropUnique(['section_key']);
            $table->dropColumn('section_key');
        });
    }

    public function down(): void
    {
        // Home: restore section_key
        Schema::table('cms_home_sections', function (Blueprint $table) {
            $table->string('section_key', 100)->unique()->after('id')->nullable();
        });

        // About: restore section_key from content_type
        Schema::table('cms_about_sections', function (Blueprint $table) {
            $table->string('section_key', 100)->unique()->after('id')->nullable();
        });

        DB::table('cms_about_sections')->update([
            'section_key' => DB::raw('content_type'),
        ]);

        Schema::table('cms_about_sections', function (Blueprint $table) {
            $table->dropColumn('content_type');
        });
    }
};
