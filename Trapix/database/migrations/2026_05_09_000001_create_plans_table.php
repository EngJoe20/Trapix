<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Free, Pro, Enterprise
            $table->string('slug')->unique();                // free, pro, enterprise
            $table->text('description')->nullable();
            $table->unsignedInteger('monthly_analyses');     // 0 = unlimited
            $table->unsignedBigInteger('max_upload_bytes');  // max single-upload size in bytes
            $table->boolean('report_downloads_unlimited')->default(false);
            $table->unsignedInteger('report_download_limit')->default(2); // per report on free
            $table->boolean('ai_access')->default(false);
            $table->boolean('priority_processing')->default(false);
            $table->unsignedInteger('price_monthly_cents')->default(0); // 0 = free
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
