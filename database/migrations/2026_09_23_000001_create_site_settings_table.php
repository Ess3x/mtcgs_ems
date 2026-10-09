<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $defaults = [
            'homepage_hero_title' => 'Mother Theresa Colegio Group of Schools Employee Management System',
            'homepage_hero_text' => 'Track attendance, manage leave requests, review payroll, and view branch calendars all from one system designed for school staff and administrators.',
            'homepage_about_text' => 'MTCGS EMS is built to support school branches with employee attendance, leave management, payroll, and verification workflows. The system is designed for ease of use by branch admins, finance officers, and employees.',
            'homepage_support_text' => 'Contact your branch administrator or support at mepoopalaretnam@mycspc.edu.ph.',
            'homepage_logo_url' => '',
            'homepage_background_url' => '',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('site_settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
