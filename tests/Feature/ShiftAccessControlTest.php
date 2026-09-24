<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_shifts_but_cannot_create_them(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-001',
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

        $response = $this->actingAs($superAdmin)->get(route('admin.shifts.index'));

        $response->assertOk();
        $response->assertDontSeeText('Create Shift');
    }

    public function test_super_admin_cannot_create_shift(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-002',
            'branch_name' => 'Secondary Branch',
            'address' => 'Test Address',
        ]);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin2@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'super_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.shifts.store'), [
            'name' => 'Morning Shift',
            'class_code' => 'IT 1211',
            'room' => 'Room 4',
            'start_time' => '07:00',
            'end_time' => '17:00',
            'working_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        ]);

        $response->assertStatus(403);
    }

    public function test_branch_admin_cannot_access_employee_schedule_route(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-003',
            'branch_name' => 'Branch Admin Branch',
            'address' => 'Test Address',
        ]);

        $branchAdmin = User::create([
            'name' => 'Branch Admin',
            'email' => 'branchadmin2@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $response = $this->actingAs($branchAdmin)->get(route('employee.schedule'));

        $response->assertStatus(403);
    }

    public function test_branch_admin_can_create_shift(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-003',
            'branch_name' => 'Branch Admin Branch',
            'address' => 'Test Address',
        ]);

        $branchAdmin = User::create([
            'name' => 'Branch Admin',
            'email' => 'branchadmin2@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $response = $this->actingAs($branchAdmin)->post(route('admin.shifts.store'), [
            'name' => 'Branch Admin Shift',
            'class_code' => 'BSIT 101',
            'room' => 'Room 2',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'working_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shifts', [
            'name' => 'Branch Admin Shift',
        ]);
    }
}
