<?php

namespace Database\Seeders;

use App\Models\RoleTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds one staff user per role template for local / QA sign-in.
 *
 * Email pattern: role template slug with underscores removed, then @example.com
 * (e.g. hr_admin → hradmin@example.com, director_gm → directorgm@example.com).
 *
 * Set ROLE_DEMO_USERS_PASSWORD in production; in other environments it defaults to
 * "password" with a console warning when unset.
 */
class RoleTemplateUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ROLE_DEMO_USERS_PASSWORD');
        if (! is_string($password) || $password === '') {
            if (app()->environment('production')) {
                $this->command?->error('RoleTemplateUsersSeeder: set ROLE_DEMO_USERS_PASSWORD in production; skipping role template users.');

                return;
            }
            $password = 'password';
            $this->command?->warn('RoleTemplateUsersSeeder: ROLE_DEMO_USERS_PASSWORD not set; using default "password" (local/development only).');
        }

        $bySlug = [
            RoleTemplate::SLUG_EMPLOYEE => ['first_name' => 'Demo', 'last_name' => 'Employee'],
            RoleTemplate::SLUG_MANAGER => ['first_name' => 'Demo', 'last_name' => 'Manager'],
            RoleTemplate::SLUG_HR_ADMIN => ['first_name' => 'Demo', 'last_name' => 'HR Admin'],
            RoleTemplate::SLUG_DIRECTOR_GM => ['first_name' => 'Demo', 'last_name' => 'Director GM'],
        ];

        foreach ($bySlug as $slug => $names) {
            $email = str_replace('_', '', $slug).'@example.com';

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'title' => null,
                    'first_name' => $names['first_name'],
                    'last_name' => $names['last_name'],
                    'password' => $password,
                    'is_staff_super_user' => false,
                    'archived_at' => null,
                    'email_verified_at' => now(),
                ],
            );

            $user->assignRoleByTemplateSlug($slug);

            $this->command?->info("RoleTemplateUsersSeeder: {$email} → {$slug}");
        }
    }
}
