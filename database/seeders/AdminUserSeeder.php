<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed a staff super-user for the staff portal (all permissions via is_staff_super_user).
     *
     * Configure with ADMIN_EMAIL (required). ADMIN_PASSWORD optional in non-production
     * (defaults to "password" in local/development with a console warning).
     */
    public function run(): void
    {
        $email = trim((string) env('ADMIN_EMAIL', ''));
        if ($email === '') {
            $this->command?->warn('AdminUserSeeder: ADMIN_EMAIL is empty; skipping admin user.');

            return;
        }

        $password = env('ADMIN_PASSWORD');
        if (! is_string($password) || $password === '') {
            if (app()->environment('production')) {
                $this->command?->error('AdminUserSeeder: set ADMIN_PASSWORD in production; skipping admin user.');

                return;
            }
            $password = 'password';
            $this->command?->warn('AdminUserSeeder: ADMIN_PASSWORD not set; using default "password" (local/development only).');
        }

        $adminName = trim((string) env('ADMIN_NAME', 'OCL HR Admin'));
        $parts = preg_split('/\s+/u', $adminName, 2, PREG_SPLIT_NO_EMPTY);

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'title' => null,
                'first_name' => $parts[0] ?? 'Admin',
                'last_name' => isset($parts[1]) ? (string) $parts[1] : 'User',
                'password' => $password,
                'is_staff_super_user' => true,
                'archived_at' => null,
                'email_verified_at' => now(),
            ],
        );

        $this->command?->info("AdminUserSeeder: staff super-user ready ({$email}).");
    }
}
