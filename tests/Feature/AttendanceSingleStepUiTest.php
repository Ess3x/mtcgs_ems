<?php

namespace Tests\Feature;

use App\Models\AdminProfile;
use App\Models\AttendanceLog;
use App\Models\Branch;
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

    public function test_biometric_registration_requires_explicit_confirmation_before_submitting(): void
    {
        $adminBlade = file_get_contents(base_path('resources/views/admin/biometric.blade.php'));
        $financeBlade = file_get_contents(base_path('resources/views/finance/dashboard.blade.php'));

        $this->assertStringContainsString('confirm(', $adminBlade);
        $this->assertStringContainsString('confirm(', $financeBlade);
    }

    public function test_biometric_employee_list_includes_registered_and_unregistered_users(): void
    {
        $user = User::create([
            'name' => 'System Admin',
            'email' => 'biometric-list@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => 1,
            'employee_number' => 'EMP-2001',
            'first_name' => 'Registered',
            'last_name' => 'User',
            'position' => 'Staff',
            'date_hired' => now()->subMonths(6)->toDateString(),
            'is_fingerprint_registered' => true,
        ]);

        EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => 1,
            'employee_number' => 'EMP-2002',
            'first_name' => 'Unregistered',
            'last_name' => 'User',
            'position' => 'Staff',
            'date_hired' => now()->subMonths(6)->toDateString(),
            'is_fingerprint_registered' => false,
        ]);

        $response = $this->actingAs($user)->getJson('/api/biometric/unregistered-employees');

        $response->assertOk();
        $response->assertJsonPath('count', 2);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_biometric_employee_list_returns_branch_grouping_metadata(): void
    {
        $branchA = Branch::create(['branch_code' => 'MTC-001', 'branch_name' => 'MTC-IRIGA', 'address' => 'Iriga']);
        $branchB = Branch::create(['branch_code' => 'MTC-002', 'branch_name' => 'MTC-BUHI', 'address' => 'Buhi']);

        $user = User::create([
            'name' => 'System Admin',
            'email' => 'biometric-branch@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => $branchA->id,
            'employee_number' => 'EMP-3001',
            'first_name' => 'Branch',
            'last_name' => 'A',
            'position' => 'Staff',
            'date_hired' => now()->subMonths(6)->toDateString(),
            'is_fingerprint_registered' => false,
        ]);

        EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => $branchB->id,
            'employee_number' => 'EMP-3002',
            'first_name' => 'Branch',
            'last_name' => 'B',
            'position' => 'Staff',
            'date_hired' => now()->subMonths(6)->toDateString(),
            'is_fingerprint_registered' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/biometric/unregistered-employees');

        $response->assertOk();
        $this->assertNotNull($response->json('data.0.branch_name'));
        $this->assertNotNull($response->json('data.1.branch_name'));
        $this->assertEquals('MTC-IRIGA', $response->json('data.0.branch_name'));
    }

    public function test_branch_admin_stats_only_count_branch_admins_not_hr_for_same_branch(): void
    {
        $branch = Branch::create(['branch_code' => 'MTC-300', 'branch_name' => 'MTC-IRIGA', 'address' => 'Iriga']);

        $branchAdminUser = User::create([
            'name' => 'Iriga Branch Admin',
            'email' => 'iriga-branch-admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $hrUser = User::create([
            'name' => 'Iriga HR',
            'email' => 'iriga-hr@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'admin_type' => 'hr',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        AdminProfile::create([
            'user_id' => $branchAdminUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'ADM-1001',
            'first_name' => 'Iriga',
            'last_name' => 'Branch Admin',
            'position' => 'Branch Administrator',
            'date_hired' => now()->subMonths(6)->toDateString(),
            'is_fingerprint_registered' => true,
        ]);

        AdminProfile::create([
            'user_id' => $hrUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'ADM-1002',
            'first_name' => 'Iriga',
            'last_name' => 'HR',
            'position' => 'Human Resources',
            'date_hired' => now()->subMonths(6)->toDateString(),
            'is_fingerprint_registered' => true,
        ]);

        $count = AdminProfile::whereHas('user', function ($q) {
            $q->where('is_active', true)
              ->where(function ($sub) {
                  $sub->where('role', 'admin')->where('admin_type', 'branch_admin');
              });
        })->where('branch_id', $branch->id)->count();

        $this->assertSame(1, $count);
    }

    public function test_branch_list_shows_total_users_per_branch(): void
    {
        $branchA = Branch::create(['branch_code' => 'MTC-101', 'branch_name' => 'MTC-IRIGA', 'address' => 'Iriga']);
        $branchB = Branch::create(['branch_code' => 'MTC-102', 'branch_name' => 'MTC-BUHI', 'address' => 'Buhi']);

        $userA1 = User::create([
            'name' => 'Branch A1',
            'email' => 'branch-a1@test.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $userA2 = User::create([
            'name' => 'Branch A2',
            'email' => 'branch-a2@test.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $userB1 = User::create([
            'name' => 'Branch B1',
            'email' => 'branch-b1@test.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        EmployeeProfile::create([
            'user_id' => $userA1->id,
            'branch_id' => $branchA->id,
            'employee_number' => 'EMP-4001',
            'first_name' => 'Branch',
            'last_name' => 'A1',
            'position' => 'Staff',
            'date_hired' => now()->subMonths(6)->toDateString(),
            'is_fingerprint_registered' => false,
        ]);

        EmployeeProfile::create([
            'user_id' => $userA2->id,
            'branch_id' => $branchA->id,
            'employee_number' => 'EMP-4002',
            'first_name' => 'Branch',
            'last_name' => 'A2',
            'position' => 'Staff',
            'date_hired' => now()->subMonths(6)->toDateString(),
            'is_fingerprint_registered' => true,
        ]);

        EmployeeProfile::create([
            'user_id' => $userB1->id,
            'branch_id' => $branchB->id,
            'employee_number' => 'EMP-4003',
            'first_name' => 'Branch',
            'last_name' => 'B1',
            'position' => 'Staff',
            'date_hired' => now()->subMonths(6)->toDateString(),
            'is_fingerprint_registered' => true,
        ]);

        $this->assertSame(2, $branchA->employeeCount());
        $this->assertSame(1, $branchB->employeeCount());
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
            'position' => 'Staff',
            'date_hired' => now()->subMonths(6)->toDateString(),
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
