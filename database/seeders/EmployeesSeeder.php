<?php

namespace Database\Seeders;

use App\Models\RoleTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds staff users from database/data/employees.csv (Title, First Name, Last Name, Email, Role).
 *
 * Role labels must match RBAC role template names (Employee, Manager, HR/Admin, Director/GM).
 * Set EMPLOYEE_SEED_PASSWORD in production; in other environments it defaults to "password"
 * with a console warning when unset.
 */
class EmployeesSeeder extends Seeder
{
    private const DATA_FILE = 'data/employees.csv';

    public function run(): void
    {
        $path = database_path(self::DATA_FILE);
        if (! is_readable($path)) {
            $this->command?->warn("EmployeesSeeder: missing or unreadable file: {$path}");

            return;
        }

        $password = $this->resolvePassword();
        if ($password === null) {
            return;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->command?->error("EmployeesSeeder: could not open {$path}");

            return;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return;
        }

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $record = $this->mapRow($header, $row);
            if ($record === null) {
                continue;
            }

            $slug = $this->roleTemplateSlugForLabel($record['role']);
            if ($slug === null) {
                $this->command?->warn("EmployeesSeeder: unknown role \"{$record['role']}\" for {$record['email']}; skipping.");

                continue;
            }

            $user = User::query()->updateOrCreate(
                ['email' => $record['email']],
                [
                    'title' => $record['title'] !== '' ? $record['title'] : null,
                    'first_name' => $record['first_name'],
                    'last_name' => $record['last_name'],
                    'password' => $password,
                    'is_staff_super_user' => false,
                    'archived_at' => null,
                    'email_verified_at' => now(),
                ],
            );

            $user->assignRoleByTemplateSlug($slug);
            $count++;
            $this->command?->info("EmployeesSeeder: {$record['email']} → {$slug}");
        }

        fclose($handle);

        $this->command?->info("EmployeesSeeder: seeded {$count} employee(s).");
    }

    private function resolvePassword(): ?string
    {
        $password = env('EMPLOYEE_SEED_PASSWORD');
        if (! is_string($password) || $password === '') {
            if (app()->environment('production')) {
                $this->command?->error('EmployeesSeeder: set EMPLOYEE_SEED_PASSWORD in production; skipping employees.');

                return null;
            }
            $password = 'password';
            $this->command?->warn('EmployeesSeeder: EMPLOYEE_SEED_PASSWORD not set; using default "password" (local/development only).');
        }

        return $password;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string|false|null>  $header
     * @param  list<string|null>  $row
     * @return array{title: string, first_name: string, last_name: string, email: string, role: string}|null
     */
    private function mapRow(array $header, array $row): ?array
    {
        $data = [];
        foreach ($header as $i => $column) {
            $key = Str::snake(trim((string) $column));
            $data[$key] = trim((string) ($row[$i] ?? ''));
        }

        $email = strtolower($data['email'] ?? '');
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $firstName = $data['first_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        if ($firstName === '' && $lastName === '') {
            return null;
        }

        return [
            'title' => $data['title'] ?? '',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'role' => $data['role'] ?? '',
        ];
    }

    private function roleTemplateSlugForLabel(string $role): ?string
    {
        return match (trim($role)) {
            'Employee' => RoleTemplate::SLUG_EMPLOYEE,
            'Manager' => RoleTemplate::SLUG_MANAGER,
            'HR/Admin' => RoleTemplate::SLUG_HR_ADMIN,
            'Director/GM' => RoleTemplate::SLUG_DIRECTOR_GM,
            default => null,
        };
    }
}
