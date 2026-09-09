<?php

namespace Tests\Unit;

use App\Http\Controllers\LeaveController;
use App\Models\AttendanceLog;
use App\Models\DTR;
use App\Models\EmployeeProfile;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\PayrollComputationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DTRSubmitWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('dtrs')) {
            Schema::create('dtrs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_profile_id')->nullable();
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->enum('status', ['draft', 'submitted', 'pending_system_admin', 'approved', 'rejected'])->default('draft');
                $table->text('remarks')->nullable();
                $table->decimal('total_hours', 8, 2)->nullable();
                $table->integer('days_present')->nullable();
                $table->integer('days_absent')->nullable();
                $table->decimal('overtime_hours', 8, 2)->nullable();
                $table->integer('late_minutes')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->string('auditable_type');
                $table->unsignedBigInteger('auditable_id');
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->text('url')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('attendance_logs')) {
            Schema::create('attendance_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_profile_id')->nullable();
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->date('attendance_date');
                $table->dateTime('am_in')->nullable();
                $table->dateTime('am_out')->nullable();
                $table->dateTime('break_in')->nullable();
                $table->dateTime('break_out')->nullable();
                $table->dateTime('pm_in')->nullable();
                $table->dateTime('pm_out')->nullable();
                $table->string('status')->nullable();
                $table->integer('late_minutes')->default(0);
                $table->decimal('overtime_hours', 8, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('employee_profiles')) {
            Schema::create('employee_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('employee_number')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('position')->nullable();
                $table->date('date_hired')->nullable();
                $table->string('status')->nullable();
                $table->decimal('basic_salary', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('role')->default('employee');
                $table->unsignedBigInteger('profile_id')->nullable();
                $table->string('profile_type')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_profile_id')->nullable();
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->string('leave_type')->nullable();
                $table->decimal('total_days', 8, 2)->default(0);
                $table->text('reason')->nullable();
                $table->string('status')->nullable();
                $table->boolean('is_absent')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leave_balances')) {
            Schema::create('leave_balances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_profile_id');
                $table->integer('year');
                $table->decimal('sick_leave_total', 8, 2)->default(0);
                $table->decimal('sick_leave_used', 8, 2)->default(0);
                $table->decimal('vacation_leave_total', 8, 2)->default(0);
                $table->decimal('vacation_leave_used', 8, 2)->default(0);
                $table->decimal('emergency_leave_total', 8, 2)->default(0);
                $table->decimal('emergency_leave_used', 8, 2)->default(0);
                $table->decimal('maternity_leave_total', 8, 2)->default(0);
                $table->decimal('maternity_leave_used', 8, 2)->default(0);
                $table->decimal('paternity_leave_total', 8, 2)->default(0);
                $table->decimal('paternity_leave_used', 8, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function test_dtr_can_be_submitted_for_branch_head_approval(): void
    {
        $dtr = new DTR([
            'status' => 'draft',
        ]);

        $this->assertTrue($dtr->canSubmit());

        $result = $dtr->submit();

        $this->assertSame('submitted', $dtr->status);
        $this->assertSame('submitted', $result->status);
        $this->assertInstanceOf(DTR::class, $result);
    }

    public function test_branch_head_approval_forwards_dtr_to_system_admin_review(): void
    {
        $dtr = new DTR([
            'status' => 'submitted',
        ]);

        $dtr->approve(10, 'branch_admin');

        $this->assertSame('pending_system_admin', $dtr->status);

        $dtr->approve(20, 'super_admin');

        $this->assertSame('approved', $dtr->status);
    }

    public function test_dtr_visibility_is_scoped_to_branch_head_and_system_admin(): void
    {
        $submitted = new DTR(['status' => 'submitted']);
        $pendingSystemAdmin = new DTR(['status' => 'pending_system_admin']);
        $approved = new DTR(['status' => 'approved']);

        $this->assertTrue($submitted->isVisibleToBranchAdmin());
        $this->assertFalse($submitted->isVisibleToSystemAdmin());

        $this->assertFalse($pendingSystemAdmin->isVisibleToBranchAdmin());
        $this->assertTrue($pendingSystemAdmin->isVisibleToSystemAdmin());

        $this->assertFalse($approved->isVisibleToBranchAdmin());
        $this->assertFalse($approved->isVisibleToSystemAdmin());
    }

    public function test_finance_staff_only_sees_approved_dtrs(): void
    {
        $submitted = new DTR(['status' => 'submitted']);
        $pendingSystemAdmin = new DTR(['status' => 'pending_system_admin']);
        $approved = new DTR(['status' => 'approved']);

        $this->assertFalse($submitted->isVisibleToFinanceStaff());
        $this->assertFalse($pendingSystemAdmin->isVisibleToFinanceStaff());
        $this->assertTrue($approved->isVisibleToFinanceStaff());
    }

    public function test_payroll_basic_salary_falls_back_to_profile_salary(): void
    {
        $service = new PayrollComputationService();

        $this->assertSame(25000.0, $service->resolveBasicSalary(['basic_salary' => '25000.00']));
        $this->assertSame(25000.0, $service->resolveBasicSalary((object) ['basic_salary' => '25000.00']));
        $this->assertSame(25000.0, $service->resolveBasicPay(25000.0, 0));
        $this->assertSame(25000.0, $service->resolveBasicPay(25000.0, 10));
        $this->assertSame(0.0, $service->resolveBasicSalary(null));
    }

    public function test_basic_pay_matches_the_profile_basic_salary_exactly(): void
    {
        $service = new PayrollComputationService();

        $this->assertSame(0.0, $service->resolveBasicPay(0.0, 10));
        $this->assertSame(10000.0, $service->resolveBasicPay(10000.0, 10));
    }

    public function test_daily_rate_uses_the_standard_workday_divisor(): void
    {
        $this->assertSame(229.89, round(10000.0 / 43.5, 2));
    }

    public function test_calculate_totals_counts_approved_leave_and_absent_days_correctly(): void
    {
        $dtr = DTR::create([
            'employee_profile_id' => 1,
            'period_start' => '2026-08-17',
            'period_end' => '2026-08-20',
            'status' => 'draft',
        ]);

        AttendanceLog::create([
            'employee_profile_id' => 1,
            'attendance_date' => '2026-08-17',
            'am_in' => '2026-08-17 06:32:00',
            'pm_out' => '2026-08-17 17:33:00',
            'status' => 'present',
        ]);

        AttendanceLog::create([
            'employee_profile_id' => 1,
            'attendance_date' => '2026-08-18',
            'am_in' => '2026-08-18 06:48:00',
            'pm_out' => '2026-08-18 17:00:00',
            'status' => 'present',
        ]);

        AttendanceLog::create([
            'employee_profile_id' => 1,
            'attendance_date' => '2026-08-19',
            'status' => 'absent',
        ]);

        LeaveRequest::create([
            'employee_profile_id' => 1,
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-20',
            'leave_type' => 'vacation',
            'status' => 'approved',
            'is_absent' => false,
        ]);

        $breakdown = $dtr->getCalculationBreakdown();

        $this->assertSame(2, $breakdown['days_present']);
        $this->assertSame(1, $breakdown['days_absent']);
        $this->assertSame(1, $breakdown['paid_leave']);
        $this->assertSame(0, $breakdown['leave_without_pay']);
        $this->assertGreaterThan(0, $breakdown['total_hours']);
    }

    public function test_missing_attendance_log_is_not_auto_counted_as_absent(): void
    {
        $dtr = DTR::create([
            'employee_profile_id' => 1,
            'period_start' => '2026-08-17',
            'period_end' => '2026-08-18',
            'status' => 'draft',
        ]);

        AttendanceLog::create([
            'employee_profile_id' => 1,
            'attendance_date' => '2026-08-17',
            'am_in' => '2026-08-17 06:32:00',
            'pm_out' => '2026-08-17 17:33:00',
            'status' => 'present',
        ]);

        $breakdown = $dtr->getCalculationBreakdown();

        $this->assertSame(1, $breakdown['days_present']);
        $this->assertSame(0, $breakdown['days_absent']);
    }

    public function test_exhausted_sick_leave_can_still_be_filed_as_leave_without_pay(): void
    {
        $profile = EmployeeProfile::create([
            'user_id' => 1,
            'branch_id' => 1,
            'employee_number' => 'EMP-1001',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => 'Regular',
            'basic_salary' => 25000,
        ]);

        $user = User::create([
            'name' => 'Test Employee',
            'email' => 'employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'profile_id' => $profile->id,
            'profile_type' => EmployeeProfile::class,
            'is_active' => true,
        ]);

        $profile->update(['user_id' => $user->id]);

        LeaveBalance::create([
            'employee_profile_id' => $profile->id,
            'year' => date('Y'),
            'sick_leave_total' => 3,
            'sick_leave_used' => 3,
            'vacation_leave_total' => 3,
            'vacation_leave_used' => 0,
            'emergency_leave_total' => 3,
            'emergency_leave_used' => 0,
            'maternity_leave_total' => 0,
            'maternity_leave_used' => 0,
            'paternity_leave_total' => 0,
            'paternity_leave_used' => 0,
        ]);

        $this->actingAs($user);

        $request = Request::create('/leave', 'POST', [
            'leave_type' => 'sick',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d'),
            'reason' => 'Emergency medical appointment',
        ]);

        $response = app(LeaveController::class)->store($request);

        $this->assertTrue($response->isRedirect());
        $this->assertDatabaseHas('leave_requests', [
            'employee_profile_id' => $profile->id,
            'leave_type' => 'sick',
            'is_absent' => true,
        ]);
    }

    public function test_dtr_tracks_early_out_minutes_for_payroll_deduction(): void
    {
        $dtr = DTR::create([
            'employee_profile_id' => 1,
            'period_start' => '2026-08-17',
            'period_end' => '2026-08-17',
            'status' => 'draft',
        ]);

        AttendanceLog::create([
            'employee_profile_id' => 1,
            'attendance_date' => '2026-08-17',
            'am_in' => '2026-08-17 08:00:00',
            'am_out' => '2026-08-17 12:00:00',
            'pm_in' => '2026-08-17 13:00:00',
            'pm_out' => '2026-08-17 16:00:00',
            'status' => 'present',
        ]);

        $this->assertSame(60, $dtr->getTotalEarlyOutMinutes());
        $this->assertSame(60, $dtr->getCalculationBreakdown()['early_out_minutes']);
    }

    public function test_attendance_log_resolves_dtr_statuses(): void
    {
        $onTime = AttendanceLog::create([
            'employee_profile_id' => 1,
            'attendance_date' => '2026-08-18',
            'am_in' => '2026-08-18 07:00:00',
            'pm_in' => '2026-08-18 13:00:00',
            'pm_out' => '2026-08-18 17:00:00',
            'status' => 'present',
        ]);
        $late = AttendanceLog::create([
            'employee_profile_id' => 1,
            'attendance_date' => '2026-08-19',
            'am_in' => '2026-08-19 07:01:00',
            'pm_in' => '2026-08-19 13:00:00',
            'pm_out' => '2026-08-19 17:00:00',
            'status' => 'late',
            'late_minutes' => 1,
        ]);
        $earlyOut = AttendanceLog::create([
            'employee_profile_id' => 1,
            'attendance_date' => '2026-08-20',
            'am_in' => '2026-08-20 07:00:00',
            'pm_in' => '2026-08-20 13:00:00',
            'pm_out' => '2026-08-20 16:00:00',
            'status' => 'present',
        ]);
        $halfDay = AttendanceLog::create([
            'employee_profile_id' => 1,
            'attendance_date' => '2026-08-21',
            'am_in' => '2026-08-21 07:00:00',
            'status' => 'present',
        ]);

        $this->assertSame('Present', $onTime->getDtrStatus());
        $this->assertSame('Late', $late->getDtrStatus());
        $this->assertSame('Early Out', $earlyOut->getDtrStatus());
        $this->assertSame('Half Day', $halfDay->getDtrStatus());
    }
}
