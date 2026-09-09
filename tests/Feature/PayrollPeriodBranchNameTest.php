<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPeriodBranchNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_payroll_period_with_name_and_branch(): void
    {
        $branch = Branch::create([
            'branch_code' => 'BR-001',
            'branch_name' => 'Main Branch',
            'address' => 'Test Address',
        ]);

        $admin = User::create([
            'name' => 'Payroll Admin',
            'email' => 'payroll.admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'branch_id' => $branch->id,
            'is_active' => true,
            'is_verified' => true,
            'id_verification_status' => 'approved',
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.payroll.create-period'), [
            'name' => 'August 2026 Payroll',
            'branch_id' => $branch->id,
            'period_type' => 'semi_monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
            'payment_date' => '2026-08-20',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payroll_periods', [
            'name' => 'August 2026 Payroll',
            'branch_id' => $branch->id,
            'period_type' => 'semi_monthly',
        ]);
    }
}
