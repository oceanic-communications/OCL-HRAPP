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
        public bool $inductionPolicyManage,
    ) {}

    public static function forUser(?User $user): ?self
    {
        if ($user === null) {
            return null;
        }

        if ($user->is_staff_super_user) {
            return new self(
                staffUserRead: true,
                staffUserCreate: true,
                staffUserUpdate: true,
                inductionPolicyManage: true,
            );
        }

        return new self(
            staffUserRead: $user->hasPermission(PortalPermissions::STAFF_USER_READ),
            staffUserCreate: $user->hasPermission(PortalPermissions::STAFF_USER_CREATE),
            staffUserUpdate: $user->hasPermission(PortalPermissions::STAFF_USER_UPDATE),
            inductionPolicyManage: $user->hasPermission(PortalPermissions::INDUCTION_POLICY_MANAGE),
        );
    }
}
