<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('philhealth_contributions')) {
            Schema::create('philhealth_contributions', function (Blueprint $table) {
                $table->id();
                $table->decimal('min_salary', 10, 2);
                $table->decimal('max_salary', 10, 2);
                $table->decimal('employee_share', 10, 2);
                $table->decimal('employer_share', 10, 2);
                $table->year('effectivity_year');
                $table->timestamps();
            });

            DB::table('philhealth_contributions')->insert([
                ['min_salary' => 0, 'max_salary' => 10000, 'employee_share' => 150, 'employer_share' => 150, 'effectivity_year' => 2024],
                ['min_salary' => 10000.01, 'max_salary' => 60000, 'employee_share' => 0, 'employer_share' => 0, 'effectivity_year' => 2024],
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('philhealth_contributions');
    }
};
