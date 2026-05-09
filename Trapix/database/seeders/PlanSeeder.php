<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'                       => 'Free',
                'slug'                       => 'free',
                'description'                => 'Get started with basic malware triage.',
                'monthly_analyses'           => 10,
                'max_upload_bytes'           => 10 * 1024 * 1024,  // 10 MB
                'report_downloads_unlimited' => false,
                'report_download_limit'      => 2,
                'ai_access'                  => false,
                'priority_processing'        => false,
                'price_monthly_cents'        => 0,
                'is_active'                  => true,
            ],
            [
                'name'                       => 'Pro',
                'slug'                       => 'pro',
                'description'                => 'For security researchers and analysts.',
                'monthly_analyses'           => 200,
                'max_upload_bytes'           => 100 * 1024 * 1024, // 100 MB
                'report_downloads_unlimited' => true,
                'report_download_limit'      => 999,
                'ai_access'                  => true,
                'priority_processing'        => true,
                'price_monthly_cents'        => 2900,               // $29/month
                'is_active'                  => true,
            ],
            [
                'name'                       => 'Enterprise',
                'slug'                       => 'enterprise',
                'description'                => 'Unlimited analysis for security teams.',
                'monthly_analyses'           => 0,                  // 0 = unlimited
                'max_upload_bytes'           => 500 * 1024 * 1024, // 500 MB
                'report_downloads_unlimited' => true,
                'report_download_limit'      => 999,
                'ai_access'                  => true,
                'priority_processing'        => true,
                'price_monthly_cents'        => 9900,               // $99/month
                'is_active'                  => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $this->command->info('✅ Plans seeded: Free, Pro, Enterprise');
    }
}
