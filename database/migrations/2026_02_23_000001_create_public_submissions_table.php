<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_submissions', function (Blueprint $table) {
            $table->id();

            // Tracking token dikirim ke WA — digunakan warga untuk lacak status
            $table->string('tracking_token', 64)->unique();

            // Data pemohon
            $table->string('nik', 16)->index();
            $table->string('petitioner_name');
            $table->string('phone_wa', 20);               // nomor WA tujuan notifikasi
            $table->string('respondent_name')->nullable();
            $table->string('respondent_nik', 16)->nullable();
            $table->date('divorce_date')->nullable();
            $table->string('verdict_number')->nullable();  // nomor putusan cerai
            $table->text('notes')->nullable();

            // Status pengajuan
            $table->enum('status', [
                'PENDING',           // baru masuk, belum diproses
                'REVIEWING',         // sedang ditinjau staff
                'WAITING_OCR',       // menunggu hasil OCR
                'APPROVED',          // disetujui, kasus resmi dibuat
                'REJECTED',          // ditolak
                'COMPLETED',         // selesai penuh
            ])->default('PENDING');

            // Notifikasi WA
            $table->timestamp('wa_sent_at')->nullable();
            $table->string('wa_message_id')->nullable();   // ID dari gateway WA
            $table->enum('wa_status', ['pending','sent','delivered','failed'])->default('pending');
            $table->string('wa_error')->nullable();

            // Relasi ke kasus resmi (setelah staff buat kasus dari pengajuan ini)
            $table->foreignId('case_id')->nullable()->constrained('cases')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();

            // Audit
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index untuk rate-limit per NIK
            $table->index(['nik', 'created_at']);
        });

        Schema::create('public_submission_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_submission_id')
                  ->constrained('public_submissions')
                  ->onDelete('cascade');
            $table->enum('document_type', [
                'KTP','KK','AKTA_CERAI','PUTUSAN_PA',
                'SURAT_NIKAH','FOTO_DIRI','LAINNYA',
            ]);
            $table->string('original_filename');
            $table->string('stored_path');
            $table->unsignedBigInteger('file_size')->default(0); // bytes
            $table->string('mime_type', 100)->nullable();
            $table->timestamps();

            $table->index(['public_submission_id', 'document_type'], 'pub_sub_docs_sub_id_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_submission_documents');
        Schema::dropIfExists('public_submissions');
    }
};
