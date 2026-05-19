<?php

namespace App\Support;

use Database\Seeders\RbacSeeder;

/**
 * Permission slugs for OCL_HR (aligned with {@see RbacSeeder}).
 */
final class PortalPermissions
{
    public const STAFF_USER_READ = 'staff_user_management.staff_user.read';

    public const STAFF_USER_CREATE = 'staff_user_management.staff_user.create';

    public const STAFF_USER_UPDATE = 'staff_user_management.staff_user.update';

    public const STAFF_USER_ARCHIVE = 'staff_user_management.staff_user.archive';

    public const STAFF_ROLE_READ = 'staff_role_management.staff_role.read';

    public const STAFF_ROLE_UPDATE = 'staff_role_management.staff_role.update';

    /** @deprecated Prefer granular induction.policy.* permissions; still grants full induction management. */
    public const INDUCTION_POLICY_MANAGE = 'induction.policy.manage';

    public const INDUCTION_POLICY_READ = 'induction.policy.read';

    public const INDUCTION_POLICY_CREATE = 'induction.policy.create';

    public const INDUCTION_POLICY_UPDATE = 'induction.policy.update';

    public const INDUCTION_POLICY_ARCHIVE = 'induction.policy.archive';

    public const INDUCTION_ENROLLMENT_READ = 'induction.enrollment.read';

    /**
     * Slugs that satisfy a required permission (including legacy umbrella grants).
     *
     * @return list<string>
     */
    public static function grantingSlugsFor(string $requiredSlug): array
    {
        $grants = [$requiredSlug];

        if ($requiredSlug === self::INDUCTION_POLICY_MANAGE) {
            return array_values(array_unique([
                self::INDUCTION_POLICY_MANAGE,
                self::INDUCTION_POLICY_READ,
                self::INDUCTION_POLICY_CREATE,
                self::INDUCTION_POLICY_UPDATE,
                self::INDUCTION_POLICY_ARCHIVE,
            ]));
        }

        if (in_array($requiredSlug, [
            self::INDUCTION_POLICY_READ,
            self::INDUCTION_POLICY_CREATE,
            self::INDUCTION_POLICY_UPDATE,
            self::INDUCTION_POLICY_ARCHIVE,
        ], true)) {
            $grants[] = self::INDUCTION_POLICY_MANAGE;
        }

        return array_values(array_unique($grants));
    }

    /**
     * @param  list<string>  $assignedSlugs
     */
    public static function isGranted(string $requiredSlug, array $assignedSlugs): bool
    {
        foreach (self::grantingSlugsFor($requiredSlug) as $grantingSlug) {
            if (in_array($grantingSlug, $assignedSlugs, true)) {
                return true;
            }
        }

        return false;
    }
}
