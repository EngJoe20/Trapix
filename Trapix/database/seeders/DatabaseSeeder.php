<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── Seed subscription plans first ──────────────────────────────────────
        $this->call(PlanSeeder::class);

        // ── Super Admin and Custom Users ───────────────────────────────────────
        $this->call(SuperAdminSeeder::class);

        // ── Test user ──────────────────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name'  => 'Test User',
                'password' => Hash::make('password'),
            ]
        );
    }
}
