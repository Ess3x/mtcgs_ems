<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\BiometricController;
use App\Http\Controllers\DTRController;
use App\Http\Controllers\Admin\PayrollController;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\DTR;
use App\Models\EmployeeProfile;
use App\Models\FinanceProfile;
use App\Models\LeaveRequest;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\WorkingDayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FinanceFingerprintDtrGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payslip_view_includes_attendance_summary_stats(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-PAYSLIP-SUM',
            'branch_name' => 'Payslip Summary Branch',
            'address' => 'Payslip Summary Address',
        ]);

        $adminUser = User::create([
            'name' => 'Payslip Admin',
            'email' => 'payslip.admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeUser = User::create([
            'name' => 'Payslip Employee',
            'email' => 'payslip.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeProfile = EmployeeProfile::create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-PAYSLIP-001',
            'first_name' => 'Payslip',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'basic_salary' => 25000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $period = PayrollPeriod::create([
            'branch_id' => $branch->id,
            'period_code' => 'P-2026-09',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'payment_date' => now()->endOfMonth()->addDay(),
            'status' => 'draft',
        ]);

        $dtr = DTR::create([
            'employee_profile_id' => $employeeProfile->id,
            'period_start' => $period->start_date,
            'period_end' => $period->end_date,
            'status' => 'draft',
            'days_present' => 3,
            'days_absent' => 1,
            'late_minutes' => 30,
            'early_out_minutes' => 15,
            'paid_leave_days' => 2,
            'leave_without_pay_days' => 1,
        ]);

        $entry = PayrollEntry::create([
            'payroll_period_id' => $period->id,
            'employee_profile_id' => $employeeProfile->id,
            'branch_id' => $branch->id,
            'dtr_id' => $dtr->id,
            'basic_pay' => 25000,
            'days_present' => 3,
            'days_absent' => 1,
            'gross_pay' => 25000,
            'total_deductions' => 0,
            'net_pay' => 25000,
            'status' => 'draft',
            'payroll_breakdown' => json_encode([
                'days_present' => 3,
                'days_absent' => 1,
                'late_minutes' => 30,
                'early_out_minutes' => 15,
                'paid_leave' => 2,
                'leave_without_pay' => 1,
                'daily_rate' => 1250,
                'total_daily_rate' => 3750,
            ]),
        ]);

        $this->actingAs($adminUser);
        $response = app(PayrollController::class)->viewPayslip($entry->id);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertSame(3, $response->getData()['dtrStats']['days_present']);
        $this->assertSame(1, $response->getData()['dtrStats']['days_absent']);
        $this->assertSame(30, $response->getData()['dtrStats']['late_minutes']);
        $this->assertSame(15, $response->getData()['dtrStats']['early_out_minutes']);
        $this->assertSame(2, $response->getData()['dtrStats']['paid_leave']);
        $this->assertSame(1, $response->getData()['dtrStats']['leave_without_pay']);
    }

    public function test_finance_fingerprint_attendance_generates_current_dtr_record(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-DTR-FIN',
            'branch_name' => 'Finance DTR Branch',
            'address' => 'Finance DTR Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Employee Mirror',
            'email' => 'employee.mirror@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeProfile = EmployeeProfile::create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-DTR-001',
            'first_name' => 'Finance',
            'last_name' => 'Mirror',
            'position' => 'Accountant',
            'basic_salary' => 25000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $financeUser = User::create([
            'name' => 'Finance Officer',
            'email' => 'finance.dtr@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance_officer',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $financeProfile = FinanceProfile::create([
            'user_id' => $financeUser->id,
            'employee_profile_id' => $employeeProfile->id,
            'branch_id' => $branch->id,
            'employee_number' => 'FIN-DTR-001',
            'first_name' => 'Finance',
            'last_name' => 'Officer',
            'position' => 'Finance Officer',
            'basic_salary' => 35000,
            'date_hired' => now(),
            'fingerprint_template' => 'sample-fingerprint-template',
            'is_fingerprint_registered' => true,
        ]);

        $financeUser->profile_id = $financeProfile->id;
        $financeUser->profile_type = FinanceProfile::class;
        $financeUser->save();

        $request = Request::create('/api/biometric/finance-attendance', 'POST', [
            'fingerprint_data' => 'sample-fingerprint-template',
            'timestamp' => now()->setTime(8, 15)->format('m/d/Y h:i:s A'),
        ]);

        $response = (new BiometricController())->processFinanceAttendance($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('attendance_logs', [
            'employee_profile_id' => $employeeProfile->id,
            'attendance_date' => today()->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('dtrs', [
            'employee_profile_id' => $employeeProfile->id,
        ]);
    }

    public function test_scanner_time_clock_generates_current_dtr_record(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-DTR-SCANNER',
            'branch_name' => 'Scanner DTR Branch',
            'address' => 'Scanner DTR Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Scanner Employee',
            'email' => 'scanner.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employee = EmployeeProfile::create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-SCANNER-001',
            'first_name' => 'Scanner',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'basic_salary' => 25000,
            'date_hired' => now(),
            'fingerprint_template' => 'scanner-template',
            'is_fingerprint_registered' => true,
        ]);

        $request = Request::create('/api/biometric/time-clock', 'POST', [
            'fingerprint_data' => 'scanner-template',
            'action' => 'TIME-IN',
        ]);

        $response = (new BiometricController())->processTimeClock($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('attendance_logs', [
            'employee_profile_id' => $employee->id,
            'attendance_date' => today()->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('dtrs', [
            'employee_profile_id' => $employee->id,
        ]);
    }

    public function test_attendance_date_selects_the_correct_cutoff_period(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-DTR-CUTOFF',
            'branch_name' => 'Cutoff DTR Branch',
            'address' => 'Cutoff DTR Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Cutoff Employee',
            'email' => 'cutoff.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employee = EmployeeProfile::create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-CUTOFF-001',
            'first_name' => 'Cutoff',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'basic_salary' => 25000,
            'date_hired' => now(),
        ]);

        $firstCutoff = DTR::generateForCurrentPeriod($employee->id, '2026-09-15');
        $secondCutoff = DTR::generateForCurrentPeriod($employee->id, '2026-09-16');

        $this->assertSame('2026-09-01', $firstCutoff->period_start->toDateString());
        $this->assertSame('2026-09-15', $firstCutoff->period_end->toDateString());
        $this->assertSame('2026-09-16', $secondCutoff->period_start->toDateString());
        $this->assertSame('2026-09-30', $secondCutoff->period_end->toDateString());
    }

    public function test_philippine_holidays_are_excluded_from_working_days(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-PH-HOLIDAY',
            'branch_name' => 'Philippine Holiday Branch',
            'address' => 'Philippine Holiday Address',
        ]);

        $service = app(WorkingDayService::class);

        $this->assertTrue($service->isHoliday('2026-05-01', $branch->id));

        CalendarEvent::create([
            'title' => 'Branch Anniversary',
            'event_date' => '2026-08-18',
            'event_type' => 'holiday',
            'created_by' => User::factory()->create()->id,
            'branch_id' => $branch->id,
        ]);

        $this->assertFalse($service->isWorkingDay('2026-08-18', $branch->id));
        $this->assertTrue($service->isWorkingDay('2026-08-19', $branch->id));
        $this->assertSame(4, $service->countWorkingDays('2026-08-17', '2026-08-21', $branch->id));
    }

    public function test_late_attendance_renders_as_late_in_dtr_status(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-DTR-LATE',
            'branch_name' => 'Late DTR Branch',
            'address' => 'Late DTR Address',
        ]);

        $user = User::create([
            'name' => 'Late Employee',
            'email' => 'late.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeProfile = EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-LATE-001',
            'first_name' => 'Late',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'basic_salary' => 20000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $user->profile_id = $employeeProfile->id;
        $user->profile_type = EmployeeProfile::class;
        $user->save();

        $dtr = DTR::create([
            'employee_profile_id' => $employeeProfile->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth()->day <= 15 ? now()->day(15) : now()->endOfMonth(),
            'status' => 'draft',
        ]);

        AttendanceLog::create([
            'employee_profile_id' => $employeeProfile->id,
            'employee_id' => $user->id,
            'branch_id' => $branch->id,
            'attendance_date' => now()->toDateString(),
            'am_in' => now()->setTime(7, 31),
            'pm_out' => now()->setTime(16, 30),
            'status' => 'late',
            'late_minutes' => 31,
            'verification_method' => 'fingerprint',
        ]);

        $this->actingAs($user);

        $view = app(DTRController::class)->show($dtr->id);
        $html = $view->render();

        $this->assertStringContainsString('Late', $html);
        $this->assertStringNotContainsString('>Present<', $html);
        $this->assertStringContainsString('1/1', $html);
        $this->assertStringContainsString('Late: 31 min', $html);
        $this->assertStringContainsString('Early Out: 30 min', $html);
    }

    public function test_show_refreshes_stale_dtr_totals_from_live_logs(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-DTR-REFRESH-SHOW',
            'branch_name' => 'Refresh DTR Show Branch',
            'address' => 'Refresh DTR Show Address',
        ]);

        $user = User::create([
            'name' => 'Refresh Show Employee',
            'email' => 'refresh.show@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeProfile = EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-SHOW-REFRESH-001',
            'first_name' => 'Refresh',
            'last_name' => 'Show',
            'position' => 'Staff',
            'basic_salary' => 20000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $dtr = DTR::create([
            'employee_profile_id' => $employeeProfile->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'draft',
            'days_present' => 0,
            'late_minutes' => 0,
        ]);

        AttendanceLog::create([
            'employee_profile_id' => $employeeProfile->id,
            'employee_id' => $user->id,
            'branch_id' => $branch->id,
            'attendance_date' => now()->toDateString(),
            'am_in' => now()->setTime(7, 31),
            'pm_out' => now()->setTime(16, 30),
            'status' => 'late',
            'late_minutes' => 31,
            'overtime_hours' => 0,
            'verification_method' => 'fingerprint',
        ]);

        $this->actingAs($user);
        app(DTRController::class)->show($dtr->id);

        $this->assertSame(1, $dtr->fresh()->days_present);
        $this->assertSame(31, $dtr->fresh()->late_minutes);
    }

    public function test_invalid_late_minutes_outside_shift_window_are_ignored(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-DTR-INVALID-LATE',
            'branch_name' => 'Invalid Late Branch',
            'address' => 'Invalid Late Address',
        ]);

        $user = User::create([
            'name' => 'Invalid Late Employee',
            'email' => 'invalid.late@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeProfile = EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-INVALID-LATE-001',
            'first_name' => 'Invalid',
            'last_name' => 'Late',
            'position' => 'Staff',
            'basic_salary' => 20000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $dtr = DTR::create([
            'employee_profile_id' => $employeeProfile->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'draft',
            'days_present' => 0,
            'late_minutes' => 0,
        ]);

        AttendanceLog::create([
            'employee_profile_id' => $employeeProfile->id,
            'employee_id' => $user->id,
            'branch_id' => $branch->id,
            'attendance_date' => now()->toDateString(),
            'am_in' => now()->setTime(21, 20, 11),
            'pm_out' => null,
            'status' => 'late',
            'late_minutes' => 800,
            'verification_method' => 'fingerprint',
        ]);

        $this->assertSame(0, $dtr->fresh()->getTotalLateMinutes());
    }

    public function test_leave_without_pay_is_not_counted_as_absent_in_dtr_breakdown(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-DTR-LWOP',
            'branch_name' => 'LWOP DTR Branch',
            'address' => 'LWOP DTR Address',
        ]);

        $user = User::create([
            'name' => 'LWOP Employee',
            'email' => 'lwop.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeProfile = EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-LWOP-001',
            'first_name' => 'LWOP',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'basic_salary' => 20000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $dtr = DTR::create([
            'employee_profile_id' => $employeeProfile->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'draft',
            'days_present' => 0,
            'days_absent' => 0,
            'late_minutes' => 0,
        ]);

        LeaveRequest::create([
            'employee_id' => $user->id,
            'employee_profile_id' => $employeeProfile->id,
            'leave_type' => 'vacation',
            'start_date' => now()->startOfMonth()->addDays(3),
            'end_date' => now()->startOfMonth()->addDays(3),
            'total_days' => 1,
            'reason' => 'Leave without pay test',
            'status' => 'approved',
            'is_absent' => true,
        ]);

        $breakdown = $dtr->fresh()->getCalculationBreakdown();

        $this->assertSame(0, $breakdown['days_absent']);
        $this->assertSame(1, $breakdown['leave_without_pay']);
        $this->assertEquals(0, $breakdown['days_absent']);
    }

    public function test_payroll_entry_syncs_stale_breakdown_to_live_dtr_values(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-PAYROLL-SYNC',
            'branch_name' => 'Payroll Sync Branch',
            'address' => 'Payroll Sync Address',
        ]);

        $user = User::create([
            'name' => 'Sync Employee',
            'email' => 'sync.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeProfile = EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-PAYROLL-SYNC-001',
            'first_name' => 'Sync',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'basic_salary' => 20000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $dtr = DTR::create([
            'employee_profile_id' => $employeeProfile->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'draft',
            'days_present' => 0,
            'days_absent' => 9,
            'late_minutes' => 0,
        ]);

        $payrollPeriod = PayrollPeriod::create([
            'period_code' => 'PRD-SYNC-001',
            'period_type' => 'monthly',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'cutoff_date' => now()->endOfMonth(),
            'payment_date' => now()->endOfMonth()->addDay(),
            'status' => 'draft',
        ]);

        AttendanceLog::create([
            'employee_profile_id' => $employeeProfile->id,
            'employee_id' => $user->id,
            'branch_id' => $branch->id,
            'attendance_date' => now()->toDateString(),
            'am_in' => now()->setTime(7, 31),
            'pm_out' => now()->setTime(16, 30),
            'status' => 'late',
            'late_minutes' => 31,
            'verification_method' => 'fingerprint',
        ]);

        LeaveRequest::create([
            'employee_id' => $user->id,
            'employee_profile_id' => $employeeProfile->id,
            'leave_type' => 'vacation',
            'start_date' => now()->startOfMonth()->addDays(3),
            'end_date' => now()->startOfMonth()->addDays(3),
            'total_days' => 1,
            'reason' => 'Vacation',
            'status' => 'approved',
            'is_absent' => false,
        ]);

        $adminUser = User::create([
            'name' => 'Payroll Admin',
            'email' => 'payroll.admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $entry = PayrollEntry::create([
            'payroll_period_id' => $payrollPeriod->id,
            'employee_profile_id' => $employeeProfile->id,
            'branch_id' => $branch->id,
            'dtr_id' => $dtr->id,
            'days_present' => 0,
            'days_absent' => 10,
            'basic_pay' => 20000,
            'gross_pay' => 0,
            'net_pay' => 0,
            'status' => 'draft',
            'payroll_breakdown' => json_encode(['days_present' => 0, 'days_absent' => 10, 'late_minutes' => 0]),
        ]);

        $this->actingAs($adminUser);
        app(PayrollController::class)->editEntry($entry->id);

        $fresh = $entry->fresh();
        $this->assertSame(1, $fresh->days_present);
        $this->assertSame(0, $fresh->days_absent);

        $breakdown = json_decode($fresh->payroll_breakdown, true);
        $this->assertSame(1, $breakdown['days_present']);
        $this->assertSame(0, $breakdown['days_absent']);
        $this->assertArrayHasKey('paid_leave', $breakdown);
        $this->assertArrayHasKey('leave_without_pay', $breakdown);
        $this->assertGreaterThanOrEqual(0, $fresh->absent_deduction ?? 0);
    }

    public function test_dtr_getters_recalculate_from_live_attendance_logs(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-DTR-REFRESH',
            'branch_name' => 'Refresh DTR Branch',
            'address' => 'Refresh DTR Address',
        ]);

        $user = User::create([
            'name' => 'Refresh Employee',
            'email' => 'refresh.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeProfile = EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-REFRESH-001',
            'first_name' => 'Refresh',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'basic_salary' => 20000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $dtr = DTR::create([
            'employee_profile_id' => $employeeProfile->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'draft',
            'days_present' => 0,
            'late_minutes' => 0,
        ]);

        AttendanceLog::create([
            'employee_profile_id' => $employeeProfile->id,
            'employee_id' => $user->id,
            'branch_id' => $branch->id,
            'attendance_date' => now()->toDateString(),
            'am_in' => now()->setTime(7, 31),
            'pm_out' => now()->setTime(16, 30),
            'status' => 'late',
            'late_minutes' => 31,
            'overtime_hours' => 0,
            'verification_method' => 'fingerprint',
        ]);

        $this->assertSame(1, $dtr->getDaysPresent());
        $this->assertSame(31, $dtr->getTotalLateMinutes());
        $this->assertSame(1, $dtr->getCalculationBreakdown()['days_present']);
    }
}
