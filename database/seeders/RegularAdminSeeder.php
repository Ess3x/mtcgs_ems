<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AdminProfile;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;

class RegularAdminSeeder extends Seeder
{
    public function run()
    {
        // Get the branches
        $irigaBranch = Branch::where('branch_code', 'MTC-IRIGA')->first();
        $buhiBranch = Branch::where('branch_code', 'MTC-BUHI')->first();
        
        if (!$irigaBranch || !$buhiBranch) {
            $this->command->error('Branches not found. Please run BranchSeeder first.');
            return;
        }

        // Iriga Admin
        $irigaAdmin = User::firstOrCreate(
            ['email' => 'admin.iriga@mtcgs.edu.ph'],
            [
                'name' => 'Iriga Administrator',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'admin_type' => 'branch_admin',
                'id_verification_status' => 'approved',
                'is_active' => true,
                'email_verified_at' => now(),
                'branch_id' => $irigaBranch->id,
            ]
        );

        $irigaAdmin->update([
            'name' => 'Iriga Administrator',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'id_verification_status' => 'approved',
            'is_active' => true,
            'email_verified_at' => now(),
            'branch_id' => $irigaBranch->id,
        ]);

        $irigaProfile = AdminProfile::firstOrCreate(
            ['user_id' => $irigaAdmin->id],
            [
                'branch_id' => $irigaBranch->id,
                'employee_number' => 'ADM-IRIGA',
                'first_name' => 'Iriga',
                'last_name' => 'Administrator',
                'position' => 'Branch Administrator',
                'admin_level' => 'branch_admin',
                'can_verify_ids' => false,
                'date_hired' => now(),
            ]
        );
        
        $irigaProfile->update(['branch_id' => $irigaBranch->id]);

        $irigaAdmin->update([
            'profile_type' => AdminProfile::class,
            'profile_id' => $irigaProfile->id,
        ]);

        // Buhi Admin
        $buhiAdmin = User::firstOrCreate(
            ['email' => 'admin.buhi@mtcgs.edu.ph'],
            [
                'name' => 'Buhi Administrator',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'admin_type' => 'branch_admin',
                'id_verification_status' => 'approved',
                'is_active' => true,
                'email_verified_at' => now(),
                'branch_id' => $buhiBranch->id,
            ]
        );

        $buhiAdmin->update([
            'name' => 'Buhi Administrator',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'admin_type' => 'branch_admin',
            'id_verification_status' => 'approved',
            'is_active' => true,
            'email_verified_at' => now(),
            'branch_id' => $buhiBranch->id,
        ]);

        $buhiProfile = AdminProfile::firstOrCreate(
            ['user_id' => $buhiAdmin->id],
            [
                'branch_id' => $buhiBranch->id,
                'employee_number' => 'ADM-BUHI',
                'first_name' => 'Buhi',
                'last_name' => 'Administrator',
                'position' => 'Branch Administrator',
                'admin_level' => 'branch_admin',
                'can_verify_ids' => false,
                'date_hired' => now(),
            ]
        );
        
        $buhiProfile->update(['branch_id' => $buhiBranch->id]);

        $buhiAdmin->update([
            'profile_type' => AdminProfile::class,
            'profile_id' => $buhiProfile->id,
        ]);

        $this->command->info('Regular admins created successfully!');
        $this->command->info('Iriga Admin: admin.iriga@mtcgs.edu.ph / password123');
        $this->command->info('Buhi Admin: admin.buhi@mtcgs.edu.ph / password123');
    }
}