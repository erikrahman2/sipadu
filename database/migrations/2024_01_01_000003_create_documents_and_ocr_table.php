<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('original_name');
            $table->string('stored_name')->unique();
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('mime_type', 50);
            $table->unsignedBigInteger('size_bytes');
            $table->enum('document_type', [
                'KTP', 'KK', 'AKTA_CERAI', 'PUTUSAN_PA',
                'AKTA_NIKAH', 'SURAT_PENGANTAR', 'OTHER'
            ]);
            $table->enum('status', [
                'PENDING','PROCESSING','PROCESSED','VALIDATED','REJECTED'
            ])->default('PENDING');
            $table->string('checksum', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['case_id','document_type']);
        });

        Schema::create('ocr_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('case_id')->constrained('cases');
            $table->string('nik', 16)->nullable();
            $table->string('no_kk', 16)->nullable();
            $table->string('nama')->nullable();
            $table->string('tgl_lahir', 20)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->string('jenis_kelamin', 10)->nullable();
            $table->string('alamat')->nullable();
            $table->string('rt_rw', 10)->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->json('raw_text')->nullable();
            $table->json('confidence_scores')->nullable();
            $table->float('overall_confidence')->nullable();
            $table->boolean('is_validated')->default(false);
            $table->json('validation_errors')->nullable();
            $table->enum('ocr_status', ['SUCCESS','PARTIAL','FAILED'])->default('PARTIAL');
            $table->string('engine_version', 20)->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->timestamps();
            $table->index(['case_id','ocr_status']);
        });

        Schema::create('ocr_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents');
            $table->enum('status', ['QUEUED','PROCESSING','DONE','FAILED'])->default('QUEUED');
            $table->integer('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->json('result_payload')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_jobs');
        Schema::dropIfExists('ocr_results');
        Schema::dropIfExists('documents');
    }
};
