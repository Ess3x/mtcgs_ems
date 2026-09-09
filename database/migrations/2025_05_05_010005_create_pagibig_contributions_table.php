<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pagibig_contributions')) {
            Schema::create('pagibig_contributions', function (Blueprint $table) {
                $table->id();
                $table->decimal('min_salary', 10, 2);
                $table->decimal('max_salary', 10, 2);
                $table->decimal('employee_share', 10, 2);
                $table->decimal('employer_share', 10, 2);
                $table->year('effectivity_year');
                $table->timestamps();
            });

            DB::table('pagibig_contributions')->insert([
                ['min_salary' => 0, 'max_salary' => 1500, 'employee_share' => 0, 'employer_share' => 0, 'effectivity_year' => 2024],
                ['min_salary' => 1500.01, 'max_salary' => 999999, 'employee_share' => 100, 'employer_share' => 100, 'effectivity_year' => 2024],
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('pagibig_contributions');
    }
};
