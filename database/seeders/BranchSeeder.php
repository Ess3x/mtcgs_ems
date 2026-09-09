<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    public function run()
    {
        // Use firstOrCreate para hindi mag-duplicate
        Branch::firstOrCreate(
            ['branch_code' => 'MTC-IRIGA'],
            [
                'branch_name' => 'Mother Theresa Colegio de Iriga',
                'address' => 'Iriga City, Camarines Sur',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        Branch::firstOrCreate(
            ['branch_code' => 'MTC-BUHI'],
            [
                'branch_name' => 'Mother Theresa Colegio de Buhi',
                'address' => 'Buhi, Camarines Sur',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        $this->command->info('Branches seeded successfully!');
    }
}
