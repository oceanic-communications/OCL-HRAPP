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

    public const INDUCTION_POLICY_MANAGE = 'induction.policy.manage';
}
