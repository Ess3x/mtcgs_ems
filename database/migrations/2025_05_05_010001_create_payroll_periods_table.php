<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('payroll_periods')) {
            Schema::create('payroll_periods', function (Blueprint $table) {
                $table->id();
                $table->string('period_code', 50)->unique();
                $table->enum('period_type', ['semi_monthly', 'monthly'])->default('monthly');
                $table->date('start_date');
                $table->date('end_date');
                $table->date('cutoff_date')->nullable();
                $table->date('payment_date');
                $table->enum('status', ['draft', 'processing', 'completed', 'approved'])->default('draft');
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('processed_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->decimal('total_gross', 15, 2)->default(0);
                $table->decimal('total_deductions', 15, 2)->default(0);
                $table->decimal('total_net', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('payroll_periods');
    }
};
