<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->string('tracking_token', 64)->unique();
            $table->foreignId('submitter_id')->constrained('users');
            $table->foreignId('institution_id')->constrained('institutions');
            $table->string('spouse_nik', 16)->nullable();
            $table->string('spouse_name')->nullable();
            $table->date('divorce_date')->nullable();
            $table->string('verdict_number')->nullable();
            $table->enum('status', [
                'DRAFT','SUBMITTED','OCR_PROCESSED',
                'PA_REVIEW','DISDUKCAPIL_VALIDATION',
                'COMPLETED','ARCHIVED','REJECTED'
            ])->default('DRAFT');
            $table->text('notes')->nullable();
            $table->foreignId('assigned_pa_user_id')->nullable()->constrained('users');
            $table->foreignId('assigned_disdukcapil_user_id')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'institution_id']);
            $table->index('tracking_token');
        });

        Schema::create('case_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->string('from_state');
            $table->string('to_state');
            $table->foreignId('transitioned_by')->constrained('users');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['case_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_transitions');
        Schema::dropIfExists('cases');
    }
};
