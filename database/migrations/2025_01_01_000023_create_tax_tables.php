<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tax_tables', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_taxable', 12, 2);
            $table->decimal('max_taxable', 12, 2);
            $table->decimal('fixed_tax', 12, 2);
            $table->decimal('excess_percentage', 5, 2);
            $table->year('effectivity_year');
            $table->timestamps();
        });

        // BIR TRAIN Law Withholding Tax (2024)
        $taxBrackets = [
            [0, 20832, 0, 0],
            [20833, 33332, 0, 15],
            [33333, 66666, 1875, 20],
            [66667, 166666, 8542, 25],
            [166667, 666666, 33542, 30],
            [666667, 999999999, 183542, 35],
        ];

        foreach ($taxBrackets as $bracket) {
            DB::table('tax_tables')->insert([
                'min_taxable' => $bracket[0],
                'max_taxable' => $bracket[1],
                'fixed_tax' => $bracket[2],
                'excess_percentage' => $bracket[3],
                'effectivity_year' => 2024,
                'created_at' => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('tax_tables');
    }
};
