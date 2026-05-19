<?php

namespace App\Support;

use App\Models\Permission;
use Illuminate\Support\Collection;

/**
 * Business-facing access levels shown when configuring role template permissions.
 */
final class PortalAccessLevels
{
    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     subtitle: string|null,
     *     capabilities: list<array{slug: string, label: string}>
     * }>
     */
    public static function definitions(): array
    {
        return [
            [
                'key' => 'user_management',
                'label' => 'User Management',
                'subtitle' => 'CRU & Archive',
                'capabilities' => [
                    ['slug' => PortalPermissions::STAFF_USER_READ, 'label' => 'Read'],
                    ['slug' => PortalPermissions::STAFF_USER_CREATE, 'label' => 'Create'],
                    ['slug' => PortalPermissions::STAFF_USER_UPDATE, 'label' => 'Update'],
                    ['slug' => PortalPermissions::STAFF_USER_ARCHIVE, 'label' => 'Archive'],
                ],
            ],
            [
                'key' => 'induction_management',
                'label' => 'Induction Management',
                'subtitle' => 'CRU & Archive',
                'capabilities' => [
                    ['slug' => PortalPermissions::INDUCTION_POLICY_READ, 'label' => 'Read'],
                    ['slug' => PortalPermissions::INDUCTION_POLICY_CREATE, 'label' => 'Create'],
                    ['slug' => PortalPermissions::INDUCTION_POLICY_UPDATE, 'label' => 'Update'],
                    ['slug' => PortalPermissions::INDUCTION_POLICY_ARCHIVE, 'label' => 'Archive'],
                ],
            ],
            [
                'key' => 'user_induction_progress',
                'label' => 'User Induction Progress',
                'subtitle' => null,
                'capabilities' => [
                    ['slug' => PortalPermissions::INDUCTION_ENROLLMENT_READ, 'label' => 'Read'],
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function permissionSlugs(): array
    {
        $slugs = [];

        foreach (self::definitions() as $level) {
            foreach ($level['capabilities'] as $capability) {
                $slugs[] = $capability['slug'];
            }
        }

        return array_values(array_unique($slugs));
    }

    /**
     * @param  Collection<int, Permission>|list<Permission>  $permissions
     * @return list<array{
     *     key: string,
     *     label: string,
     *     subtitle: string|null,
     *     capabilities: list<array{slug: string, label: string, granted: bool}>
     * }>
     */
    public static function summarize(Collection|array $permissions): array
    {
        $assigned = collect($permissions)->pluck('slug')->all();
        $summary = [];

        foreach (self::definitions() as $level) {
            $capabilities = [];

            foreach ($level['capabilities'] as $capability) {
                $capabilities[] = [
                    'slug' => $capability['slug'],
                    'label' => $capability['label'],
                    'granted' => PortalPermissions::isGranted($capability['slug'], $assigned),
                ];
            }

            $summary[] = [
                'key' => $level['key'],
                'label' => $level['label'],
                'subtitle' => $level['subtitle'],
                'capabilities' => $capabilities,
            ];
        }

        return $summary;
    }

    /**
     * @param  Collection<int, Permission>  $allPermissions
     * @return array<string, int>
     */
    public static function permissionIdsBySlug(Collection $allPermissions): array
    {
        return $allPermissions->pluck('id', 'slug')->all();
    }
}
