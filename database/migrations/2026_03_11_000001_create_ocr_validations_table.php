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
        Schema::create('ocr_validations', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('ocr_result_id')->constrained('ocr_results')->onDelete('cascade');
            $table->foreignId('case_id')->nullable()->constrained('cases')->onDelete('cascade');
            $table->foreignId('public_submission_id')->nullable()->constrained('public_submissions')->onDelete('cascade');
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            
            // Input snapshot (data yang diinput manual)
            $table->string('input_nik', 16)->nullable();
            $table->string('input_nama')->nullable();
            $table->string('input_tempat_lahir')->nullable();
            $table->date('input_tgl_lahir')->nullable();
            $table->text('input_alamat')->nullable();
            $table->string('input_rt_rw', 10)->nullable();
            $table->string('input_kelurahan')->nullable();
            $table->string('input_kecamatan')->nullable();
            $table->string('input_no_kk', 16)->nullable();
            
            // OCR snapshot (data hasil ekstraksi OCR)
            $table->string('ocr_nik', 16)->nullable();
            $table->string('ocr_nama')->nullable();
            $table->string('ocr_tempat_lahir')->nullable();
            $table->string('ocr_tgl_lahir', 50)->nullable();
            $table->text('ocr_alamat')->nullable();
            $table->string('ocr_rt_rw', 10)->nullable();
            $table->string('ocr_kelurahan')->nullable();
            $table->string('ocr_kecamatan')->nullable();
            $table->string('ocr_no_kk', 16)->nullable();
            
            // Comparison results (JSON field-by-field comparison)
            $table->json('comparison_results');
            $table->decimal('overall_match_score', 5, 2)->default(0);
            $table->unsignedInteger('fields_matched')->default(0);
            $table->unsignedInteger('fields_total')->default(0);
            
            // Validation status
            $table->enum('validation_status', ['MATCH', 'PARTIAL_MATCH', 'MISMATCH', 'MANUAL_REVIEW']);
            $table->boolean('is_reviewed')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['case_id', 'validation_status']);
            $table->index(['public_submission_id', 'validation_status']);
            $table->index('is_reviewed');
        });
        
        // Update ocr_results table
        Schema::table('ocr_results', function (Blueprint $table) {
            $table->boolean('has_validation')->default(false)->after('is_validated');
            $table->index('has_validation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ocr_results', function (Blueprint $table) {
            $table->dropIndex(['has_validation']);
            $table->dropColumn('has_validation');
        });
        
        Schema::dropIfExists('ocr_validations');
    }
};
