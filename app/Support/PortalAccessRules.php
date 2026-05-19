<?php

namespace App\Support;

use App\Models\User;

/**
 * Granular portal access rules (aligned with {@see PortalAccessLevels}).
 */
final class PortalAccessRules
{
    public static function allows(?User $user, string $permission): bool
    {
        return $user !== null && ($user->isStaffSuperUser() || $user->hasPermission($permission));
    }

    public static function allowsAny(?User $user, string ...$permissions): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isStaffSuperUser()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function canReadUsers(?User $user): bool
    {
        return self::allows($user, PortalPermissions::STAFF_USER_READ);
    }

    public static function canCreateUsers(?User $user): bool
    {
        return self::allows($user, PortalPermissions::STAFF_USER_CREATE);
    }

    public static function canUpdateUsers(?User $user): bool
    {
        return self::allows($user, PortalPermissions::STAFF_USER_UPDATE);
    }

    public static function canArchiveUsers(?User $user): bool
    {
        return self::allows($user, PortalPermissions::STAFF_USER_ARCHIVE);
    }

    public static function canReadRoles(?User $user): bool
    {
        return self::allows($user, PortalPermissions::STAFF_ROLE_READ);
    }

    public static function canUpdateRoles(?User $user): bool
    {
        return self::allows($user, PortalPermissions::STAFF_ROLE_UPDATE);
    }

    public static function canReadInductionPolicies(?User $user): bool
    {
        return self::allows($user, PortalPermissions::INDUCTION_POLICY_READ);
    }

    public static function canCreateInductionPolicies(?User $user): bool
    {
        return self::allows($user, PortalPermissions::INDUCTION_POLICY_CREATE);
    }

    public static function canUpdateInductionPolicies(?User $user): bool
    {
        return self::allows($user, PortalPermissions::INDUCTION_POLICY_UPDATE);
    }

    public static function canArchiveInductionPolicies(?User $user): bool
    {
        return self::allows($user, PortalPermissions::INDUCTION_POLICY_ARCHIVE);
    }

    public static function canReadInductionEnrollment(?User $user): bool
    {
        return self::allows($user, PortalPermissions::INDUCTION_ENROLLMENT_READ);
    }

    /**
     * May open the induction admin area (dashboard link and index route).
     */
    public static function canAccessInductionAdmin(?User $user): bool
    {
        return self::allowsAny(
            $user,
            PortalPermissions::INDUCTION_POLICY_MANAGE,
            PortalPermissions::INDUCTION_POLICY_READ,
            PortalPermissions::INDUCTION_POLICY_CREATE,
            PortalPermissions::INDUCTION_POLICY_UPDATE,
            PortalPermissions::INDUCTION_POLICY_ARCHIVE,
            PortalPermissions::INDUCTION_ENROLLMENT_READ,
        );
    }

    public static function authorize(?User $user, string $permission): void
    {
        abort_unless(self::allows($user, $permission), 403);
    }
}
