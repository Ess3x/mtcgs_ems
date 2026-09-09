<?php

namespace Tests\Feature;

use App\Models\AdminProfile;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCreateEmployeeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_null_admin_type_can_access_create_employee_page(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-100',
            'branch_name' => 'Main Branch',
            'address' => 'Test Address',
        ]);

        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => null,
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        AdminProfile::create([
            'user_id' => $admin->id,
            'branch_id' => $branch->id,
            'employee_number' => 'ADM-001',
            'first_name' => 'System',
            'last_name' => 'Admin',
            'position' => 'System Administrator',
            'department' => 'IT',
            'admin_level' => 'super_admin',
            'can_verify_ids' => true,
            'can_create_employees' => true,
            'can_manage_accounts' => true,
            'date_hired' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.employee-create'));

        $response->assertOk();
    }
}
