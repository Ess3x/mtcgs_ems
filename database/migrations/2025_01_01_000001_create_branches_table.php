<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('branch_code', 20)->unique();
                $table->string('branch_name', 100);
                $table->text('address')->nullable();
                $table->timestamps();
            });
        }

        // Insert default branches kung wala pa
        if (DB::table('branches')->count() == 0) {
            DB::table('branches')->insert([
                ['branch_code' => 'MTC-IRIGA', 'branch_name' => 'Mother Theresa Colegio de Iriga', 'created_at' => now()],
                ['branch_code' => 'MTC-BUHI', 'branch_name' => 'Mother Theresa Colegio de Buhi', 'created_at' => now()],
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('branches');
    }
};
