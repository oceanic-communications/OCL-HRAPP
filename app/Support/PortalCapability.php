<?php

namespace App\Support;

use App\Models\User;

/**
 * View helper: which portal actions the signed-in user may perform.
 */
final readonly class PortalCapability
{
    public function __construct(
        public bool $staffUserRead,
        public bool $staffUserCreate,
        public bool $staffUserUpdate,
        public bool $staffUserArchive,
        public bool $staffRoleRead,
        public bool $staffRoleUpdate,
        public bool $inductionAdminAccess,
        public bool $inductionPolicyRead,
        public bool $inductionPolicyCreate,
        public bool $inductionPolicyUpdate,
        public bool $inductionPolicyArchive,
        public bool $inductionEnrollmentRead,
        public bool $inductionChangeLogRead,
    ) {}

    public static function forUser(?User $user): ?self
    {
        if ($user === null) {
            return null;
        }

        return new self(
            staffUserRead: PortalAccessRules::canReadUsers($user),
            staffUserCreate: PortalAccessRules::canCreateUsers($user),
            staffUserUpdate: PortalAccessRules::canUpdateUsers($user),
            staffUserArchive: PortalAccessRules::canArchiveUsers($user),
            staffRoleRead: PortalAccessRules::canReadRoles($user),
            staffRoleUpdate: PortalAccessRules::canUpdateRoles($user),
            inductionAdminAccess: PortalAccessRules::canAccessInductionAdmin($user),
            inductionPolicyRead: PortalAccessRules::canReadInductionPolicies($user),
            inductionPolicyCreate: PortalAccessRules::canCreateInductionPolicies($user),
            inductionPolicyUpdate: PortalAccessRules::canUpdateInductionPolicies($user),
            inductionPolicyArchive: PortalAccessRules::canArchiveInductionPolicies($user),
            inductionEnrollmentRead: PortalAccessRules::canReadInductionEnrollment($user),
            inductionChangeLogRead: PortalAccessRules::canReadInductionChangeLogs($user),
        );
    }
}
