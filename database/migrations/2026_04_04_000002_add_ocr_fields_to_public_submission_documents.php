<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_submission_documents', function (Blueprint $table) {
            // Add OCR processing columns if they don't exist
            if (!Schema::hasColumn('public_submission_documents', 'ocr_status')) {
                $table->string('ocr_status')->default('PENDING')->after('mime_type')->comment('PENDING, PROCESSING, PROCESSED, FAILED');
            }
            if (!Schema::hasColumn('public_submission_documents', 'ocr_data')) {
                $table->longText('ocr_data')->nullable()->after('ocr_status')->comment('JSON data from OCR service');
            }
            if (!Schema::hasColumn('public_submission_documents', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('ocr_data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('public_submission_documents', function (Blueprint $table) {
            $table->dropColumn(['ocr_status', 'ocr_data', 'processed_at']);
        });
    }
};
