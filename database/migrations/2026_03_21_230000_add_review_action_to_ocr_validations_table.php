<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ocr_validations', function (Blueprint $table) {
            if (!Schema::hasColumn('ocr_validations', 'review_action')) {
                $table->enum('review_action', ['approve', 'reject', 'request_correction'])
                    ->nullable()
                    ->after('is_reviewed');
                $table->index('review_action');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ocr_validations', function (Blueprint $table) {
            if (Schema::hasColumn('ocr_validations', 'review_action')) {
                $table->dropIndex(['review_action']);
                $table->dropColumn('review_action');
            }
        });
    }
};
