<?php

namespace Tests\Feature;

use App\Services\CreditLateFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditLateFeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_late_fee_configuration_is_disabled_by_policy(): void
    {
        $configuration = app(CreditLateFeeService::class)->configuration();

        $this->assertFalse($configuration['enabled']);
        $this->assertSame(0.0, $configuration['fixed_amount']);
        $this->assertSame(0.0, $configuration['percentage']);
        $this->assertStringContainsString('no cobra recargos', $configuration['policy']);
    }

    public function test_apply_late_fees_is_kept_as_noop_for_compatibility(): void
    {
        $result = app(CreditLateFeeService::class)->applyLateFees(today(), null, true, null);

        $this->assertFalse($result['enabled']);
        $this->assertSame(0, $result['applied']);
        $this->assertStringContainsString('no cobra recargos', $result['message']);
    }
}
