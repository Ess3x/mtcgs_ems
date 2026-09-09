<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AdminProfile;
use App\Models\FinanceProfile;
use App\Models\EmployeeProfile;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $branch = Branch::first();
        
        // Admin User
        $admin = User::updateOrCreate(
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
        
        $adminProfile = AdminProfile::updateOrCreate(
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
        
        // Finance Officer
        $finance = User::updateOrCreate(
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
        
        $financeProfile = FinanceProfile::updateOrCreate(
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
        
        // Regular Employee
        $employee = User::updateOrCreate(
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
        
        $employeeProfile = EmployeeProfile::updateOrCreate(
            ['user_id' => $employee->id],
            [
                'branch_id' => $branch->id ?? 1,
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
        
        // Create Ghost Employee with attendance and payroll data
        $this->call(GhostEmployeeSeeder::class);
        
        $this->command->info('Users created successfully!');
        $this->command->info('Admin: admin@mtcgs.edu.ph / admin123');
        $this->command->info('Finance: finance@mtcgs.edu.ph / finance123');
        $this->command->info('Employee: employee@mtcgs.edu.ph / employee123');
        $this->command->info('Ghost Employee: ghost.employee@mtcgs.edu.ph / password123');
        
            // Additional users requested by user
            $extraUsers = [
                ['name' => 'Admin Buhi', 'email' => 'admin.buhi@mtcgs.edu.ph', 'password' => 'password123', 'role' => 'admin'],
                ['name' => 'Admin Iriga', 'email' => 'admin.iriga@mtcgs.edu.ph', 'password' => 'password123', 'role' => 'admin'],
                ['name' => 'Yukka', 'email' => 'Yukka@mtc.com', 'password' => 'password123', 'role' => 'employee'],
                ['name' => 'Mepoop Alaretnam', 'email' => 'mepoopalaretnam@my.cspc.edu.ph', 'password' => '*3tpEe6JcIV7', 'role' => 'employee'],
            ];

            foreach ($extraUsers as $u) {
                // avoid duplicate emails
                User::updateOrCreate(
                    ['email' => $u['email']],
                    [
                        'name' => $u['name'],
                        'password' => Hash::make($u['password']),
                        'role' => $u['role'],
                        'id_verification_status' => 'approved',
                        'is_active' => true,
                        'is_verified' => true,
                        'email_verified_at' => now(),
                    ]
                );

                $this->command->info('Created user: ' . $u['email'] . ' / ' . $u['password']);
            }
    }
}
