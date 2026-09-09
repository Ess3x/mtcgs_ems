<?php

namespace Tests\Feature;

use Tests\TestCase;

class AttendanceSingleStepUiTest extends TestCase
{
    public function test_admin_and_finance_ui_use_a_single_attendance_button(): void
    {
        $adminBlade = file_get_contents(base_path('resources/views/admin/biometric.blade.php'));
        $financeBlade = file_get_contents(base_path('resources/views/finance/dashboard.blade.php'));

        $this->assertSame(1, substr_count($adminBlade, 'onclick="processAdminAttendance()"'));
        $this->assertSame(1, substr_count($financeBlade, 'onclick="processFinanceAttendance()"'));
    }
}
