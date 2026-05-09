<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Analysis Jobs ──────────────────────────────────────────────────────
        Schema::create('analysis_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_token')->nullable()->index();      // for unauthenticated users
            $table->string('status')->default('pending');            // pending|processing|completed|failed
            $table->string('input_type');                            // file | folder
            $table->unsignedInteger('file_count')->default(1);
            $table->string('vt_api_key')->nullable();                // user-supplied key override
            $table->boolean('skip_vt')->default(false);
            $table->json('options')->nullable();                     // additional options
            $table->json('result')->nullable();                      // final JSON result
            $table->text('error_message')->nullable();
            $table->string('python_exit_code')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // ── Uploaded Files ─────────────────────────────────────────────────────
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('analysis_job_id')->index();
            $table->foreign('analysis_job_id')->references('id')->on('analysis_jobs')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');                           // UUID-based safe name
            $table->string('disk')->default('local');                // local | s3
            $table->string('path');                                  // relative storage path
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256')->nullable()->index();
            $table->timestamps();
        });

        // ── Analysis Reports ────────────────────────────────────────────────────
        Schema::create('analysis_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('analysis_job_id')->unique();
            $table->foreign('analysis_job_id')->references('id')->on('analysis_jobs')->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('pdf_path')->nullable();                  // path to PDF report
            $table->string('json_path')->nullable();                 // path to JSON result dump
            $table->string('risk_level')->nullable();                // CLEAN|LOW|MEDIUM|HIGH|CRITICAL
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
        });

        // ── Download Logs ───────────────────────────────────────────────────────
        Schema::create('report_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_downloads');
        Schema::dropIfExists('analysis_reports');
        Schema::dropIfExists('uploaded_files');
        Schema::dropIfExists('analysis_jobs');
    }
};
