<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\BiometricController;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class BiometricControllerTimeOutStatusTest extends TestCase
{
    public function test_early_time_out_is_treated_as_present_but_early_out(): void
    {
        $controller = new BiometricController();

        $result = $controller->evaluateTimeOutStatus(
            Carbon::parse('2026-08-29 16:30:00'),
            Carbon::parse('2026-08-29 17:00:00')
        );

        $this->assertSame('present', $result['status']);
        $this->assertTrue($result['is_early_out']);
        $this->assertSame(0.0, $result['overtime_hours']);
    }

    public function test_late_time_out_is_treated_as_overtime(): void
    {
        $controller = new BiometricController();

        $result = $controller->evaluateTimeOutStatus(
            Carbon::parse('2026-08-29 17:30:00'),
            Carbon::parse('2026-08-29 17:00:00')
        );

        $this->assertSame('present', $result['status']);
        $this->assertFalse($result['is_early_out']);
        $this->assertEquals(0.5, $result['overtime_hours']);
    }
}
