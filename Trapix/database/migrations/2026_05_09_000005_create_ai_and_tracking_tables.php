<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── AI Provider Responses ──────────────────────────────────────────────
        Schema::create('ai_responses', function (Blueprint $table) {
            $table->id();
            $table->uuid('analysis_job_id')->index();
            $table->foreign('analysis_job_id')->references('id')->on('analysis_jobs')->cascadeOnDelete();
            $table->string('provider');                    // openai | gemini | claude | ollama
            $table->string('model')->nullable();
            $table->json('insights')->nullable();          // structured JSON insights
            $table->string('pdf_path')->nullable();        // AI-generated PDF
            $table->unsignedInteger('tokens_used')->default(0);
            $table->unsignedInteger('cost_microcents')->default(0); // cost in micro-cents
            $table->string('status')->default('pending');  // pending|completed|failed
            $table->text('error')->nullable();
            $table->timestamps();
        });

        // ── API Usage Tracking ─────────────────────────────────────────────────
        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_token')->nullable()->index();
            $table->string('endpoint');
            $table->string('method', 10);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // ── Guest Analysis Quota Tracking ──────────────────────────────────────
        // Tracks guest tokens so we can enforce the 3-analysis limit by IP+fingerprint
        Schema::create('guest_quota_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedTinyInteger('analysis_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_quota_tokens');
        Schema::dropIfExists('api_usage_logs');
        Schema::dropIfExists('ai_responses');
    }
};
