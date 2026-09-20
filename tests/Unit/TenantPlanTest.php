<?php

namespace Tests\Unit;

use App\Models\Tenant;
use Tests\TestCase;

class TenantPlanTest extends TestCase
{
    private function tenant(string $status, ?string $trialEnds = null): Tenant
    {
        $t = new Tenant();
        $t->plan_status   = $status;
        $t->trial_ends_at = $trialEnds ? new \DateTimeImmutable($trialEnds) : null;

        return $t;
    }

    public function test_active_plan_is_active(): void
    {
        $this->assertTrue($this->tenant('active')->isActive());
    }

    public function test_trial_is_active_only_until_it_ends(): void
    {
        $this->assertTrue($this->tenant('trial', '+5 days')->isActive());
        $this->assertFalse($this->tenant('trial', '-1 day')->isActive());
    }

    public function test_suspended_and_cancelled_are_inactive(): void
    {
        $this->assertFalse($this->tenant('suspended')->isActive());
        $this->assertFalse($this->tenant('cancelled')->isActive());
    }
}
