<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'homepage_slide_1_image' => 'https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=1400&q=80',
            'homepage_slide_1_title' => 'One System for Your Whole Staff',
            'homepage_slide_1_text' => 'Attendance, leave, and payroll unified in a single portal.',
            'homepage_slide_2_image' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1400&q=80',
            'homepage_slide_2_title' => 'Manage Leave with Ease',
            'homepage_slide_2_text' => 'Submit, approve, and track leave requests in real time.',
            'homepage_slide_3_image' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=1400&q=80',
            'homepage_slide_3_title' => 'Payroll Made Simple',
            'homepage_slide_3_text' => 'Accurate payroll and reports built on your attendance data.',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('site_settings')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('site_settings')->whereIn('key', [
            'homepage_slide_1_image', 'homepage_slide_1_title', 'homepage_slide_1_text',
            'homepage_slide_2_image', 'homepage_slide_2_title', 'homepage_slide_2_text',
            'homepage_slide_3_image', 'homepage_slide_3_title', 'homepage_slide_3_text',
        ])->delete();
    }
};
