<?php

namespace Tests\Feature;

use App\Models\AdminProfile;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_admin_can_submit_employee_for_system_admin_approval_without_fingerprint_data(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-001',
            'branch_name' => 'Main Branch',
            'address' => 'Test Address',
        ]);

        $branchAdmin = User::create([
            'name' => 'Branch Admin',
            'email' => 'branchadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $adminProfile = AdminProfile::create([
            'user_id' => $branchAdmin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'BA-001',
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
        $branchAdmin->profile_type = AdminProfile::class;
        $branchAdmin->save();

        $response = $this
            ->withoutMiddleware()
            ->actingAs($branchAdmin)
            ->post(route('admin.employee-store'), [
                'first_name' => 'Test',
                'last_name' => 'Employee',
                'email' => 'newemployee@example.com',
                'employee_number' => 'EMP-9001',
                'branch_id' => $branch->id,
                'position' => 'Cashier',
                'date_hired' => now()->format('Y-m-d'),
                'status' => 'New Hire',
                'role' => 'employee',
                'basic_salary' => '25000',
            ]);

        $response->assertRedirect(route('admin.employees', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'newemployee@example.com',
            'id_verification_status' => 'pending',
            'is_active' => false,
        ]);
    }

    public function test_super_admin_can_create_branch_specific_admin_account(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-002',
            'branch_name' => 'Branch Admin Test',
            'address' => 'Test Address',
        ]);

        $superAdmin = User::create([
            'name' => 'System Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $response = $this
            ->withoutMiddleware()
            ->actingAs($superAdmin)
            ->post(route('admin.branch-heads.store'), [
                'email' => 'branch.admin@example.com',
                'first_name' => 'Branch',
                'last_name' => 'Admin',
                'middle_name' => 'Test',
                'employee_number' => 'ADM-BR-1001',
                'branch_id' => $branch->id,
                'position' => 'Branch Administrator',
                'date_hired' => now()->format('Y-m-d'),
                'contact_number' => '09171234567',
                'address' => 'Test Address',
            ]);

        $response->assertRedirect(route('admin.branch-heads.index', absolute: false));

        $user = User::where('email', 'branch.admin@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('admin', $user->role);
        $this->assertSame('branch_admin', $user->admin_type);
        $this->assertSame($branch->id, $user->branch_id);
        $this->assertDatabaseHas('admin_profiles', [
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'admin_level' => 'branch_admin',
        ]);
    }

    public function test_default_employee_list_shows_employee_profiles_only(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-003',
            'branch_name' => 'Mix Branch',
            'address' => 'Mix Address',
        ]);

        $superAdmin = User::create([
            'name' => 'System Admin',
            'email' => 'mixadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeUser = User::create([
            'name' => 'Regular Employee',
            'email' => 'regemployee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employeeProfile = \App\Models\EmployeeProfile::create([
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-MIX-01',
            'first_name' => 'Regular',
            'last_name' => 'Employee',
            'position' => 'Cashier',
            'date_hired' => now(),
            'status' => 'Regular',
            'basic_salary' => 22000,
        ]);

        $employeeUser->profile_id = $employeeProfile->id;
        $employeeUser->profile_type = \App\Models\EmployeeProfile::class;
        $employeeUser->save();

        $financeUser = User::create([
            'name' => 'Finance Officer',
            'email' => 'finance@example.com',
            'password' => bcrypt('password'),
            'role' => 'finance_officer',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $financeProfile = \App\Models\FinanceProfile::create([
            'user_id' => $financeUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'FIN-MIX-01',
            'first_name' => 'Finance',
            'last_name' => 'Officer',
            'position' => 'Accounting Staff',
            'date_hired' => now(),
            'basic_salary' => 30000,
            'status' => 'Regular',
            'can_process_payroll' => true,
        ]);

        $financeUser->profile_id = $financeProfile->id;
        $financeUser->profile_type = \App\Models\FinanceProfile::class;
        $financeUser->save();

        $adminUser = User::create([
            'name' => 'Branch Admin',
            'email' => 'branchmix@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $branchAdminProfile = \App\Models\AdminProfile::create([
            'user_id' => $adminUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'BA-MIX-01',
            'first_name' => 'Branch',
            'last_name' => 'Admin',
            'position' => 'Branch Administrator',
            'date_hired' => now(),
            'admin_level' => 'branch_admin',
            'can_create_employees' => false,
        ]);

        $adminUser->profile_id = $branchAdminProfile->id;
        $adminUser->profile_type = \App\Models\AdminProfile::class;
        $adminUser->save();

        $response = $this
            ->withoutMiddleware()
            ->actingAs($superAdmin)
            ->get(route('admin.employees', absolute: false));

        $response->assertOk();
        $response->assertViewHas('employees', function ($employees) {
            if (!($employees instanceof \Illuminate\Contracts\Pagination\Paginator) && !($employees instanceof \Illuminate\Pagination\LengthAwarePaginator)) {
                return false;
            }

            $items = collect($employees->items());
            if ($items->isEmpty()) {
                return false;
            }

            return $items->every(fn ($item) => ($item->role ?? null) === 'employee')
                && $items->contains(fn ($item) => ($item->status ?? null) === 'Regular');
        });
    }

    public function test_branch_admin_list_includes_legacy_branch_admin_records(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-004',
            'branch_name' => 'Legacy Branch',
            'address' => 'Legacy Address',
        ]);

        $superAdmin = User::create([
            'name' => 'System Admin',
            'email' => 'legacyadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $branchAdminUser = User::create([
            'name' => 'Legacy Branch Admin',
            'email' => 'legacybranchadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $branchAdminProfile = \App\Models\AdminProfile::create([
            'user_id' => $branchAdminUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'BA-LEG-001',
            'first_name' => 'Legacy',
            'last_name' => 'Branch',
            'position' => 'Branch Administrator',
            'date_hired' => now(),
            'admin_level' => 'branch_admin',
        ]);

        $branchAdminUser->profile_id = $branchAdminProfile->id;
        $branchAdminUser->profile_type = \App\Models\AdminProfile::class;
        $branchAdminUser->save();

        $response = $this
            ->withoutMiddleware()
            ->actingAs($superAdmin)
            ->get(route('admin.branch-heads.index', absolute: false));

        $response->assertOk();
        $response->assertViewHas('branchHeads', function ($branchHeads) {
            return $branchHeads->total() >= 1 && $branchHeads->items() !== [];
        });
    }

    public function test_branch_admin_edit_page_handles_string_date_hired_without_crashing(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-005',
            'branch_name' => 'Edit Branch',
            'address' => 'Edit Address',
        ]);

        $superAdmin = User::create([
            'name' => 'System Admin',
            'email' => 'editadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $branchAdminUser = User::create([
            'name' => 'Editable Branch Admin',
            'email' => 'editablebranchadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $branchAdminProfile = AdminProfile::create([
            'user_id' => $branchAdminUser->id,
            'branch_id' => $branch->id,
            'employee_number' => 'BA-EDIT-001',
            'first_name' => 'Editable',
            'last_name' => 'Branch',
            'position' => 'Branch Administrator',
            'date_hired' => '2023-05-15',
            'admin_level' => 'branch_admin',
        ]);

        $branchAdminUser->profile_id = $branchAdminProfile->id;
        $branchAdminUser->profile_type = \App\Models\AdminProfile::class;
        $branchAdminUser->save();

        $response = $this
            ->withoutMiddleware()
            ->actingAs($superAdmin)
            ->get(route('admin.branch-heads.edit', $branchAdminProfile->id));

        $response->assertOk();
        $response->assertSee('Edit Branch Head Account');
    }

    public function test_deleting_employee_keeps_user_account_in_database(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-002',
            'branch_name' => 'Second Branch',
            'address' => 'Second Address',
        ]);

        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'sysadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $user = User::create([
            'name' => 'Deleted Employee',
            'email' => 'deleteemployee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $employee = \App\Models\EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'employee_number' => 'EMP-DEL-01',
            'first_name' => 'Deleted',
            'last_name' => 'Employee',
            'position' => 'Cashier',
            'date_hired' => now(),
            'basic_salary' => 25000,
        ]);

        $user->profile_id = $employee->id;
        $user->profile_type = \App\Models\EmployeeProfile::class;
        $user->save();

        $response = $this
            ->withoutMiddleware()
            ->actingAs($admin)
            ->delete(route('admin.employee-delete', $employee->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
        $this->assertDatabaseHas('employee_profiles', ['id' => $employee->id]);
    }
}
