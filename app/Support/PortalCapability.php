<?php

namespace App\Support;

use App\Models\User;

/**
 * View helper: which user-management actions the signed-in user may perform.
 */
final readonly class PortalCapability
{
    public function __construct(
        public bool $staffUserRead,
        public bool $staffUserCreate,
        public bool $staffUserUpdate,
        public bool $staffRoleRead,
        public bool $staffRoleUpdate,
        public bool $inductionPolicyManage,
        public bool $inductionEnrollmentRead,
    ) {}

    public static function forUser(?User $user): ?self
    {
        if ($user === null) {
            return null;
        }

        if ($user->isStaffSuperUser()) {
            return new self(
                staffUserRead: true,
                staffUserCreate: true,
                staffUserUpdate: true,
                staffRoleRead: true,
                staffRoleUpdate: true,
                inductionPolicyManage: true,
                inductionEnrollmentRead: true,
            );
        }

        return new self(
            staffUserRead: $user->hasPermission(PortalPermissions::STAFF_USER_READ),
            staffUserCreate: $user->hasPermission(PortalPermissions::STAFF_USER_CREATE),
            staffUserUpdate: $user->hasPermission(PortalPermissions::STAFF_USER_UPDATE),
            staffRoleRead: $user->hasPermission(PortalPermissions::STAFF_ROLE_READ),
            staffRoleUpdate: $user->hasPermission(PortalPermissions::STAFF_ROLE_UPDATE),
            inductionPolicyManage: $user->hasAnyPermission(
                PortalPermissions::INDUCTION_POLICY_MANAGE,
                PortalPermissions::INDUCTION_POLICY_READ,
                PortalPermissions::INDUCTION_POLICY_CREATE,
                PortalPermissions::INDUCTION_POLICY_UPDATE,
                PortalPermissions::INDUCTION_POLICY_ARCHIVE,
            ),
            inductionEnrollmentRead: $user->hasPermission(PortalPermissions::INDUCTION_ENROLLMENT_READ),
        );
    }
}
