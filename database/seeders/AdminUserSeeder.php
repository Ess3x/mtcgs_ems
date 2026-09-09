<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AdminProfile;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Check if admin already exists
        $admin = User::updateOrCreate(
            ['email' => 'admin@mtcgs.edu.ph'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'admin_type' => 'super_admin',
                'id_verification_status' => 'approved',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create admin profile for admin
        $branch = Branch::first();

        $adminProfile = AdminProfile::where('user_id', $admin->id)->orderBy('id')->first();

        if (!$adminProfile) {
            $adminProfile = AdminProfile::create([
                'user_id' => $admin->id,
                'branch_id' => $branch->id ?? 1,
                'employee_number' => 'ADMIN001',
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'position' => 'System Administrator',
                'date_hired' => now(),
                'basic_salary' => 0,
            ]);
        } else {
            $adminProfile->update([
                'branch_id' => $branch->id ?? 1,
                'employee_number' => $adminProfile->employee_number ?: 'ADMIN001',
                'first_name' => $adminProfile->first_name ?: 'System',
                'last_name' => $adminProfile->last_name ?: 'Administrator',
                'position' => $adminProfile->position ?: 'System Administrator',
                'date_hired' => $adminProfile->date_hired ?: now(),
                'basic_salary' => $adminProfile->basic_salary ?? 0,
            ]);
        }
        
        // Update user with polymorphic relationship
        $admin->update([
            'profile_type' => AdminProfile::class,
            'profile_id' => $adminProfile->id,
        ]);
        
        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@mtcgs.edu.ph');
        $this->command->info('Password: admin123');
    }
}
