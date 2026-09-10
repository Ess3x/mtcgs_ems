<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceSingleStepUiTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_and_finance_ui_use_a_single_attendance_button(): void
    {
        $adminBlade = file_get_contents(base_path('resources/views/admin/biometric.blade.php'));
        $financeBlade = file_get_contents(base_path('resources/views/finance/dashboard.blade.php'));

        $this->assertSame(1, substr_count($adminBlade, 'onclick="processAdminAttendance()"'));
        $this->assertSame(1, substr_count($financeBlade, 'onclick="processFinanceAttendance()"'));
    }

    public function test_dashboard_recent_attendance_only_shows_today_records(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin-dashboard@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $employee = EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => 1,
            'employee_number' => 'EMP-1001',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'is_fingerprint_registered' => true,
        ]);

        AttendanceLog::create([
            'employee_id' => $employee->user_id,
            'employee_profile_id' => $employee->id,
            'branch_id' => 1,
            'attendance_date' => now()->subDay(),
            'am_in' => now()->subDay()->setTime(8, 0, 0),
            'status' => 'present',
        ]);

        AttendanceLog::create([
            'employee_id' => $employee->user_id,
            'employee_profile_id' => $employee->id,
            'branch_id' => 1,
            'attendance_date' => today(),
            'am_in' => now()->setTime(7, 30, 0),
            'status' => 'present',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('recentAttendance', function ($recentAttendance) {
            return count($recentAttendance) === 1 && $recentAttendance[0]['date'] === today()->format('M d, Y');
        });
    }
}
