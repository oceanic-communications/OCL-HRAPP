<?php

namespace Tests\Unit;

use App\Models\InductionPolicy;
use App\Models\InductionSection;
use App\Support\InductionPolicyChangeNotification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InductionPolicyChangeNotificationTest extends TestCase
{
    #[Test]
    public function clause_new_notification_includes_policy_change_type_and_clause(): void
    {
        $policy = new InductionPolicy([
            'name' => 'Human Resources',
            'abbreviation' => 'HR',
        ]);

        $notification = InductionPolicyChangeNotification::clauseNew($policy, 'Leave entitlements');

        $this->assertSame('HR · New · Clause · Leave entitlements', $notification->notificationTitle());
        $this->assertStringContainsString('Policy: Human Resources (HR).', $notification->notificationBody(false));
        $this->assertStringContainsString('Change: New clause — "Leave entitlements".', $notification->notificationBody(false));
    }

    #[Test]
    public function sub_clause_amendment_notification_includes_parent_clause(): void
    {
        $policy = new InductionPolicy([
            'name' => 'IT Security',
            'abbreviation' => 'IT',
        ]);
        $section = new InductionSection(['title' => 'Access control']);

        $notification = InductionPolicyChangeNotification::subClauseAmendment($policy, $section, 'Password rules');

        $this->assertSame('IT · Amendment · Sub-clause · Access control · Password rules', $notification->notificationTitle());
        $this->assertStringContainsString('Amendment sub-clause', $notification->emailSubject());
        $this->assertStringContainsString('under clause "Access control"', $notification->summary());
    }
}
