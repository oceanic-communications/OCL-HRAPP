<?php

namespace App\Http\Controllers\Concerns;

use App\Support\PortalAccessRules;
use App\Support\PortalPermissions;

trait AuthorizesPortalAccess
{
    protected function portalUser()
    {
        return auth()->user();
    }

    protected function authorizeReadUsers(): void
    {
        PortalAccessRules::authorize($this->portalUser(), PortalPermissions::STAFF_USER_READ);
    }

    protected function authorizeCreateUsers(): void
    {
        PortalAccessRules::authorize($this->portalUser(), PortalPermissions::STAFF_USER_CREATE);
    }

    protected function authorizeUpdateUsers(): void
    {
        PortalAccessRules::authorize($this->portalUser(), PortalPermissions::STAFF_USER_UPDATE);
    }

    protected function authorizeArchiveUsers(): void
    {
        PortalAccessRules::authorize($this->portalUser(), PortalPermissions::STAFF_USER_ARCHIVE);
    }

    protected function authorizeInductionAdminIndex(): void
    {
        abort_unless(PortalAccessRules::canAccessInductionAdmin($this->portalUser()), 403);
    }

    protected function authorizeReadInductionPolicies(): void
    {
        PortalAccessRules::authorize($this->portalUser(), PortalPermissions::INDUCTION_POLICY_READ);
    }

    protected function authorizeCreateInductionPolicies(): void
    {
        PortalAccessRules::authorize($this->portalUser(), PortalPermissions::INDUCTION_POLICY_CREATE);
    }

    protected function authorizeUpdateInductionPolicies(): void
    {
        PortalAccessRules::authorize($this->portalUser(), PortalPermissions::INDUCTION_POLICY_UPDATE);
    }

    protected function authorizeArchiveInductionPolicies(): void
    {
        PortalAccessRules::authorize($this->portalUser(), PortalPermissions::INDUCTION_POLICY_ARCHIVE);
    }

    protected function authorizeReadInductionEnrollment(): void
    {
        PortalAccessRules::authorize($this->portalUser(), PortalPermissions::INDUCTION_ENROLLMENT_READ);
    }
}
