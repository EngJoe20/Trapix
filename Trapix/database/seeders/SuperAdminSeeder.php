<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create or get a High-Limit Plan for the Super Admin
        $plan = Plan::updateOrCreate(
            ['slug' => 'super-admin-plan'],
            [
                'name' => 'Super Admin Plan',
                'description' => 'Unlimited administrative access and high quotas.',
                'monthly_analyses' => 1000,
                'max_upload_bytes' => 1048576 * 500, // 500MB
                'report_downloads_unlimited' => true,
                'report_download_limit' => 5000,
                'ai_access' => true,
                'priority_processing' => true,
                'price_monthly_cents' => 0,
                'is_active' => true,
            ]
        );

        // 2. Create the Super Admin User
        User::updateOrCreate(
            ['email' => 'admin@trapix.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'), // Simple password as requested
                'role' => 'admin',
                'plan_id' => $plan->id,
                'monthly_analysis_used' => 0,
                'quota_reset_date' => now()->addMonth(),
            ]
        );
    }
}
