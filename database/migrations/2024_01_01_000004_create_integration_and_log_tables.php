<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Integration / outbox queue for dual-DB sync
        Schema::create('integration_queue', function (Blueprint $table) {
            $table->id();
            $table->string('aggregate_type');       // 'Case', 'Document', 'User', ...
            $table->unsignedBigInteger('aggregate_id');
            $table->string('event_type');           // 'created', 'updated', 'deleted', ...
            $table->json('payload');
            $table->enum('status', ['PENDING','PROCESSING','SUCCESS','FAILED'])->default('PENDING');
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->integer('backoff_seconds')->default(10);
            $table->text('error')->nullable();
            $table->timestamp('available_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['status','available_at']);
            $table->index(['aggregate_type','aggregate_id']);
        });

        // Graph sync audit
        Schema::create('graph_sync_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained('integration_queue');
            $table->enum('operation', ['UPSERT_NODE','UPSERT_REL','DELETE_NODE','DELETE_REL']);
            $table->string('label_or_rel');
            $table->string('neo4j_id')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();
        });

        // Rate-limit / access log auxiliary
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('ip_address', 45);
            $table->string('method', 10);
            $table->string('path');
            $table->integer('status_code');
            $table->integer('response_time_ms')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_headers')->nullable();
            $table->timestamps();
            $table->index(['user_id','created_at']);
            $table->index('ip_address');
        });

        // Audit trail
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['user_id','created_at']);
            $table->index(['subject_type','subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('graph_sync_log');
        Schema::dropIfExists('integration_queue');
    }
};
