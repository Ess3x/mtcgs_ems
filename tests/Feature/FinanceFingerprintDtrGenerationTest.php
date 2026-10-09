<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\BiometricController;
use App\Http\Controllers\DTRController;
use App\Http\Controllers\Admin\PayrollController;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\CashCharge;
use App\Models\DTR;
use App\Models\Device;
use App\Models\EmployeeProfile;
use App\Models\FinanceProfile;
use App\Models\LeaveRequest;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\WorkingDayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FinanceFingerprintDtrGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_holiday_bonus_is_shown_on_web_and_pdf_payslips(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-PAYSLIP-BONUS',
            'branch_name' => 'Payslip Bonus Branch',
            'address' => 'Payslip Bonus Address',
        ]);
        $admin = User::create([
            'name' => 'Payslip Bonus Admin',
            'email' => 'payslip-bonus-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $employeeUser = User::create([
            'name' => 'Payslip Bonus Employee',
            'email' => 'payslip-bonus-employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $profile = EmployeeProfile::create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-PAYSLIP-BONUS',
            'first_name' => 'Payslip Bonus',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'basic_salary' => 10000,
            'date_hired' => now(),
        ]);
        $period = PayrollPeriod::create([
            'branch_id' => $branch->id,
            'period_code' => 'PRD-PAYSLIP-BONUS',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'payment_date' => now()->endOfMonth()->addDay(),
            'status' => 'approved',
        ]);
        $entry = PayrollEntry::create([
            'payroll_period_id' => $period->id,
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'basic_pay' => 10000,
            'gross_pay' => 10000,
            'total_deductions' => 0,
            'net_pay' => 10250,
            'status' => 'approved',
            'payroll_breakdown' => json_encode([
                'daily_rate' => 500,
                'holiday_bonus' => 250,
            ]),
        ]);

        $this->actingAs($admin);
        $webView = app(PayrollController::class)->viewPayslip($entry->id);
        $webHtml = $webView->render();
        $pdfHtml = view('pdf.payslip', [
            'entry' => $entry->fresh(['employee', 'employeeProfile.branch', 'payrollPeriod', 'branch']),
            'employeeSignature' => null,
            'financeOfficerSignature' => null,
            'branchHeadSignature' => null,
        ])->render();

        $this->assertStringContainsString('Holiday Bonus', $webHtml);
        $this->assertStringContainsString('₱250.00', $webHtml);
        $this->assertStringContainsString('Total Half Day', $webHtml);
        $this->assertStringContainsString('Total Holiday', $webHtml);
        $this->assertStringContainsString('Total Suspension', $webHtml);
        $this->assertStringContainsString('Total Suspended Hours', $webHtml);
        $this->assertStringContainsString('Holiday Bonus', $pdfHtml);
        $this->assertStringContainsString('₱250.00', $pdfHtml);
        $this->assertStringContainsString('Total Half Day', $pdfHtml);
        $this->assertStringContainsString('Total Holiday', $pdfHtml);
        $this->assertStringContainsString('Total Suspension', $pdfHtml);
        $this->assertStringContainsString('Total Suspended Hours', $pdfHtml);
    }

    public function test_payslip_is_published_and_visible_when_email_delivery_fails(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-PAYSLIP-MAIL-ERROR',
            'branch_name' => 'Payslip Mail Error Branch',
            'address' => 'Payslip Mail Error Address',
        ]);
        $financeOfficer = User::create([
            'name' => 'Mail Error Finance Officer',
            'email' => 'mail-error.finance@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance_officer',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $employeeUser = User::create([
            'name' => 'Payslip Mail Recipient',
            'email' => 'mail-recipient@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $profile = EmployeeProfile::create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-PAYSLIP-MAIL-ERROR',
            'first_name' => 'Payslip Mail',
            'last_name' => 'Recipient',
            'position' => 'Staff',
            'basic_salary' => 10000,
            'date_hired' => now(),
        ]);
        $period = PayrollPeriod::create([
            'branch_id' => $branch->id,
            'period_code' => 'PRD-PAYSLIP-MAIL-ERROR',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'payment_date' => now()->endOfMonth()->addDay(),
            'status' => 'completed',
            'finance_submitted_at' => now(),
        ]);
        $entry = PayrollEntry::create([
            'payroll_period_id' => $period->id,
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'basic_pay' => 10000,
            'gross_pay' => 10000,
            'total_deductions' => 0,
            'net_pay' => 10000,
            'status' => 'approved',
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->with($employeeUser->email)
            ->andThrow(new \Symfony\Component\Mailer\Exception\TransportException('SMTP certificate is not yet valid.'));

        $response = $this->actingAs($financeOfficer)
            ->from(route('admin.payroll.entries', $period->id))
            ->post(route('admin.payroll.submit-payslip', $entry->id));

        $response->assertRedirect(route('admin.payroll.entries', $period->id));
        $response->assertSessionHas('error');
        $this->assertNotNull($entry->fresh()->payslip_published_at);
        $this->assertNull($entry->fresh()->payslip_sent_at);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $employeeUser->id,
            'type' => \App\Notifications\SystemNotification::class,
        ]);

        $this->actingAs($employeeUser);
        $dashboard = app(PayrollController::class)->myPayslips();
        $this->assertTrue($dashboard->getData()['payslips']->contains('id', $entry->id));
    }

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
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-11',
            'payment_date' => '2026-08-12',
            'status' => 'draft',
        ]);

        $dtr = DTR::create([
            'employee_profile_id' => $employeeProfile->id,
            'period_start' => '2026-08-03',
            'period_end' => '2026-08-11',
            'status' => 'draft',
            'days_present' => 3,
            'days_absent' => 1,
            'late_minutes' => 30,
            'early_out_minutes' => 15,
            'paid_leave_days' => 2,
            'leave_without_pay_days' => 1,
        ]);

        foreach (['2026-08-03', '2026-08-04', '2026-08-05'] as $attendanceDate) {
            AttendanceLog::create([
                'employee_profile_id' => $employeeProfile->id,
                'employee_id' => $employeeUser->id,
                'branch_id' => $branch->id,
                'attendance_date' => $attendanceDate,
                'am_in' => $attendanceDate . ($attendanceDate === '2026-08-03' ? ' 08:30:00' : ' 08:00:00'),
                'pm_out' => $attendanceDate . ($attendanceDate === '2026-08-03' ? ' 16:45:00' : ' 17:00:00'),
                'status' => 'present',
            ]);
        }

        LeaveRequest::create([
            'employee_id' => $employeeUser->id,
            'employee_profile_id' => $employeeProfile->id,
            'start_date' => '2026-08-06',
            'end_date' => '2026-08-07',
            'leave_type' => 'vacation',
            'total_days' => 2,
            'reason' => 'Payslip summary test',
            'status' => 'approved',
            'is_absent' => false,
        ]);

        LeaveRequest::create([
            'employee_id' => $employeeUser->id,
            'employee_profile_id' => $employeeProfile->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
            'leave_type' => 'vacation',
            'total_days' => 1,
            'reason' => 'Payslip summary LWOP test',
            'status' => 'approved',
            'is_absent' => true,
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

    public function test_cash_charge_deduction_uses_cutoff_installment_and_caps_at_remaining_balance(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-CASH-CHARGE-PAYROLL',
            'branch_name' => 'Cash Charge Payroll Branch',
            'address' => 'Cash Charge Payroll Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Cash Charge Payroll Employee',
            'email' => 'cash-charge-payroll@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $profile = EmployeeProfile::create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-CASH-CHARGE-PAYROLL',
            'first_name' => 'Cash Charge',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'basic_salary' => 25000,
            'date_hired' => '2024-01-15',
            'status' => 'Regular',
        ]);

        $cashCharge = CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 1000,
            'installment_per_cutoff' => 400,
            'reason' => 'Payroll deduction test',
            'requested_by' => $employeeUser->id,
            'approved_by' => $employeeUser->id,
            'approved_at' => '2026-07-20 09:00:00',
            'status' => 'approved',
        ]);

        $periods = [];
        foreach ([
            ['2026-07-01', '2026-07-15'],
            ['2026-07-16', '2026-07-31'],
            ['2026-08-01', '2026-08-15'],
        ] as $index => [$startDate, $endDate]) {
            $periods[] = PayrollPeriod::create([
                'branch_id' => $branch->id,
                'period_code' => 'CASH-CHARGE-' . $index,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'payment_date' => $endDate,
                'status' => 'draft',
            ]);
        }

        $payrollEntryAttributes = [
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'basic_pay' => 1000,
            'gross_pay' => 1000,
            'status' => 'draft',
        ];
        $payrollService = app(\App\Services\PayrollComputationService::class);
        $firstDeduction = $payrollService->cashChargeDeductionForPayroll($profile->id, $periods[0]);
        $this->assertSame($cashCharge->id, $firstDeduction['id']);
        $this->assertSame(400.0, $firstDeduction['amount']);

        foreach ([$periods[0], $periods[1]] as $period) {
            $period->update([
                'status' => 'completed',
                'approved_at' => now(),
                'hr_approved_at' => now(),
                'branch_approved_at' => now(),
                'admin_approval_stage' => 'bh_approved',
            ]);
            PayrollEntry::create($payrollEntryAttributes + [
                'payroll_period_id' => $period->id,
                'cash_charge_id' => $cashCharge->id,
                'cash_charge_deduction' => 400,
                'status' => 'approved',
            ]);
        }

        $this->assertSame(400.0, $payrollService->cashChargeDeductionForPayroll($profile->id, $periods[1])['amount']);
        $this->assertSame(200.0, $payrollService->cashChargeDeductionForPayroll($profile->id, $periods[2])['amount']);
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

        $device = Device::create([
            'mac_address' => 'AA-BB-CC-DD-EE-FF',
            'wifi_mac_address' => null,
            'serial_number' => 'SERIAL-DTR-SCANNER-001',
            'device_name' => 'DTR Scanner Test',
            'device_type' => 'computer',
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);
        $timestamp = now()->toIso8601String();
        $payload = [
            'fingerprint_data' => 'scanner-template',
            'action' => 'TIME-IN',
            'employee_id' => (string) $employee->id,
            'employee_number' => $employee->employee_number,
            'device_serial' => $device->serial_number,
            'wifi_mac' => 'AA-BB-CC-DD-EE-FF',
            'device_id' => (string) $device->id,
            'mac_address' => 'AA-BB-CC-DD-EE-FF',
            'timestamp' => $timestamp,
            'attendance_timestamp' => $timestamp,
            'nonce' => 'scanner-dtr-test-nonce',
        ];
        $secret = config('app.biometric_device_secret') ?? env('BIOMETRIC_DEVICE_SECRET') ?? env('APP_KEY');
        $payload['signature'] = hash_hmac('sha256', implode('|', [
            $payload['employee_id'],
            $payload['employee_number'],
            $payload['device_serial'],
            $payload['wifi_mac'],
            $payload['device_id'],
            $payload['mac_address'],
            $payload['timestamp'],
            $payload['nonce'],
        ]), $secret);
        $request = Request::create('/api/biometric/time-clock', 'POST', $payload);

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

    public function test_finance_head_sees_hr_approved_dtrs_in_approved_tab(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-FH-DTR-TABS',
            'branch_name' => 'Finance Head DTR Branch',
            'address' => 'Test Address',
        ]);

        $employeeUser = User::create([
            'name' => 'DTR Employee',
            'email' => 'dtr.employee.tabs@example.com',
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
            'employee_number' => 'EMP-FH-DTR-TABS',
            'first_name' => 'DTR',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'basic_salary' => 20000,
            'date_hired' => now(),
        ]);

        $financeHead = User::create([
            'name' => 'Finance Head',
            'email' => 'finance.head.dtr.tabs@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance_head',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $financeHeadEmployeeProfile = EmployeeProfile::create([
            'user_id' => $financeHead->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-FH-DTR-SELF',
            'first_name' => 'Finance',
            'last_name' => 'Head',
            'position' => 'Finance Head',
            'basic_salary' => 30000,
            'date_hired' => now(),
        ]);
        $financeProfile = FinanceProfile::create([
            'user_id' => $financeHead->id,
            'employee_profile_id' => $financeHeadEmployeeProfile->id,
            'branch_id' => $branch->id,
            'employee_number' => 'FIN-FH-DTR-TABS',
            'first_name' => 'Finance',
            'last_name' => 'Head',
            'position' => 'Finance Head',
            'date_hired' => now(),
        ]);
        $financeHead->profile_id = $financeProfile->id;
        $financeHead->profile_type = FinanceProfile::class;
        $financeHead->save();

        $dtr = DTR::create([
            'employee_profile_id' => $employeeProfile->id,
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-15',
            'status' => 'pending_finance_head',
        ]);

        $this->actingAs($financeHead)
            ->get(route('admin.dtr.index'))
            ->assertOk()
            ->assertSeeText('Approved by HR')
            ->assertSeeText('Awaiting FH Computation')
            ->assertSee('Compute DTR')
            ->assertViewHas('pendingDTRs', fn ($dtrs) => $dtrs->total() === 0)
            ->assertViewHas('approvedDTRs', fn ($dtrs) =>
                $dtrs->total() === 1 && $dtrs->getCollection()->first()->id === $dtr->id
            );

        $this->get(route('admin.dtr.show', $dtr))
            ->assertOk()
            ->assertSeeText('Approved by HR - Awaiting FH Computation')
            ->assertSeeText('Total Holidays')
            ->assertSeeText('Total Suspensions')
            ->assertSeeText('Total Halfdays')
            ->assertSeeText('Total Suspended Hours');

            $this->actingAs($employeeUser)
                ->get(route('employee.dtr.show', $dtr))
                ->assertOk()
                ->assertSeeText('Approved by HR - Awaiting FH Computation');
    }

    public function test_calendar_suspensions_are_non_working_days_for_dtr(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-DTR-SUSPENSION',
            'branch_name' => 'Suspension DTR Branch',
            'address' => 'Suspension DTR Address',
        ]);

        CalendarEvent::create([
            'title' => 'Class Suspension',
            'event_date' => '2026-08-18',
            'event_type' => 'suspension',
            'created_by' => User::factory()->create()->id,
            'branch_id' => $branch->id,
        ]);
        CalendarEvent::create([
            'title' => 'No-Punch Suspension',
            'event_date' => '2026-08-20',
            'event_type' => 'suspension',
            'created_by' => User::factory()->create()->id,
            'branch_id' => $branch->id,
        ]);
        CalendarEvent::create([
            'title' => 'Branch Holiday',
            'event_date' => '2026-08-17',
            'event_type' => 'holiday',
            'created_by' => User::factory()->create()->id,
            'branch_id' => $branch->id,
        ]);

        $service = app(WorkingDayService::class);

        $this->assertTrue($service->isSuspension('2026-08-18', $branch->id));
        $this->assertFalse($service->isWorkingDay('2026-08-18', $branch->id));
        $this->assertSame(2, $service->countWorkingDays('2026-08-17', '2026-08-21', $branch->id));

        $user = User::create([
            'name' => 'Suspension DTR Employee',
            'email' => 'suspension.dtr.employee@example.com',
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
            'employee_number' => 'EMP-DTR-SUSPENSION',
            'first_name' => 'Suspension',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'basic_salary' => 20000,
            'date_hired' => now(),
        ]);

        AttendanceLog::create([
            'employee_profile_id' => $employeeProfile->id,
            'employee_id' => $user->id,
            'branch_id' => $branch->id,
            'attendance_date' => '2026-08-18',
            'am_in' => '2026-08-18 08:10:00',
            'pm_out' => '2026-08-18 12:22:00',
            'status' => 'present',
            'verification_method' => 'fingerprint',
        ]);

        AttendanceLog::create([
            'employee_profile_id' => $employeeProfile->id,
            'employee_id' => $user->id,
            'branch_id' => $branch->id,
            'attendance_date' => '2026-08-19',
            'am_in' => '2026-08-19 07:13:00',
            'am_out' => '2026-08-19 11:13:00',
            'status' => 'present',
            'late_minutes' => 13,
            'verification_method' => 'fingerprint',
        ]);

        AttendanceLog::create([
            'employee_profile_id' => $employeeProfile->id,
            'employee_id' => $user->id,
            'branch_id' => $branch->id,
            'attendance_date' => '2026-08-21',
            'am_in' => '2026-08-21 08:00:00',
            'am_out' => '2026-08-21 12:00:00',
            'pm_in' => '2026-08-21 13:00:00',
            'pm_out' => '2026-08-21 16:54:00',
            'status' => 'present',
            'verification_method' => 'fingerprint',
        ]);

        $dtr = DTR::create([
            'employee_profile_id' => $employeeProfile->id,
            'period_start' => '2026-08-17',
            'period_end' => '2026-08-21',
            'status' => 'draft',
        ]);

        $showResponse = $this->actingAs($user)->get(route('employee.dtr.show', $dtr));

        $showResponse
            ->assertOk()
            ->assertSeeText('Suspension')
            ->assertDontSeeText('Suspended/Present')
            ->assertSeeText('Total Holidays')
            ->assertSeeText('Total Suspensions')
            ->assertSeeText('Total Halfdays')
            ->assertSeeText('Total Suspended Hours')
            ->assertViewHas('stats', fn ($stats) =>
                $stats['total_holidays'] === 1
                && $stats['total_suspensions'] === 2
                && $stats['total_half_days'] === 1
                && $stats['total_suspended_hours'] === 4.2
                && $stats['total_early_out_minutes'] === 6
                && $stats['days_present'] === 2
                && $stats['total_hours'] === 11.9
            );

        $pdfHtml = view('pdf.dtr', [
            'dtr' => $dtr,
            'employeeProfile' => $employeeProfile,
            'daysInPeriod' => $showResponse->viewData('daysInPeriod'),
            'stats' => $showResponse->viewData('stats'),
            'employeeSignature' => null,
            'logo' => null,
        ])->render();
        $this->assertMatchesRegularExpression(
            '/<td class="center">07:13 AM<\/td>\s*<td class="center">11:13 AM<\/td>\s*<td class="center">0<\/td>\s*<td class="status">Half Day<\/td>/',
            $pdfHtml
        );
        $this->assertStringContainsString('<th>Late/Early Out (min)</th>', $pdfHtml);
        $this->assertMatchesRegularExpression(
            '/<td class="center">08:00 AM<\/td>\s*<td class="center">04:54 PM<\/td>\s*<td class="center">6<\/td>\s*<td class="status">Early Out<\/td>/',
            $pdfHtml
        );

        $this->get(route('employee.dtr.download', $dtr))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $recordedSuspensionLog = AttendanceLog::where('employee_profile_id', $employeeProfile->id)
            ->whereDate('attendance_date', '2026-08-18')
            ->first();
        $this->assertNotNull($recordedSuspensionLog);
        $this->assertSame('08:10:00', $recordedSuspensionLog->am_in->format('H:i:s'));
        $this->assertSame('12:22:00', $recordedSuspensionLog->pm_out->format('H:i:s'));

        $dtr->calculateTotals();
        $this->assertSame(2, $dtr->days_present);
        $this->assertEqualsWithDelta(11.9, (float) $dtr->total_hours, 0.001);
    }

    public function test_payroll_deducts_daily_rate_for_unattended_holidays_and_suspensions(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-CALENDAR-DEDUCTION',
            'branch_name' => 'Calendar Deduction Branch',
            'address' => 'Calendar Deduction Address',
        ]);

        $admin = User::create([
            'name' => 'Calendar Payroll Admin',
            'email' => 'calendar.payroll.admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance_head',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $employee = User::create([
            'name' => 'Calendar Deduction Employee',
            'email' => 'calendar.deduction.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $profile = EmployeeProfile::create([
            'user_id' => $employee->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-CALENDAR-DEDUCTION',
            'first_name' => 'Calendar',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'basic_salary' => 10000,
            'date_hired' => now(),
        ]);
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->startOfDay();
        $holidayDate = $periodStart->copy();
        $suspensionDate = $holidayDate->copy()->addDay();
        $attendedHolidayDate = $holidayDate->copy()->addDays(2);
        $attendedSuspensionDate = $holidayDate->copy()->addDays(3);

        CalendarEvent::create([
            'title' => 'Unattended Holiday',
            'event_date' => $holidayDate->toDateString(),
            'event_type' => 'holiday',
            'created_by' => $admin->id,
            'branch_id' => $branch->id,
            'approval_status' => 'approved',
        ]);
        CalendarEvent::create([
            'title' => 'Unattended Suspension',
            'event_date' => $suspensionDate->toDateString(),
            'event_type' => 'suspension',
            'created_by' => $admin->id,
            'branch_id' => $branch->id,
            'approval_status' => 'approved',
        ]);
        CalendarEvent::create([
            'title' => 'Attended Holiday',
            'event_date' => $attendedHolidayDate->toDateString(),
            'event_type' => 'holiday',
            'created_by' => $admin->id,
            'branch_id' => $branch->id,
            'approval_status' => 'approved',
        ]);
        CalendarEvent::create([
            'title' => 'Attended Suspension',
            'event_date' => $attendedSuspensionDate->toDateString(),
            'event_type' => 'suspension',
            'created_by' => $admin->id,
            'branch_id' => $branch->id,
            'approval_status' => 'approved',
        ]);
        AttendanceLog::create([
            'employee_profile_id' => $profile->id,
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'attendance_date' => $attendedHolidayDate->toDateString(),
            'am_in' => $attendedHolidayDate->copy()->setTime(8, 0),
            'pm_out' => $attendedHolidayDate->copy()->setTime(17, 0),
            'status' => 'present',
        ]);
        AttendanceLog::create([
            'employee_profile_id' => $profile->id,
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'attendance_date' => $attendedSuspensionDate->toDateString(),
            'am_in' => $attendedSuspensionDate->copy()->setTime(8, 0),
            'am_out' => $attendedSuspensionDate->copy()->setTime(12, 0),
            'status' => 'present',
        ]);

        $dtr = DTR::create([
            'employee_profile_id' => $profile->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => 'draft',
        ]);
        $payrollPeriod = PayrollPeriod::create([
            'branch_id' => $branch->id,
            'period_code' => 'PRD-CALENDAR-DEDUCTION',
            'period_type' => 'monthly',
            'start_date' => $periodStart,
            'end_date' => $periodEnd,
            'cutoff_date' => $periodEnd,
            'payment_date' => $periodEnd->copy()->addDay(),
            'status' => 'draft',
        ]);
        $entry = PayrollEntry::create([
            'payroll_period_id' => $payrollPeriod->id,
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'dtr_id' => $dtr->id,
            'basic_pay' => 10000,
            'days_present' => 0,
            'days_absent' => 0,
            'gross_pay' => 10000,
            'net_pay' => 10000,
            'total_deductions' => 0,
            'overtime_pay' => 0,
            'status' => 'draft',
            'payroll_breakdown' => json_encode([
                'holiday_bonus' => 454,
                'suspended_hours_to_pay' => 5,
            ]),
        ]);

        $this->actingAs($admin);
        $view = app(PayrollController::class)->editEntry($entry->id);
        $dtrStats = $view->getData()['dtrStats'];
        $this->assertSame(1, $dtrStats['missed_holiday_days']);
        $this->assertArrayHasKey('half_day_days', $dtrStats);
        $this->assertSame(1, $dtrStats['missed_suspension_days']);
        $this->assertSame(2, $dtrStats['total_holidays']);
        $this->assertSame(2, $dtrStats['total_suspensions']);
        $this->assertIsNumeric($dtrStats['total_suspended_hours'] ?? null);
        $dailyRate = 10000 / max(1, $dtr->getWorkingDays());
        $this->assertEqualsWithDelta($dailyRate * 2, (float) $dtrStats['suspension_deduction'], 0.01);
        $view->with('errors', new \Illuminate\Support\ViewErrorBag());
        $html = $view->render();
        $this->assertStringContainsString('Total Half Day', $html);
        $this->assertMatchesRegularExpression('/id="gross_pay_total"[^>]*value="10000\.00"/', $html);
        $this->assertMatchesRegularExpression('/id="overtime_pay_display"[^>]*value="754\.00"/', $html);
        $this->assertMatchesRegularExpression('/id="computed_net_pay"[^>]*value="₱10,754\.00"/', $html);
        $this->assertEqualsWithDelta(10754.0, (float) $entry->fresh()->net_pay, 0.01);

        app(PayrollController::class)->updateEntry(Request::create('/admin/payroll/entry/' . $entry->id, 'PUT', [
            'daily_rate' => $dailyRate,
            'sss_contribution' => 0,
            'philhealth_contribution' => 0,
            'pagibig_contribution' => 0,
            'withholding_tax' => 0,
            'status' => 'calculated',
        ]), $entry->id);

        $fresh = $entry->fresh();
        $breakdown = json_decode($fresh->payroll_breakdown, true);
        $this->assertEqualsWithDelta($dailyRate, (float) $breakdown['holiday_deduction'], 0.01);
        $this->assertEqualsWithDelta($dailyRate * 2, (float) $breakdown['suspension_deduction'], 0.01);
        $expectedTotalDeductions = (float) $fresh->absent_deduction + ($dailyRate * 3);
        $this->assertEqualsWithDelta($expectedTotalDeductions, (float) $fresh->total_deductions, 0.01);

        $expectedComputedNetPay = (float) $fresh->gross_pay
            - (float) $fresh->total_deductions
            + (float) ($breakdown['holiday_bonus'] ?? 0)
            + ((float) ($breakdown['suspended_hours_to_pay'] ?? 0) * 60);
        $entriesView = app(PayrollController::class)->viewEntries($payrollPeriod->id);
        $listedEntry = $entriesView->getData()['entries']->firstWhere('id', $entry->id);
        $this->assertEqualsWithDelta($expectedComputedNetPay, (float) $listedEntry->net_pay, 0.01);
        $this->assertEqualsWithDelta($expectedComputedNetPay, (float) $entry->fresh()->net_pay, 0.01);
    }

    public function test_approved_entry_keeps_manually_entered_daily_rate(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-APPROVED-DAILY-RATE',
            'branch_name' => 'Approved Daily Rate Branch',
            'address' => 'Approved Daily Rate Address',
        ]);

        $admin = User::create([
            'name' => 'Approved Daily Rate Admin',
            'email' => 'approved.daily.rate.admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance_head',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $employee = User::create([
            'name' => 'Approved Daily Rate Employee',
            'email' => 'approved.daily.rate.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $profile = EmployeeProfile::create([
            'user_id' => $employee->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-APPROVED-DAILY-RATE',
            'first_name' => 'Approved',
            'last_name' => 'Rate',
            'position' => 'Staff',
            'basic_salary' => 10000,
            'date_hired' => now(),
        ]);

        $periodStart = now()->startOfMonth();
        $periodEnd = now()->startOfDay();
        $dtr = DTR::create([
            'employee_profile_id' => $profile->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => 'approved',
        ]);
        $period = PayrollPeriod::create([
            'branch_id' => $branch->id,
            'period_code' => 'PRD-APPROVED-DAILY-RATE',
            'period_type' => 'monthly',
            'start_date' => $periodStart,
            'end_date' => $periodEnd,
            'cutoff_date' => $periodEnd,
            'payment_date' => $periodEnd->copy()->addDay(),
            'status' => 'draft',
        ]);
        $entry = PayrollEntry::create([
            'payroll_period_id' => $period->id,
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'dtr_id' => $dtr->id,
            'basic_pay' => 10000,
            'days_present' => 0,
            'days_absent' => 0,
            'gross_pay' => 10000,
            'net_pay' => 10000,
            'total_deductions' => 0,
            'overtime_pay' => 0,
            'status' => 'approved',
            'payroll_breakdown' => json_encode([
                'daily_rate' => 625.00,
                'holiday_bonus' => 0,
                'suspended_hours_to_pay' => 0,
            ]),
        ]);

        $this->actingAs($admin);
        app(PayrollController::class)->updateEntry(Request::create('/admin/payroll/entry/' . $entry->id, 'PUT', [
            'daily_rate' => 3200,
            'sss_contribution' => 0,
            'philhealth_contribution' => 0,
            'pagibig_contribution' => 0,
            'withholding_tax' => 0,
            'status' => 'approved',
        ]), $entry->id);

        $fresh = $entry->fresh();
        $breakdown = json_decode($fresh->payroll_breakdown, true);
        $this->assertEqualsWithDelta(3200.0, (float) ($breakdown['daily_rate'] ?? 0), 0.01);

        $view = app(PayrollController::class)->editEntry($entry->id);
        $view->with('errors', new \Illuminate\Support\ViewErrorBag());
        $html = $view->render();
        $this->assertMatchesRegularExpression('/id="daily_rate_input"[^>]*value="3200\.00"/', $html);
    }

    public function test_approved_payroll_entry_is_static_for_non_finance_head(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-STATIC-APPROVED',
            'branch_name' => 'Static Approved Branch',
            'address' => 'Static Approved Address',
        ]);

        $admin = User::create([
            'name' => 'Static Approved Admin',
            'email' => 'static.approved.admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $employee = User::create([
            'name' => 'Static Approved Employee',
            'email' => 'static.approved.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $profile = EmployeeProfile::create([
            'user_id' => $employee->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-STATIC-APPROVED',
            'first_name' => 'Static',
            'last_name' => 'Approved',
            'position' => 'Staff',
            'basic_salary' => 10000,
            'date_hired' => now(),
        ]);

        $periodStart = now()->startOfMonth();
        $periodEnd = now()->startOfDay();
        $dtr = DTR::create([
            'employee_profile_id' => $profile->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => 'approved',
        ]);
        $period = PayrollPeriod::create([
            'branch_id' => $branch->id,
            'period_code' => 'PRD-STATIC-APPROVED',
            'period_type' => 'monthly',
            'start_date' => $periodStart,
            'end_date' => $periodEnd,
            'cutoff_date' => $periodEnd,
            'payment_date' => $periodEnd->copy()->addDay(),
            'status' => 'approved',
        ]);
        $entry = PayrollEntry::create([
            'payroll_period_id' => $period->id,
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'dtr_id' => $dtr->id,
            'basic_pay' => 10000,
            'days_present' => 6,
            'days_absent' => 1,
            'gross_pay' => 10000,
            'net_pay' => 10000,
            'total_deductions' => 0,
            'overtime_pay' => 0,
            'status' => 'approved',
            'payroll_breakdown' => json_encode([
                'daily_rate' => 455.00,
                'holiday_bonus' => 0,
                'suspended_hours_to_pay' => 0,
            ]),
        ]);

        $this->actingAs($admin);
        $view = app(PayrollController::class)->editEntry($entry->id);
        $view->with('errors', new \Illuminate\Support\ViewErrorBag());
        $html = $view->render();

        $this->assertStringContainsString('max-width: 1120px;', $html);
        $this->assertStringContainsString('flex: 0 0 100%;', $html);
        $this->assertMatchesRegularExpression('/<fieldset[^>]*disabled/', $html);
        $this->assertMatchesRegularExpression('/id="daily_rate_input"[^>]*readonly/', $html);
    }

    public function test_correction_return_allows_editing_approved_entry_fields(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-CORRECTION-EDIT',
            'branch_name' => 'Correction Edit Branch',
            'address' => 'Correction Edit Address',
        ]);

        $admin = User::create([
            'name' => 'Correction Edit Admin',
            'email' => 'correction.edit.admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance_head',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $employee = User::create([
            'name' => 'Correction Edit Employee',
            'email' => 'correction.edit.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);
        $profile = EmployeeProfile::create([
            'user_id' => $employee->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-CORRECTION-EDIT',
            'first_name' => 'Correction',
            'last_name' => 'Edit',
            'position' => 'Staff',
            'basic_salary' => 10000,
            'date_hired' => now(),
        ]);

        $periodStart = now()->startOfMonth();
        $periodEnd = now()->startOfDay();
        $dtr = DTR::create([
            'employee_profile_id' => $profile->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => 'approved',
        ]);
        $period = PayrollPeriod::create([
            'branch_id' => $branch->id,
            'period_code' => 'PRD-CORRECTION-EDIT',
            'period_type' => 'monthly',
            'start_date' => $periodStart,
            'end_date' => $periodEnd,
            'cutoff_date' => $periodEnd,
            'payment_date' => $periodEnd->copy()->addDay(),
            'status' => 'approved',
        ]);
        $entry = PayrollEntry::create([
            'payroll_period_id' => $period->id,
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'dtr_id' => $dtr->id,
            'basic_pay' => 10000,
            'days_present' => 6,
            'days_absent' => 1,
            'gross_pay' => 10000,
            'net_pay' => 10000,
            'total_deductions' => 0,
            'overtime_pay' => 0,
            'status' => 'approved',
            'correction_stage' => 'fh_correction',
            'payroll_breakdown' => json_encode([
                'daily_rate' => 455.00,
                'holiday_bonus' => 0,
                'suspended_hours_to_pay' => 0,
            ]),
        ]);

        $this->actingAs($admin);
        $view = app(PayrollController::class)->editEntry($entry->id);
        $view->with('errors', new \Illuminate\Support\ViewErrorBag());
        $html = $view->render();

        $this->assertDoesNotMatchRegularExpression('/<fieldset[^>]*disabled/', $html);
        $this->assertMatchesRegularExpression('/id="daily_rate_input"[^>]*value="455\.00"/', $html);
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
            'am_in' => now()->setTime(8, 31),
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
        $this->assertStringContainsString('Late: 31 min', $html);
        $this->assertStringContainsString('Early Out: 30 min', $html);
        $this->assertSame(1, $view->getData()['stats']['days_present']);
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
            'am_in' => now()->setTime(8, 31),
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
            'period_start' => '2026-08-03',
            'period_end' => '2026-08-03',
            'status' => 'draft',
            'days_present' => 0,
            'days_absent' => 0,
            'late_minutes' => 0,
        ]);

        LeaveRequest::create([
            'employee_id' => $user->id,
            'employee_profile_id' => $employeeProfile->id,
            'leave_type' => 'vacation',
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-03',
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
            'am_in' => now()->setTime(8, 31),
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
        app(PayrollController::class)->updateEntry(Request::create('/admin/payroll/entry/' . $entry->id, 'PUT', [
            'sss_contribution' => 0,
            'philhealth_contribution' => 0,
            'pagibig_contribution' => 0,
            'withholding_tax' => 0,
            'status' => 'calculated',
            'absent_deduction' => 999,
            'holiday_deduction' => 999,
            'suspension_deduction' => 999,
            'leave_without_pay_amount' => 999,
        ]), $entry->id);

        $fresh = $entry->fresh();
        $this->assertSame(1, $fresh->days_present);
        $this->assertSame($dtr->fresh()->days_absent, $fresh->days_absent);

        $breakdown = json_decode($fresh->payroll_breakdown, true);
        $this->assertSame(1, $breakdown['days_present']);
        $this->assertSame($fresh->days_absent, $breakdown['days_absent']);
        $this->assertArrayHasKey('paid_leave', $breakdown);
        $this->assertArrayHasKey('leave_without_pay', $breakdown);
        $this->assertEqualsWithDelta($fresh->days_absent * (float) $breakdown['daily_rate'], (float) $fresh->absent_deduction, 0.01);
        $this->assertNotSame(999.0, (float) ($breakdown['holiday_deduction'] ?? 0));
        $this->assertNotSame(999.0, (float) ($breakdown['suspension_deduction'] ?? 0));
        $expectedDeductions = (float) $fresh->sss_contribution
            + (float) $fresh->philhealth_contribution
            + (float) $fresh->pagibig_contribution
            + (float) $fresh->withholding_tax
            + (float) ($fresh->cash_advance_deduction ?? 0)
            + (float) ($fresh->cash_charge_deduction ?? 0)
            + (float) $fresh->late_deduction
            + (float) $fresh->absent_deduction
            + ((int) $breakdown['leave_without_pay'] * (float) $breakdown['daily_rate'])
            + (float) ($breakdown['early_out_deduction'] ?? 0)
            + (float) ($breakdown['half_day_deduction'] ?? 0)
            + (float) ($breakdown['holiday_deduction'] ?? 0)
            + (float) ($breakdown['suspension_deduction'] ?? 0);
        $this->assertEqualsWithDelta($expectedDeductions, (float) $fresh->total_deductions, 0.01);
    }

    public function test_late_rule_starts_after_8_00_am(): void
    {
        $scheduledStart = now()->setTime(8, 0, 0);

        $this->assertSame(0, \App\Services\AttendanceTimeRules::lateMinutes(now()->setTime(8, 0, 0), $scheduledStart));
        $this->assertSame(1, \App\Services\AttendanceTimeRules::lateMinutes(now()->setTime(8, 1, 0), $scheduledStart));
        $this->assertSame(30, \App\Services\AttendanceTimeRules::lateMinutes(now()->setTime(8, 30, 0), $scheduledStart));
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
            'am_in' => now()->setTime(8, 31),
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
