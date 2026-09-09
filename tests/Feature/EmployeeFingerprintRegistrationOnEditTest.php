<?php

namespace Tests\Feature;

use App\Models\AdminProfile;
use App\Models\Branch;
use App\Models\EmployeeProfile;
use App\Models\FinanceProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmployeeFingerprintRegistrationOnEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_admin_can_save_finance_fingerprint_data_from_edit_page(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-FIN-EDIT',
            'branch_name' => 'Finance Edit Branch',
            'address' => 'Finance Edit Address',
        ]);

        $branchAdmin = User::create([
            'name' => 'Branch Admin',
            'email' => 'branch.admin.finance.edit@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $adminProfile = \App\Models\AdminProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'ADM-BR-EDIT-01',
            'first_name' => 'Branch',
            'last_name' => 'Admin',
            'position' => 'Branch Admin',
            'date_hired' => now(),
            'is_fingerprint_registered' => false,
        ]);

        $branchAdmin->profile_id = $adminProfile->id;
        $branchAdmin->profile_type = \App\Models\AdminProfile::class;
        $branchAdmin->save();

        $financeUser = User::create([
            'name' => 'Finance Officer',
            'email' => 'finance.edit.branch@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $financeProfile = FinanceProfile::create([
            'user_id' => $financeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'FIN-EDIT-01',
            'first_name' => 'Finance',
            'last_name' => 'Officer',
            'position' => 'Finance Officer',
            'basic_salary' => 30000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $financeUser->profile_id = $financeProfile->id;
        $financeUser->profile_type = FinanceProfile::class;
        $financeUser->save();

        $fingerprintData = 'sample-fingerprint-template-for-finance-edit-' . str_repeat('B', 200);

        $response = $this
            ->actingAs($branchAdmin)
            ->put(route('admin.user-update', ['role' => 'finance', 'id' => $financeProfile->id]), [
                'first_name' => 'Finance Updated',
                'last_name' => 'Officer Updated',
                'position' => 'Senior Finance Officer',
                'email' => 'finance.updated.branch@example.com',
                'status' => 'Regular',
                'date_hired' => now()->format('Y-m-d'),
                'can_process_payroll' => '1',
                'is_active' => '1',
                'fingerprint_data' => $fingerprintData,
            ]);

        $response->assertRedirect(route('admin.employees', ['finance' => 1], false));

        $financeProfile->refresh();

        $this->assertSame('Finance Updated', $financeProfile->first_name);
        $this->assertSame('Officer Updated', $financeProfile->last_name);
        $this->assertSame('Senior Finance Officer', $financeProfile->position);
        $this->assertDatabaseHas('finance_profiles', [
            'id' => $financeProfile->id,
            'pending_changes->fingerprint_template' => base64_encode($fingerprintData),
        ]);
        $this->assertDatabaseHas('finance_profiles', [
            'id' => $financeProfile->id,
            'pending_changes->is_fingerprint_registered' => true,
        ]);
    }

    public function test_branch_admin_finance_update_sanitizes_invalid_utf8_in_pending_fingerprint_changes(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-FIN-UTF8',
            'branch_name' => 'UTF8 Finance Branch',
            'address' => 'UTF8 Finance Address',
        ]);

        $branchAdmin = User::create([
            'name' => 'Branch Admin',
            'email' => 'branch.admin.utf8@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $adminProfile = \App\Models\AdminProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'ADM-UTF8-01',
            'first_name' => 'Branch',
            'last_name' => 'Admin',
            'position' => 'Branch Admin',
            'date_hired' => now(),
            'is_fingerprint_registered' => false,
        ]);

        $branchAdmin->profile_id = $adminProfile->id;
        $branchAdmin->profile_type = \App\Models\AdminProfile::class;
        $branchAdmin->save();

        $financeUser = User::create([
            'name' => 'Finance Officer',
            'email' => 'finance.utf8@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $financeProfile = FinanceProfile::create([
            'user_id' => $financeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'FIN-UTF8-01',
            'first_name' => 'Finance',
            'last_name' => 'Officer',
            'position' => 'Finance Officer',
            'basic_salary' => 30000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $financeUser->profile_id = $financeProfile->id;
        $financeUser->profile_type = FinanceProfile::class;
        $financeUser->save();

        $invalidUtf8 = "bad\xFF\xFEfingerprint";

        $response = $this
            ->actingAs($branchAdmin)
            ->put(route('admin.user-update', ['role' => 'finance', 'id' => $financeProfile->id]), [
                'first_name' => 'Finance',
                'last_name' => 'Officer',
                'position' => 'Finance Officer',
                'email' => 'finance.utf8@example.com',
                'status' => 'Regular',
                'date_hired' => now()->format('Y-m-d'),
                'can_process_payroll' => '1',
                'is_active' => '1',
                'fingerprint_data' => $invalidUtf8,
            ]);

        $response->assertRedirect(route('admin.employees', ['finance' => 1], false));

        $financeProfile->refresh();
        $this->assertNotNull($financeProfile->pending_changes);
        $this->assertArrayHasKey('fingerprint_template', $financeProfile->pending_changes);
        $this->assertIsString($financeProfile->pending_changes['fingerprint_template']);
        $this->assertMatchesRegularExpression('/^[\\x20-\\x7E\\r\\n]+$/', $financeProfile->pending_changes['fingerprint_template']);
    }

    public function test_admin_can_save_employee_fingerprint_data_from_edit_page(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-EDIT',
            'branch_name' => 'Edit Branch',
            'address' => 'Edit Address',
        ]);

        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin-edit@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeUser = User::create([
            'name' => 'Jane Employee',
            'email' => 'jane.employee@example.com',
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
            'employee_number' => 'EMP-EDIT-01',
            'first_name' => 'Jane',
            'last_name' => 'Employee',
            'position' => 'Cashier',
            'basic_salary' => 25000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $employeeUser->profile_id = $employee->id;
        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->save();

        $fingerprintData = 'sample-fingerprint-template-for-employee-edit-' . str_repeat('A', 200);

        $response = $this
            ->actingAs($admin)
            ->put(route('admin.employee-update', $employee->id), [
                'first_name' => 'Jane',
                'last_name' => 'Employee',
                'position' => 'Senior Cashier',
                'branch_id' => $branch->id,
                'role' => 'employee',
                'basic_salary' => '26000',
                'is_active' => '1',
                'date_hired' => now()->format('Y-m-d'),
                'status' => 'Regular',
                'fingerprint_data' => $fingerprintData,
            ]);

        $response->assertRedirect(route('admin.employees', absolute: false));

        $this->assertDatabaseHas('employee_profiles', [
            'id' => $employee->id,
            'fingerprint_template' => $fingerprintData,
            'is_fingerprint_registered' => true,
        ]);
    }

    public function test_branch_admin_finance_employee_list_renders_without_collection_query_error(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-FIN-02',
            'branch_name' => 'Branch Finance Branch',
            'address' => 'Branch Finance Address',
        ]);

        $branchAdmin = User::create([
            'name' => 'Branch Admin',
            'email' => 'branch.admin.finance@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $financeUser = User::create([
            'name' => 'Finance Officer',
            'email' => 'branch.finance.officer@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $financeProfile = FinanceProfile::create([
            'user_id' => $financeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'FIN-BR-001',
            'first_name' => 'Branch',
            'last_name' => 'Finance',
            'position' => 'Finance Officer',
            'basic_salary' => 30000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $financeUser->profile_id = $financeProfile->id;
        $financeUser->profile_type = FinanceProfile::class;
        $financeUser->save();

        $response = $this
            ->actingAs($branchAdmin)
            ->get(route('admin.employees', ['finance' => 1]));

        $response->assertOk();
        $response->assertSee('Finance Officer');
    }

    public function test_finance_employee_list_renders_without_undefined_hasregisteredfingerprint_property(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-FIN-01',
            'branch_name' => 'Finance Branch',
            'address' => 'Finance Address',
        ]);

        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin-finance-list@example.com',
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
            'email' => 'finance.officer@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $financeProfile = FinanceProfile::create([
            'user_id' => $financeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'FIN-001',
            'first_name' => 'Finance',
            'last_name' => 'Officer',
            'position' => 'Finance Officer',
            'basic_salary' => 30000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $financeUser->profile_id = $financeProfile->id;
        $financeUser->profile_type = FinanceProfile::class;
        $financeUser->save();

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.employees', ['finance' => 1]));

        $response->assertOk();
        $response->assertSee('Not Registered');
    }

    public function test_finance_officer_can_only_view_own_dtr(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-FIN-OWN',
            'branch_name' => 'Own DTR Branch',
            'address' => 'Own DTR Address',
        ]);

        $financeUser = User::create([
            'name' => 'Finance Officer',
            'email' => 'finance.own.dtr@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance_officer',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $financeProfile = FinanceProfile::create([
            'user_id' => $financeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'FIN-OWN-001',
            'first_name' => 'Finance',
            'last_name' => 'Officer',
            'position' => 'Finance Officer',
            'basic_salary' => 30000,
            'date_hired' => now(),
            'fingerprint_template' => null,
            'is_fingerprint_registered' => false,
        ]);

        $financeUser->profile_id = $financeProfile->id;
        $financeUser->profile_type = FinanceProfile::class;
        $financeUser->save();

        $ownEmployee = EmployeeProfile::create([
            'user_id' => $financeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'FIN-SELF-001',
            'first_name' => 'Finance',
            'last_name' => 'Self',
            'position' => 'Finance Officer',
            'basic_salary' => 30000,
            'date_hired' => now(),
        ]);

        $financeProfile->employee_profile_id = $ownEmployee->id;
        $financeProfile->save();

        $otherEmployeeUser = User::create([
            'name' => 'Other Employee',
            'email' => 'other.employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $otherEmployee = EmployeeProfile::create([
            'user_id' => $otherEmployeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-OTHER-001',
            'first_name' => 'Other',
            'last_name' => 'Employee',
            'position' => 'Cashier',
            'basic_salary' => 25000,
            'date_hired' => now(),
        ]);

        $otherEmployeeUser->profile_id = $otherEmployee->id;
        $otherEmployeeUser->profile_type = \App\Models\EmployeeProfile::class;
        $otherEmployeeUser->save();

        $ownDtr = \App\Models\DTR::create([
            'employee_profile_id' => $ownEmployee->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'approved',
            'total_hours' => 120,
            'days_present' => 20,
        ]);

        \App\Models\DTR::create([
            'employee_profile_id' => $otherEmployee->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'approved',
            'total_hours' => 100,
            'days_present' => 18,
        ]);

        $response = $this
            ->actingAs($financeUser)
            ->get(route('admin.dtr.index'));

        $response->assertOk();
        $response->assertSee('Finance Self');
        $response->assertDontSee('Other Employee');
    }

    public function test_branch_admin_finance_status_and_date_hired_require_super_admin_approval(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-FIN-APP',
            'branch_name' => 'Approved Finance Branch',
            'address' => 'Approved Finance Address',
        ]);

        $superAdmin = User::create([
            'name' => 'System Admin',
            'email' => 'superadmin.finance.approval@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $branchAdmin = User::create([
            'name' => 'Branch Admin',
            'email' => 'branch.admin.finance.approval@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $adminProfile = \App\Models\AdminProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'BA-APP-002',
            'first_name' => 'Branch',
            'last_name' => 'Admin',
            'position' => 'Branch Manager',
            'department' => 'Operations',
            'admin_level' => 'branch_admin',
            'can_verify_ids' => false,
            'can_create_employees' => false,
            'can_manage_accounts' => false,
            'date_hired' => now(),
        ]);

        $branchAdmin->profile_id = $adminProfile->id;
        $branchAdmin->profile_type = \App\Models\AdminProfile::class;
        $branchAdmin->save();

        $financeUser = User::create([
            'name' => 'Finance Officer',
            'email' => 'finance.status.date@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance_officer',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $financeProfile = FinanceProfile::create([
            'user_id' => $financeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'FIN-APP-002',
            'first_name' => 'Finance',
            'last_name' => 'Officer',
            'position' => 'Finance Officer',
            'status' => 'Active',
            'date_hired' => '2024-01-10',
            'can_process_payroll' => true,
            'basic_salary' => 30000,
        ]);

        $financeUser->profile_id = $financeProfile->id;
        $financeUser->profile_type = FinanceProfile::class;
        $financeUser->save();

        Mail::fake();

        $response = $this
            ->actingAs($branchAdmin)
            ->put(route('admin.user-update', ['role' => 'finance', 'id' => $financeProfile->id]), [
                'first_name' => 'Finance',
                'last_name' => 'Updated',
                'position' => 'Senior Finance Officer',
                'email' => 'finance.approved.example@example.com',
                'can_process_payroll' => '1',
                'status' => '1-2 Years in Service',
                'date_hired' => '2023-05-15',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.employees', ['finance' => 1]));
        $freshProfile = $financeProfile->fresh();
        $this->assertSame('1-2 Years in Service', $freshProfile->status);
        $this->assertSame('2023-05-15', $freshProfile->date_hired instanceof \Carbon\Carbon ? $freshProfile->date_hired->format('Y-m-d') : (string) $freshProfile->date_hired);
        $this->assertNotNull($freshProfile->pending_changes);
        $this->assertSame('1-2 Years in Service', $freshProfile->pending_changes['status']);
        $this->assertSame('2023-05-15', $freshProfile->pending_changes['date_hired']);

        Mail::assertSent(\App\Mail\FinanceOfficerChangePendingApproval::class, function ($mail) use ($superAdmin) {
            return collect($mail->to)->contains(fn ($recipient) => $recipient['address'] === $superAdmin->email);
        });
    }

    public function test_finance_status_approval_updates_leave_profile_status_and_leave_credits(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-FIN-LEAVE',
            'branch_name' => 'Finance Leave Branch',
            'address' => 'Finance Leave Address',
        ]);

        $systemAdmin = User::create([
            'name' => 'System Admin',
            'email' => 'superadmin.leave@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $branchAdmin = User::create([
            'name' => 'Branch Admin',
            'email' => 'branch.admin.leave@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $adminProfile = \App\Models\AdminProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'BA-APP-004',
            'first_name' => 'Branch',
            'last_name' => 'Admin',
            'position' => 'Branch Manager',
            'department' => 'Operations',
            'admin_level' => 'branch_admin',
            'can_verify_ids' => false,
            'can_create_employees' => false,
            'can_manage_accounts' => false,
            'date_hired' => now(),
        ]);

        $branchAdmin->profile_id = $adminProfile->id;
        $branchAdmin->profile_type = \App\Models\AdminProfile::class;
        $branchAdmin->save();

        $financeUser = User::create([
            'name' => 'Finance Officer',
            'email' => 'finance.leave.credits@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance_officer',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeProfile = EmployeeProfile::create([
            'user_id' => $financeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'FIN-LEAVE-001',
            'first_name' => 'Finance',
            'last_name' => 'Officer',
            'position' => 'Finance Officer',
            'basic_salary' => 30000,
            'date_hired' => now()->subYears(2),
            'status' => 'New Hire',
        ]);

        $financeProfile = FinanceProfile::create([
            'user_id' => $financeUser->id,
            'branch_id' => $branch->id,
            'employee_profile_id' => $employeeProfile->id,
            'employee_number' => 'FIN-LEAVE-001',
            'first_name' => 'Finance',
            'last_name' => 'Officer',
            'position' => 'Finance Officer',
            'status' => 'New Hire',
            'date_hired' => now()->subYears(2),
            'can_process_payroll' => true,
            'basic_salary' => 30000,
        ]);

        $financeUser->profile_id = $financeProfile->id;
        $financeUser->profile_type = FinanceProfile::class;
        $financeUser->save();

        $financeProfile->update([
            'pending_changes' => [
                'first_name' => 'Finance',
                'last_name' => 'Officer',
                'position' => 'Finance Officer',
                'email' => $financeUser->email,
                'can_process_payroll' => true,
                'status' => 'Regular',
                'date_hired' => now()->subYears(2)->format('Y-m-d'),
            ],
            'changes_requested_by' => $branchAdmin->id,
            'changes_requested_at' => now(),
        ]);

        $this->actingAs($systemAdmin)
            ->post(route('admin.user-finance-approve', ['id' => $financeProfile->id]))
            ->assertSessionHas('success');

        $approvedEmployeeProfile = $financeProfile->fresh()->employeeProfile()->first();
        $this->assertSame('Regular', $approvedEmployeeProfile->status);

        $this->actingAs($financeUser)
            ->get(route('leave.create'))
            ->assertOk()
            ->assertSee('Status: Regular')
            ->assertSee('3 / 3.0 days');
    }

    public function test_branch_admin_employee_status_change_sends_system_admin_notification(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-STAT-EMAIL',
            'branch_name' => 'Status Email Branch',
            'address' => 'Status Email Address',
        ]);

        $superAdmin = User::create([
            'name' => 'System Admin',
            'email' => 'superadmin.status@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $branchAdmin = User::create([
            'name' => 'Branch Admin',
            'email' => 'branch.admin.status@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $adminProfile = \App\Models\AdminProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'BA-APP-003',
            'first_name' => 'Branch',
            'last_name' => 'Admin',
            'position' => 'Branch Manager',
            'department' => 'Operations',
            'admin_level' => 'branch_admin',
            'can_verify_ids' => false,
            'can_create_employees' => false,
            'can_manage_accounts' => false,
            'date_hired' => now(),
        ]);

        $branchAdmin->profile_id = $adminProfile->id;
        $branchAdmin->profile_type = \App\Models\AdminProfile::class;
        $branchAdmin->save();

        $employeeUser = User::create([
            'name' => 'Sample Employee',
            'email' => 'sample.employee.status@example.com',
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
            'employee_number' => 'EMP-STATUS-EMAIL',
            'first_name' => 'Sample',
            'last_name' => 'Employee',
            'position' => 'Cashier',
            'basic_salary' => 20000,
            'date_hired' => now(),
            'status' => 'New Hire',
        ]);

        $employeeUser->profile_id = $employee->id;
        $employeeUser->profile_type = EmployeeProfile::class;
        $employeeUser->save();

        Mail::fake();

        $this->actingAs($branchAdmin)
            ->put(route('admin.employee-update', $employee->id), [
                'first_name' => 'Sample',
                'last_name' => 'Employee',
                'position' => 'Cashier',
                'branch_id' => $branch->id,
                'role' => 'employee',
                'basic_salary' => '22000',
                'is_active' => '1',
                'date_hired' => now()->format('Y-m-d'),
                'status' => 'Regular',
            ]);

        Mail::assertSent(\App\Mail\EmployeeStatusChangePendingApproval::class, function ($mail) use ($superAdmin, $employee) {
            return collect($mail->to)->contains(fn ($recipient) => $recipient['address'] === $superAdmin->email)
                && $mail->employee->id === $employee->id
                && $mail->newStatus === 'Regular';
        });
    }
}

