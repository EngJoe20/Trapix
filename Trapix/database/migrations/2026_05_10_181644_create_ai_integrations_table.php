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
        Schema::create('ai_integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // openai, gemini, claude, ollama
            $table->text('api_key_encrypted')->nullable();
            $table->string('base_url')->nullable();
            $table->string('default_model')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            // A user can only have one configuration per provider
            $table->unique(['user_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_integrations');
    }
};
