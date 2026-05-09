<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete()->after('remember_token');
            $table->unsignedInteger('guest_analysis_count')->default(0)->after('plan_id'); // for unauthenticated sessions
            $table->unsignedInteger('monthly_analysis_used')->default(0)->after('guest_analysis_count');
            $table->date('quota_reset_date')->nullable()->after('monthly_analysis_used'); // next monthly reset
            $table->string('role')->default('user')->after('quota_reset_date');           // user | admin
            $table->string('avatar')->nullable()->after('role');
            $table->string('api_token', 80)->nullable()->unique()->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn([
                'guest_analysis_count',
                'monthly_analysis_used',
                'quota_reset_date',
                'role',
                'avatar',
                'api_token',
            ]);
        });
    }
};
