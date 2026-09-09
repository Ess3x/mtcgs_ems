<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\EmployeeProfile;

class EmployeeID6Seeder extends Seeder
{
    public function run()
    {
        // Create user account for employee 6
        $user = User::firstOrCreate(
            ['email' => 'employee6@mtcgs.com'],
            [
                'name' => 'Test Employee 6',
                'password' => bcrypt('password123'),
                'role' => 'employee',
                'admin_type' => null,
                'id_verification_status' => 'approved',
                'is_active' => true
            ]
        );

        // Create employee profile with ID 6
        $profile = EmployeeProfile::firstOrCreate(
            ['id' => 6],
            [
                'user_id' => $user->id,
                'branch_id' => 1,
                'shift_id' => null,
                'employee_number' => 'EMP006',
                'first_name' => 'Test',
                'last_name' => 'Employee Six',
                'middle_name' => 'Sample',
                'suffix' => '',
                'date_of_birth' => '1995-05-15',
                'gender' => 'Male',
                'civil_status' => 'Single',
                'position' => 'Software Developer',
                'department' => 'IT Department',
                'employment_type' => 'Regular',
                'date_hired' => '2024-01-15',
                'status' => 'active',
                'basic_salary' => 35000.00,
                'hourly_rate' => 168.27,
                'contact_number' => '09123456780',
                'emergency_contact_name' => 'Parent Name',
                'emergency_contact_number' => '09123456781',
                'address' => '123 Sample Street, Metro Manila',
                'is_fingerprint_registered' => false
            ]
        );

        // Update user with profile relationship
        $user->update([
            'profile_type' => EmployeeProfile::class,
            'profile_id' => $profile->id
        ]);

        $this->command->info('Employee ID 6 created successfully!');
        $this->command->info('Name: Test Employee Six');
        $this->command->info('Email: employee6@mtcgs.com');
        $this->command->info('Password: password123');
    }
}
