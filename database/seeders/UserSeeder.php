<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AdminProfile;
use App\Models\FinanceProfile;
use App\Models\EmployeeProfile;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $branch = Branch::first();
        
        if (!$branch) {
            $this->command->error('No branch found! Run BranchSeeder first.');
            return;
        }
        
        // === ADMIN USER ===
        $admin = User::firstOrCreate(
            ['email' => 'admin@mtcgs.edu.ph'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'admin_type' => 'super_admin',
                'id_verification_status' => 'approved',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );
        
        $adminProfile = AdminProfile::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'employee_number' => 'ADMIN001',
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'position' => 'System Administrator',
                'department' => 'IT Administration',
                'admin_level' => 'super_admin',
                'date_hired' => now(),
            ]
        );
        
        $admin->profile_id = $adminProfile->id;
        $admin->profile_type = AdminProfile::class;
        $admin->save();
        
        // === FINANCE OFFICER ===
        $finance = User::firstOrCreate(
            ['email' => 'finance@mtcgs.edu.ph'],
            [
                'name' => 'Finance Officer',
                'password' => Hash::make('finance123'),
                'role' => 'finance_officer',
                'id_verification_status' => 'approved',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );
        
        $financeProfile = FinanceProfile::firstOrCreate(
            ['user_id' => $finance->id],
            [
                'employee_number' => 'FIN001',
                'first_name' => 'Finance',
                'last_name' => 'Officer',
                'position' => 'Finance Officer',
                'department' => 'Finance Department',
                'can_process_payroll' => true,
                'date_hired' => now(),
            ]
        );
        
        $finance->profile_id = $financeProfile->id;
        $finance->profile_type = FinanceProfile::class;
        $finance->save();
        
        // === REGULAR EMPLOYEE ===
        $employee = User::firstOrCreate(
            ['email' => 'employee@mtcgs.edu.ph'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('employee123'),
                'role' => 'employee',
                'id_verification_status' => 'approved',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );
        
        $employeeProfile = EmployeeProfile::firstOrCreate(
            ['user_id' => $employee->id],
            [
                'branch_id' => $branch->id,
                'employee_number' => 'EMP001',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'position' => 'Teacher',
                'department' => 'Academics',
                'employment_type' => 'Regular',
                'date_hired' => now(),
                'basic_salary' => 25000,
            ]
        );
        
        $employee->profile_id = $employeeProfile->id;
        $employee->profile_type = EmployeeProfile::class;
        $employee->save();
        
        $this->command->info('');
        $this->command->info('====================================');
        $this->command->info('USERS CREATED SUCCESSFULLY!');
        $this->command->info('====================================');
        $this->command->info('Admin:    admin@mtcgs.edu.ph / admin123');
        $this->command->info('Finance:  finance@mtcgs.edu.ph / finance123');
        $this->command->info('Employee: employee@mtcgs.edu.ph / employee123');
        $this->command->info('====================================');
    }
}
