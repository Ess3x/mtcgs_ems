<?php

namespace Tests\Feature;

use App\Mail\LeaveCreditsUpdated;
use App\Models\AdminProfile;
use App\Models\Branch;
use App\Models\CashCharge;
use App\Models\EmployeeProfile;
use App\Models\FinanceProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveCreditManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_and_update_employee_leave_credits(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-050',
            'branch_name' => 'Main Branch',
            'address' => 'Test Address',
        ]);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeUser = User::create([
            'name' => 'Employee One',
            'email' => 'employee1@example.com',
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
            'employee_number' => 'EMP-001',
            'first_name' => 'Employee',
            'last_name' => 'One',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        $response = $this->actingAs($superAdmin)->get(route('admin.leave-credits.index'));

        $response->assertOk();
        $response->assertSeeText('Leave Credits Management');
        $response->assertSeeText('Birthday');
        $response->assertSeeText('Cash Charges');

        $response = $this->actingAs($superAdmin)->post(route('admin.leave-credits.update', $profile), [
            'sick_leave_total' => '12',
            'vacation_leave_total' => '10',
            'emergency_leave_total' => '5',
            'maternity_leave_total' => '0',
            'paternity_leave_total' => '0',
            'birthday_leave_total' => '1',
            'service_incentive_leave_total' => '5',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_balances', [
            'employee_profile_id' => $profile->id,
            'year' => now()->year,
            'sick_leave_total' => '12.0',
            'vacation_leave_total' => '10.0',
            'emergency_leave_total' => '5.0',
            'birthday_leave_total' => '1.0',
        ]);

        $balance = app(\App\Http\Controllers\LeaveController::class)->refreshLeaveBalanceForProfile($profile);
        $this->assertSame(12.0, (float) $balance->sick_leave_total);
        $this->assertSame(10.0, (float) $balance->vacation_leave_total);
        $this->assertSame(5.0, (float) $balance->emergency_leave_total);
        $this->assertSame(1.0, (float) $balance->birthday_leave_total);
        $this->assertSame(0.0, (float) $balance->cash_charge_total);
    }

    public function test_leave_credit_update_sends_email_and_user_notification(): void
    {
        Mail::fake();

        $branch = Branch::create([
            'branch_code' => 'BR-120',
            'branch_name' => 'Notification Branch',
            'address' => 'Notification Address',
        ]);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin-notify@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeUser = User::create([
            'name' => 'Employee Notify',
            'email' => 'employee-notify@example.com',
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
            'employee_number' => 'EMP-011',
            'first_name' => 'Employee',
            'last_name' => 'Notify',
            'position' => 'Staff',
            'date_hired' => '2023-01-15',
            'status' => 'Regular',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        $this->actingAs($superAdmin)->post(route('admin.leave-credits.update', $profile), [
            'sick_leave_total' => '12',
            'vacation_leave_total' => '10',
            'emergency_leave_total' => '5',
            'maternity_leave_total' => '0',
            'paternity_leave_total' => '0',
            'birthday_leave_total' => '1',
            'service_incentive_leave_total' => '5',
        ]);

        Mail::assertSent(LeaveCreditsUpdated::class, function ($mail) use ($profile) {
            return $mail->employeeProfile->id === $profile->id
                && $mail->hasTo($profile->user->email);
        });

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $employeeUser->id,
        ]);
    }

    public function test_leave_credits_form_does_not_include_cash_charge_total(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-100',
            'branch_name' => 'Test Branch',
            'address' => 'Sample Address',
        ]);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin-form@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeUser = User::create([
            'name' => 'Employee Form',
            'email' => 'employee-form@example.com',
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
            'employee_number' => 'EMP-010',
            'first_name' => 'Employee',
            'last_name' => 'Form',
            'position' => 'Staff',
            'date_hired' => '2023-01-15',
            'status' => 'Regular',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        $response = $this->actingAs($superAdmin)->get(route('admin.leave-credits.index'));

        $response->assertOk();
        $response->assertDontSee('name="cash_charge_total"', false);
    }

    public function test_branch_admin_can_access_cash_charges_management(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-200',
            'branch_name' => 'North Branch',
            'address' => 'North Address',
        ]);

        $branchAdmin = User::create([
            'name' => 'Branch Admin',
            'email' => 'branch-admin-cash@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeProfile = EmployeeProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-400',
            'first_name' => 'Branch',
            'last_name' => 'Admin Cash',
            'position' => 'Branch Admin',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        AdminProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_profile_id' => $employeeProfile->id,
            'employee_number' => 'ADM-400',
            'first_name' => 'Branch',
            'last_name' => 'Admin Cash',
            'position' => 'Branch Admin',
            'admin_level' => 'branch_admin',
            'date_hired' => '2024-01-15',
        ]);

        $response = $this->actingAs($branchAdmin)->get(route('admin.cash-charges.index'));

        $response->assertOk();
        $response->assertSeeText('Cash Charges Management');
        $response->assertDontSeeText('No charges submitted under your account yet.');
        $response->assertSeeText('Add Charges');
        $response->assertSeeText('Cash Charge Requests');
        $response->assertDontSeeText('Active Users');
    }

    public function test_super_admin_cash_charge_skips_branch_admin_review(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-SA-CHARGE',
            'branch_name' => 'Super Admin Charge Branch',
            'address' => 'Charge Test Address',
        ]);

        $superAdmin = User::create([
            'name' => 'Charge Super Admin',
            'email' => 'charge-super-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employee = User::create([
            'name' => 'Charge Employee',
            'email' => 'charge-employee@example.com',
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
            'employee_number' => 'EMP-SA-CHARGE',
            'first_name' => 'Charge',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => 'Regular',
        ]);

        Storage::fake('local');

        $this->actingAs($superAdmin)
            ->post(route('admin.cash-charges.store'), [
                'employee_id' => $profile->id,
                'amount' => 1200,
                'reason' => 'Approved by Super Admin',
                'evidence' => UploadedFile::fake()->image('cash-charge.png'),
            ])
            ->assertRedirect();

        $charge = CashCharge::where('employee_profile_id', $profile->id)->firstOrFail();

        $this->assertDatabaseHas('cash_charges', [
            'employee_profile_id' => $profile->id,
            'requested_by' => $superAdmin->id,
            'status' => 'pending_super_admin',
            'evidence_path' => $charge->evidence_path,
        ]);
        $this->assertNotEmpty($charge->evidence_path);
        Storage::disk('local')->assertExists($charge->evidence_path);

        $this->actingAs($superAdmin)
            ->get(route('admin.cash-charges.index'))
            ->assertOk()
            ->assertSeeText('Evidence')
            ->assertSeeText('View')
            ->assertSee('aria-label="View evidence image"', false)
            ->assertSee('fa-eye');

        $this->get(route('admin.cash-charges.evidence', $charge))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_employee_can_view_their_own_my_charges(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-300',
            'branch_name' => 'Employee Branch',
            'address' => 'Employee Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Employee Charges',
            'email' => 'employee-charges@example.com',
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
            'employee_number' => 'EMP-900',
            'first_name' => 'Employee',
            'last_name' => 'Charges',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        Storage::fake('local');
        $evidencePath = UploadedFile::fake()->image('approved-charge.png')->store('cash-charge-evidence', 'local');

        $charge = \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 2500,
            'reason' => 'Emergency cash assistance',
            'requested_by' => $employeeUser->id,
            'status' => 'pending',
        ]);

        $approvedCharge = CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 1000,
            'installment_per_cutoff' => 500,
            'reason' => 'Approved keyboard replacement',
            'evidence_path' => $evidencePath,
            'requested_by' => $employeeUser->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($employeeUser)->get(route('admin.cash-charges.index'));

        $response->assertOk();
        $response->assertSeeText('My Charges');
        $response->assertSee('cash_charge_total');
        $response->assertSee('Installment / Cut-off');
        $response->assertSeeText('Installment Amount');
        $response->assertSee('₱1,250.00');
        $response->assertSeeText('Emergency cash assistance');
        $response->assertSeeText('Approved by Super Admin');
        $response->assertSeeText('Approved keyboard replacement');
        $response->assertSee('fa-eye');
        $this->assertSame(2, substr_count($response->getContent(), route('admin.cash-charges.evidence', $approvedCharge)));
        $response->assertDontSeeText('Active Users');
        $response->assertDontSeeText('Cash Charge Requests');
        $this->assertDatabaseHas('cash_charges', ['id' => $charge->id, 'requested_by' => $employeeUser->id]);

        $this->get(route('admin.cash-charges.evidence', $approvedCharge))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_pending_cash_charge_request_reverts_to_last_approved_total(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-555A',
            'branch_name' => 'Pending Revert Branch',
            'address' => 'Pending Revert Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Pending Revert Employee',
            'email' => 'pending-revert@example.com',
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
            'employee_number' => 'EMP-555A',
            'first_name' => 'Pending',
            'last_name' => 'Revert',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        \App\Models\LeaveBalance::firstOrCreate(
            ['employee_profile_id' => $profile->id, 'year' => now()->year],
            [
                'sick_leave_used' => 0,
                'vacation_leave_used' => 0,
                'emergency_leave_used' => 0,
                'birthday_leave_used' => 0,
                'cash_charge_total' => 3000,
                'cash_charge_used' => 0,
                'maternity_leave_used' => 0,
                'paternity_leave_used' => 0,
            ]
        );

        \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 3500,
            'installment_per_cutoff' => 500,
            'reason' => 'Pending request should not overwrite approved total',
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
        ]);

        $response = $this->actingAs($employeeUser)->get(route('admin.cash-charges.index'));

        $response->assertOk();
        $response->assertSee('3000.00');
        $response->assertDontSee('3500.00');
    }

    public function test_pending_cash_charge_uses_last_approved_installment_on_summary(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-555B',
            'branch_name' => 'Approved Installment Branch',
            'address' => 'Approved Installment Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Approved Installment Employee',
            'email' => 'approved-installment@example.com',
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
            'employee_number' => 'EMP-555B',
            'first_name' => 'Approved',
            'last_name' => 'Installment',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        \App\Models\LeaveBalance::firstOrCreate(
            ['employee_profile_id' => $profile->id, 'year' => now()->year],
            [
                'sick_leave_used' => 0,
                'vacation_leave_used' => 0,
                'emergency_leave_used' => 0,
                'birthday_leave_used' => 0,
                'cash_charge_total' => 3000,
                'cash_charge_used' => 0,
                'maternity_leave_used' => 0,
                'paternity_leave_used' => 0,
            ]
        );

        \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 3000,
            'installment_per_cutoff' => 500,
            'reason' => 'Old approved adjustment',
            'requested_by' => $employeeUser->id,
            'status' => 'approved',
        ]);

        \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 3500,
            'installment_per_cutoff' => 300,
            'reason' => 'Pending request should not override approved installment',
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
        ]);

        $response = $this->actingAs($employeeUser)->get(route('admin.cash-charges.index'));

        $response->assertOk();
        $response->assertSee('3000.00');
        $response->assertSee('500.00');
        $response->assertSee('Pending Branch Admin');
    }

    public function test_employee_sees_branch_admin_request_and_super_admin_approval_in_separate_sections(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-560',
            'branch_name' => 'Separate Sections Branch',
            'address' => 'Separate Sections Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Separate Section Employee',
            'email' => 'separate-section@example.com',
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
            'employee_number' => 'EMP-560',
            'first_name' => 'Separate',
            'last_name' => 'Section',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        \App\Models\LeaveBalance::firstOrCreate(
            ['employee_profile_id' => $profile->id, 'year' => now()->year],
            [
                'sick_leave_used' => 0,
                'vacation_leave_used' => 0,
                'emergency_leave_used' => 0,
                'birthday_leave_used' => 0,
                'cash_charge_total' => 3000,
                'cash_charge_used' => 0,
                'maternity_leave_used' => 0,
                'paternity_leave_used' => 0,
            ]
        );

        \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 3000,
            'installment_per_cutoff' => 500,
            'reason' => 'Applied by branch admin',
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
        ]);

        \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 3000,
            'installment_per_cutoff' => 500,
            'reason' => 'Approved by super admin',
            'requested_by' => $employeeUser->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($employeeUser)->get(route('admin.cash-charges.index'));

        $response->assertOk();
        $response->assertSeeText('Applied by Branch Admin');
        $response->assertSeeText('Approved by Super Admin');
        $response->assertSeeText('Approved by super admin');
    }

    public function test_cash_charge_total_stays_fixed_while_installment_field_can_change(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-555',
            'branch_name' => 'Fixed Total Branch',
            'address' => 'Fixed Total Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Fixed Total Employee',
            'email' => 'fixed-total-employee@example.com',
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
            'employee_number' => 'EMP-555',
            'first_name' => 'Fixed',
            'last_name' => 'Total',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        \App\Models\LeaveBalance::firstOrCreate(
            ['employee_profile_id' => $profile->id, 'year' => now()->year],
            [
                'sick_leave_used' => 0,
                'vacation_leave_used' => 0,
                'emergency_leave_used' => 0,
                'birthday_leave_used' => 0,
                'cash_charge_total' => 3000,
                'cash_charge_used' => 0,
                'maternity_leave_used' => 0,
                'paternity_leave_used' => 0,
            ]
        );

        $response = $this->actingAs($employeeUser)->post(route('admin.cash-charges.request-update', $profile), [
            'installment_per_cutoff' => '2000',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cash_charges', [
            'employee_profile_id' => $profile->id,
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
            'amount' => '3000.00',
            'installment_per_cutoff' => '2000.00',
        ]);
    }

    public function test_cash_charge_balance_decreases_only_for_fully_approved_payroll_installments(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-CHARGE-APPROVAL',
            'branch_name' => 'Charge Approval Branch',
            'address' => 'Charge Approval Address',
        ]);
        $employeeUser = User::create([
            'name' => 'Charge Balance Employee',
            'email' => 'charge-balance@example.com',
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
            'employee_number' => 'EMP-CHARGE-APPROVAL',
            'first_name' => 'Charge Balance',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
        ]);
        $charge = CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 1000,
            'installment_per_cutoff' => 100,
            'reason' => 'Approved cash charge',
            'requested_by' => $employeeUser->id,
            'approved_by' => $employeeUser->id,
            'approved_at' => now(),
            'status' => 'approved',
        ]);
        $period = \App\Models\PayrollPeriod::create([
            'branch_id' => $branch->id,
            'period_code' => 'CHARGE-FINAL-APPROVAL',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'payment_date' => now()->endOfMonth(),
            'status' => 'completed',
            'approved_at' => now(),
            'hr_approved_at' => now(),
            'branch_approved_at' => now(),
            'admin_approval_stage' => 'bh_approved',
            'finance_submitted_at' => now(),
        ]);
        \App\Models\PayrollEntry::create([
            'payroll_period_id' => $period->id,
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'cash_charge_id' => $charge->id,
            'cash_charge_deduction' => 100,
            'status' => 'approved',
        ]);
        $draftPeriod = \App\Models\PayrollPeriod::create([
            'branch_id' => $branch->id,
            'period_code' => 'CHARGE-DRAFT-APPROVAL',
            'start_date' => now()->startOfMonth()->subMonth(),
            'end_date' => now()->endOfMonth()->subMonth(),
            'payment_date' => now()->endOfMonth()->subMonth(),
            'status' => 'draft',
        ]);
        \App\Models\PayrollEntry::create([
            'payroll_period_id' => $draftPeriod->id,
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'cash_charge_id' => $charge->id,
            'cash_charge_deduction' => 100,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($employeeUser)->get(route('admin.cash-charges.index'));

        $response->assertOk();
        $response->assertSee('value="900.00"', false);
        $response->assertSee('value="100.00"', false);
        $this->assertSame(1000.0, (float) $charge->fresh()->amount);
    }

    public function test_save_persists_edited_installment_value_for_pending_request(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-556',
            'branch_name' => 'Edited Installment Branch',
            'address' => 'Edited Installment Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Edited Installment Employee',
            'email' => 'edited-installment@example.com',
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
            'employee_number' => 'EMP-556',
            'first_name' => 'Edited',
            'last_name' => 'Installment',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        \App\Models\LeaveBalance::firstOrCreate(
            ['employee_profile_id' => $profile->id, 'year' => now()->year],
            [
                'sick_leave_used' => 0,
                'vacation_leave_used' => 0,
                'emergency_leave_used' => 0,
                'birthday_leave_used' => 0,
                'cash_charge_total' => 3000,
                'cash_charge_used' => 0,
                'maternity_leave_used' => 0,
                'paternity_leave_used' => 0,
            ]
        );

        $response = $this->actingAs($employeeUser)->post(route('admin.cash-charges.request-update', $profile), [
            'cash_charge_total' => '3000',
            'installment_per_cutoff' => '500',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cash_charges', [
            'employee_profile_id' => $profile->id,
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
            'amount' => '3000.00',
            'installment_per_cutoff' => '500.00',
        ]);
    }

    public function test_cash_charge_balance_update_accepts_installment_edit_and_requires_approval(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-550',
            'branch_name' => 'Installment Branch',
            'address' => 'Installment Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Installment Employee',
            'email' => 'installment-employee@example.com',
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
            'employee_number' => 'EMP-550',
            'first_name' => 'Installment',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        $response = $this->actingAs($employeeUser)->post(route('admin.cash-charges.request-update', $profile), [
            'installment_per_cutoff' => '1500',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cash_charges', [
            'employee_profile_id' => $profile->id,
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
            'amount' => '3000.00',
            'installment_per_cutoff' => '1500.00',
        ]);
    }

    public function test_branch_admin_sees_action_buttons_for_pending_branch_approval(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-520',
            'branch_name' => 'Branch Action Branch',
            'address' => 'Branch Action Address',
        ]);

        $branchAdmin = User::create([
            'name' => 'Branch Action Admin',
            'email' => 'branch-action-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeUser = User::create([
            'name' => 'Branch Action Employee',
            'email' => 'branch-action-employee@example.com',
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
            'employee_number' => 'EMP-520',
            'first_name' => 'Branch',
            'last_name' => 'Action',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        $adminProfile = EmployeeProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-521',
            'first_name' => 'Branch',
            'last_name' => 'Admin',
            'position' => 'Branch Admin',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        AdminProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_profile_id' => $adminProfile->id,
            'employee_number' => 'ADM-520',
            'first_name' => 'Branch',
            'last_name' => 'Admin',
            'position' => 'Branch Admin',
            'admin_level' => 'branch_admin',
            'date_hired' => '2024-01-15',
        ]);

        \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 2400,
            'reason' => 'Branch admin review pending',
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
        ]);

        $response = $this->actingAs($branchAdmin)->get(route('admin.cash-charges.index'));

        $response->assertOk();
        $response->assertSeeText('Approve');
        $response->assertSeeText('Reject');
        $response->assertSeeText('Pending Branch Admin');
    }

    public function test_super_admin_sees_action_buttons_for_pending_approval_states(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-525',
            'branch_name' => 'Action State Branch',
            'address' => 'Action Address',
        ]);

        $superAdmin = User::create([
            'name' => 'Action Super Admin',
            'email' => 'action-super-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $branchPendingUser = User::create([
            'name' => 'Action Employee Branch',
            'email' => 'action-employee-branch@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $branchPendingProfile = EmployeeProfile::create([
            'user_id' => $branchPendingUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-525A',
            'first_name' => 'Action',
            'last_name' => 'Branch',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $branchPendingUser->profile_type = EmployeeProfile::class;
        $branchPendingUser->profile_id = $branchPendingProfile->id;
        $branchPendingUser->save();

        $superPendingUser = User::create([
            'name' => 'Action Employee Super',
            'email' => 'action-employee-super@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $superPendingProfile = EmployeeProfile::create([
            'user_id' => $superPendingUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-525B',
            'first_name' => 'Action',
            'last_name' => 'Super',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $superPendingUser->profile_type = EmployeeProfile::class;
        $superPendingUser->profile_id = $superPendingProfile->id;
        $superPendingUser->save();

        \App\Models\CashCharge::create([
            'employee_profile_id' => $branchPendingProfile->id,
            'branch_id' => $branch->id,
            'amount' => 2500,
            'reason' => 'Pending branch admin review',
            'requested_by' => $branchPendingUser->id,
            'status' => 'pending_branch_admin',
        ]);

        \App\Models\CashCharge::create([
            'employee_profile_id' => $superPendingProfile->id,
            'branch_id' => $branch->id,
            'amount' => 3000,
            'reason' => 'Pending super admin review',
            'requested_by' => $superPendingUser->id,
            'status' => 'pending_super_admin',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.cash-charges.index'));

        $response->assertOk();
        $response->assertSeeText('Approve');
        $response->assertSeeText('Reject');
        $response->assertSeeText('Pending Branch Admin');
        $response->assertSeeText('Pending Super Admin');
    }

    public function test_legacy_pending_cash_charge_still_shows_review_buttons_for_branch_admin(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-642',
            'branch_name' => 'Legacy Pending Branch',
            'address' => 'Legacy Pending Address',
        ]);

        $branchAdmin = User::create([
            'name' => 'Legacy Pending Branch Admin',
            'email' => 'legacy-pending-branch-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeUser = User::create([
            'name' => 'Legacy Pending Employee',
            'email' => 'legacy-pending-employee@example.com',
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
            'employee_number' => 'EMP-642',
            'first_name' => 'Legacy',
            'last_name' => 'Pending',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        $adminProfile = EmployeeProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-643',
            'first_name' => 'Legacy',
            'last_name' => 'Branch Admin',
            'position' => 'Branch Admin',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        AdminProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_profile_id' => $adminProfile->id,
            'employee_number' => 'ADM-642',
            'first_name' => 'Legacy',
            'last_name' => 'Branch Admin',
            'position' => 'Branch Admin',
            'admin_level' => 'branch_admin',
            'date_hired' => '2024-01-15',
        ]);

        \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 4200,
            'reason' => 'Legacy pending request waiting review',
            'requested_by' => $employeeUser->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($branchAdmin)->get(route('admin.cash-charges.index'));

        $response->assertOk();
        $response->assertSeeText('Approve');
        $response->assertSeeText('Reject');
        $response->assertSeeText('Pending Branch Admin');
    }

    public function test_admin_sees_only_latest_pending_request_and_installment_amount(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-620',
            'branch_name' => 'Admin Latest Branch',
            'address' => 'Admin Latest Address',
        ]);

        $branchAdmin = User::create([
            'name' => 'Admin Latest',
            'email' => 'admin-latest@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeUser = User::create([
            'name' => 'Latest Admin Employee',
            'email' => 'latest-admin-employee@example.com',
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
            'employee_number' => 'EMP-620',
            'first_name' => 'Latest',
            'last_name' => 'Admin',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        $adminProfile = EmployeeProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-621',
            'first_name' => 'Branch',
            'last_name' => 'Admin',
            'position' => 'Branch Admin',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        AdminProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_profile_id' => $adminProfile->id,
            'employee_number' => 'ADM-620',
            'first_name' => 'Branch',
            'last_name' => 'Admin',
            'position' => 'Branch Admin',
            'admin_level' => 'branch_admin',
            'date_hired' => '2024-01-15',
        ]);

        \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 2000,
            'installment_per_cutoff' => 1000,
            'reason' => 'Old pending request',
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 3000,
            'installment_per_cutoff' => 1500,
            'reason' => 'Newest pending request',
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
        ]);

        $response = $this->actingAs($branchAdmin)->get(route('admin.cash-charges.index'));

        $response->assertOk();
        $response->assertSeeText('Newest pending request');
        $response->assertDontSeeText('Old pending request');
        $response->assertSee('₱1,500.00');
        $response->assertSeeText('Installment Amount');
    }

    public function test_employee_only_sees_latest_pending_request_in_my_charges(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-610',
            'branch_name' => 'Latest Pending Branch',
            'address' => 'Latest Pending Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Latest Pending Employee',
            'email' => 'latest-pending@example.com',
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
            'employee_number' => 'EMP-610',
            'first_name' => 'Latest',
            'last_name' => 'Pending',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        $older = \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 2000,
            'reason' => 'Old pending request',
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $newer = \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 5500,
            'reason' => 'Newest pending request',
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
        ]);

        $response = $this->actingAs($employeeUser)->get(route('admin.cash-charges.index'));

        $response->assertOk();
        $response->assertSeeText('Newest pending request');
        $response->assertDontSeeText('Old pending request');
        $response->assertSee('₱5,500.00');
        $this->assertDatabaseHas('cash_charges', ['id' => $newer->id, 'status' => 'pending_branch_admin']);
    }

    public function test_employee_sees_pending_request_amount_and_installment_before_approval(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-600',
            'branch_name' => 'Pending Summary Branch',
            'address' => 'Pending Summary Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Pending Summary Employee',
            'email' => 'pending-summary@example.com',
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
            'employee_number' => 'EMP-600',
            'first_name' => 'Pending',
            'last_name' => 'Summary',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 3000,
            'reason' => 'Cash charge balance adjustment request pending approval.',
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
        ]);

        \App\Models\CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 3000,
            'installment_per_cutoff' => 1500,
            'reason' => 'Cash charge balance adjustment request pending approval.',
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
        ]);

        $response = $this->actingAs($employeeUser)->get(route('admin.cash-charges.index'));

        $response->assertOk();
        $response->assertSee('3000.00');
        $response->assertSee('1500.00');
        $response->assertSeeText('Pending Branch Admin');
    }

    public function test_cash_charge_balance_update_requires_branch_and_super_admin_approval(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-500',
            'branch_name' => 'Approval Branch',
            'address' => 'Approval Address',
        ]);

        $employeeUser = User::create([
            'name' => 'Approval Employee',
            'email' => 'approval-employee@example.com',
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
            'employee_number' => 'EMP-500',
            'first_name' => 'Approval',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        $branchAdmin = User::create([
            'name' => 'Approval Branch Admin',
            'email' => 'approval-branch-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $branchAdminEmployee = EmployeeProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-501',
            'first_name' => 'Approval',
            'last_name' => 'Branch Admin',
            'position' => 'Branch Admin',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        AdminProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_profile_id' => $branchAdminEmployee->id,
            'employee_number' => 'ADM-501',
            'first_name' => 'Approval',
            'last_name' => 'Branch Admin',
            'position' => 'Branch Admin',
            'admin_level' => 'branch_admin',
            'date_hired' => '2024-01-15',
        ]);

        $superAdmin = User::create([
            'name' => 'Approval Super Admin',
            'email' => 'approval-super-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $leaveBalance = \App\Models\LeaveBalance::firstOrCreate(
            ['employee_profile_id' => $profile->id, 'year' => now()->year],
            [
                'sick_leave_used' => 0,
                'vacation_leave_used' => 0,
                'emergency_leave_used' => 0,
                'birthday_leave_used' => 0,
                'cash_charge_total' => 1200,
                'cash_charge_used' => 0,
                'maternity_leave_used' => 0,
                'paternity_leave_used' => 0,
            ]
        );

        $response = $this->actingAs($employeeUser)->post(route('admin.cash-charges.request-update', $profile), [
            'cash_charge_total' => '3200',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cash_charges', [
            'employee_profile_id' => $profile->id,
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
            'amount' => '3200.00',
        ]);

        $approvalRequest = \App\Models\CashCharge::where('employee_profile_id', $profile->id)->latest()->first();

        $this->actingAs($branchAdmin)->post(route('admin.cash-charges.approve', $approvalRequest), [
            'decision' => 'approve',
        ])->assertRedirect();

        $approvalRequest->refresh();
        $this->assertSame('pending_super_admin', $approvalRequest->status);

        $this->actingAs($superAdmin)->post(route('admin.cash-charges.approve', $approvalRequest), [
            'decision' => 'approve',
        ])->assertRedirect();

        $approvalRequest->refresh();
        $this->assertSame('approved', $approvalRequest->status);
        $this->assertSame(3200.0, (float) $leaveBalance->fresh()->cash_charge_total);
    }

    public function test_branch_admin_my_charges_scope_only_shows_their_own_entries(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-400',
            'branch_name' => 'My Charges Branch',
            'address' => 'Branch Charges Address',
        ]);

        $branchAdmin = User::create([
            'name' => 'Branch Admin Scope',
            'email' => 'branch-admin-scope@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeProfile = EmployeeProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-700',
            'first_name' => 'Scope',
            'last_name' => 'Branch Admin',
            'position' => 'Branch Admin',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        AdminProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_profile_id' => $employeeProfile->id,
            'employee_number' => 'ADM-700',
            'first_name' => 'Scope',
            'last_name' => 'Branch Admin',
            'position' => 'Branch Admin',
            'admin_level' => 'branch_admin',
            'date_hired' => '2024-01-15',
        ]);

        $charge = \App\Models\CashCharge::create([
            'employee_profile_id' => $employeeProfile->id,
            'branch_id' => $branch->id,
            'amount' => 1800,
            'reason' => 'My personal emergency',
            'requested_by' => $branchAdmin->id,
            'status' => 'pending',
        ]);

        $otherEmployee = User::create([
            'name' => 'Other Employee',
            'email' => 'other-employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $otherProfile = EmployeeProfile::create([
            'user_id' => $otherEmployee->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-701',
            'first_name' => 'Other',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        \App\Models\CashCharge::create([
            'employee_profile_id' => $otherProfile->id,
            'branch_id' => $branch->id,
            'amount' => 2800,
            'reason' => 'Other employee charge',
            'requested_by' => $branchAdmin->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($branchAdmin)->get(route('admin.cash-charges.index', ['scope' => 'my']));

        $response->assertOk();
        $response->assertSeeText('My Charges');
        $response->assertSeeText('My personal emergency');
        $response->assertDontSeeText('Other employee charge');
        $response->assertDontSeeText('Add Charges');
        $response->assertDontSeeText('Cash Charge Requests');
        $response->assertDontSeeText('Active Users');
        $this->assertDatabaseHas('cash_charges', ['id' => $charge->id, 'requested_by' => $branchAdmin->id]);
    }

    public function test_cash_charge_management_groups_processed_records_in_archives(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-600',
            'branch_name' => 'Archives Branch',
            'address' => 'Archives Address',
        ]);

        $superAdmin = User::create([
            'name' => 'Archives Super Admin',
            'email' => 'archives-super-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeUser = User::create([
            'name' => 'Archives Employee',
            'email' => 'archives-employee@example.com',
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
            'employee_number' => 'EMP-600',
            'first_name' => 'Archives',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->profile_id = $profile->id;
        $employeeUser->save();

        $pending = CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 2500,
            'installment_per_cutoff' => 1250,
            'reason' => 'Waiting for approval.',
            'requested_by' => $employeeUser->id,
            'status' => 'pending_branch_admin',
        ]);

        $approved = CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 3000,
            'installment_per_cutoff' => 1500,
            'reason' => 'Already approved.',
            'requested_by' => $employeeUser->id,
            'status' => 'approved',
            'approved_by' => $superAdmin->id,
            'approved_at' => now(),
        ]);

        $rejected = CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 1800,
            'installment_per_cutoff' => 900,
            'reason' => 'Rejected earlier.',
            'requested_by' => $employeeUser->id,
            'status' => 'rejected',
            'approved_by' => $superAdmin->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.cash-charges.index'));

        $response->assertOk();
        $response->assertSeeText('Cash Charge Requests');
        $response->assertSeeText('Archives');
        $response->assertSeeText('Waiting for approval.');
        $response->assertSeeText('Already approved.');
        $response->assertSeeText('Rejected earlier.');
    }

    public function test_cash_charge_can_be_archived_and_unarchived(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-700',
            'branch_name' => 'Archive Flow Branch',
            'address' => 'Archive Flow Address',
        ]);

        $superAdmin = User::create([
            'name' => 'Archive Flow Admin',
            'email' => 'archive-flow-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeUser = User::create([
            'name' => 'Archive Flow Employee',
            'email' => 'archive-flow-employee@example.com',
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
            'employee_number' => 'EMP-700',
            'first_name' => 'Archive',
            'last_name' => 'Flow',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $charge = CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 4500,
            'installment_per_cutoff' => 2250,
            'reason' => 'Archive flow test data',
            'requested_by' => $employeeUser->id,
            'status' => 'approved',
            'approved_by' => $superAdmin->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->post(route('admin.cash-charges.archive', $charge))
            ->assertRedirect();

        $charge->refresh();
        $this->assertSame('archived', $charge->status);
        $this->assertSame('approved', $charge->archived_from_status);

        $this->actingAs($superAdmin)
            ->post(route('admin.cash-charges.unarchive', $charge))
            ->assertRedirect();

        $charge->refresh();
        $this->assertSame('approved', $charge->status);
        $this->assertNull($charge->archived_from_status);
    }

    public function test_archived_cash_charge_redirects_without_exception_when_archived_again(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-701',
            'branch_name' => 'Archive Guard Branch',
            'address' => 'Archive Guard Address',
        ]);

        $superAdmin = User::create([
            'name' => 'Archive Guard Admin',
            'email' => 'archive-guard-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeUser = User::create([
            'name' => 'Archive Guard Employee',
            'email' => 'archive-guard-employee@example.com',
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
            'employee_number' => 'EMP-701',
            'first_name' => 'Archive',
            'last_name' => 'Guard',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        $charge = CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 2200,
            'installment_per_cutoff' => 1100,
            'reason' => 'Duplicate archive guard test',
            'requested_by' => $employeeUser->id,
            'status' => 'archived',
            'archived_from_status' => 'approved',
            'approved_by' => $superAdmin->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($superAdmin)
            ->from(route('admin.cash-charges.index'))
            ->post(route('admin.cash-charges.archive', $charge));

        $response->assertRedirect(route('admin.cash-charges.index'));
        $response->assertSessionHas('warning', 'This cash charge is already archived.');
    }

    public function test_archived_cash_charges_are_scoped_to_the_role_that_archived_them(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-702',
            'branch_name' => 'Archive Scope Branch',
            'address' => 'Archive Scope Address',
        ]);

        $branchAdmin = User::create([
            'name' => 'Scope Branch Admin',
            'email' => 'scope-branch-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $branchAdminProfile = AdminProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'ADM-BR-702',
            'first_name' => 'Scope',
            'last_name' => 'Branch Admin',
            'position' => 'Branch Admin',
            'date_hired' => '2024-01-15',
            'is_fingerprint_registered' => false,
        ]);
        $branchAdmin->profile_type = AdminProfile::class;
        $branchAdmin->profile_id = $branchAdminProfile->id;
        $branchAdmin->save();

        $superAdmin = User::create([
            'name' => 'Scope Super Admin',
            'email' => 'scope-super-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $superAdminProfile = AdminProfile::create([
            'user_id' => $superAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'ADM-SUP-702',
            'first_name' => 'Scope',
            'last_name' => 'Super Admin',
            'position' => 'Super Admin',
            'date_hired' => '2024-01-15',
            'is_fingerprint_registered' => false,
        ]);
        $superAdmin->profile_type = AdminProfile::class;
        $superAdmin->profile_id = $superAdminProfile->id;
        $superAdmin->save();

        $employeeUser = User::create([
            'name' => 'Scope Employee',
            'email' => 'scope-employee@example.com',
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
            'employee_number' => 'EMP-702',
            'first_name' => 'Scope',
            'last_name' => 'Employee',
            'position' => 'Staff',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 2000,
            'installment_per_cutoff' => 1000,
            'reason' => 'Employee archive item',
            'requested_by' => $employeeUser->id,
            'status' => 'archived',
            'archived_by_role' => 'employee',
            'archived_from_status' => 'pending',
            'approved_by' => $superAdmin->id,
            'approved_at' => now(),
        ]);

        CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 3000,
            'installment_per_cutoff' => 1500,
            'reason' => 'Branch admin archive item',
            'requested_by' => $employeeUser->id,
            'status' => 'archived',
            'archived_by_role' => 'branch_admin',
            'archived_from_status' => 'pending_branch_admin',
            'approved_by' => $superAdmin->id,
            'approved_at' => now(),
        ]);

        CashCharge::create([
            'employee_profile_id' => $profile->id,
            'branch_id' => $branch->id,
            'amount' => 4000,
            'installment_per_cutoff' => 2000,
            'reason' => 'Super admin archive item',
            'requested_by' => $employeeUser->id,
            'status' => 'archived',
            'archived_by_role' => 'super_admin',
            'archived_from_status' => 'pending_super_admin',
            'approved_by' => $superAdmin->id,
            'approved_at' => now(),
        ]);

        $branchAdminResponse = $this->actingAs($branchAdmin)->get(route('admin.cash-charges.index'));
        $branchAdminResponse->assertOk();
        $branchAdminResponse->assertDontSeeText('Branch admin archive item');
        $branchAdminResponse->assertDontSeeText('Employee archive item');
        $branchAdminResponse->assertDontSeeText('Super admin archive item');
        $branchAdminResponse->assertDontSeeText('Archives');

        $superAdminResponse = $this->actingAs($superAdmin)->get(route('admin.cash-charges.index'));
        $superAdminResponse->assertOk();
        $superAdminResponse->assertSeeText('Super admin archive item');
        $superAdminResponse->assertDontSeeText('Branch admin archive item');
        $superAdminResponse->assertDontSeeText('Employee archive item');

        $employeeResponse = $this->actingAs($employeeUser)->get(route('admin.cash-charges.index', ['scope' => 'my']));
        $employeeResponse->assertOk();
        $employeeResponse->assertDontSeeText('Employee archive item');
        $employeeResponse->assertDontSeeText('Branch admin archive item');
        $employeeResponse->assertDontSeeText('Super admin archive item');
        $employeeResponse->assertDontSeeText('Archives');
    }

    public function test_super_admin_leave_management_includes_branch_admins_and_finance_officers(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-100',
            'branch_name' => 'South Branch',
            'address' => 'South Address',
        ]);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super-admin-2@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $financeUser = User::create([
            'name' => 'Finance Officer',
            'email' => 'finance1@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance_officer',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
            'profile_type' => FinanceProfile::class,
            'profile_id' => null,
        ]);

        $employeeProfile = EmployeeProfile::create([
            'user_id' => $financeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-200',
            'first_name' => 'Finance',
            'last_name' => 'Officer',
            'position' => 'Finance Officer',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        FinanceProfile::create([
            'user_id' => $financeUser->id,
            'branch_id' => $branch->id,
            'employee_profile_id' => $employeeProfile->id,
            'employee_number' => 'FIN-200',
            'first_name' => 'Finance',
            'last_name' => 'Officer',
            'position' => 'Finance Officer',
            'status' => '1 Year of Service',
            'date_hired' => '2024-01-15',
        ]);

        $branchAdminUser = User::create([
            'name' => 'Branch Admin',
            'email' => 'branchadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
            'profile_type' => AdminProfile::class,
            'profile_id' => null,
        ]);

        $branchAdminEmployee = EmployeeProfile::create([
            'user_id' => $branchAdminUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-300',
            'first_name' => 'Branch',
            'last_name' => 'Admin',
            'position' => 'Branch Admin',
            'date_hired' => '2024-01-15',
            'status' => '1 Year of Service',
        ]);

        AdminProfile::create([
            'user_id' => $branchAdminUser->id,
            'branch_id' => $branch->id,
            'employee_profile_id' => $branchAdminEmployee->id,
            'employee_number' => 'ADM-300',
            'first_name' => 'Branch',
            'last_name' => 'Admin',
            'position' => 'Branch Admin',
            'admin_level' => 'branch_admin',
            'date_hired' => '2024-01-15',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.leave-credits.index'));

        $response->assertOk();
        $response->assertSeeText('Finance Officer');
        $response->assertSeeText('Branch Admin');
    }
}
