<?php

namespace Tests\Feature;

use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPendingLeaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_dashboard_counts_system_admin_pending_leaves(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'admin_type' => 'super_admin',
        ]);

        $profile = EmployeeProfile::create([
            'user_id' => $user->id,
            'branch_id' => 1,
            'employee_number' => 'EMP-001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'position' => 'Staff',
            'employment_type' => 'Regular',
            'status' => 'Regular',
            'date_hired' => now()->toDateString(),
            'is_fingerprint_registered' => false,
        ]);

        LeaveRequest::create([
            'employee_id' => $user->id,
            'employee_profile_id' => $profile->id,
            'leave_type' => 'vacation',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'total_days' => 1,
            'reason' => 'Test leave pending system admin review',
            'status' => 'pending_system_admin',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('pendingLeaves', 1);
    }
}
