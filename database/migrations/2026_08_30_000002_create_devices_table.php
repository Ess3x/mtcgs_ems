<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('mac_address')->unique()->comment('MAC address of the device');
            $table->string('device_name')->nullable()->comment('Human-readable name (e.g., Scanner1, Scanner2)');
            $table->string('device_type')->nullable()->comment('Type: biometric_scanner, kiosk, computer');
            $table->unsignedBigInteger('branch_id')->nullable()->comment('Which branch this device is assigned to');
            $table->string('location')->nullable()->comment('Physical location (e.g., Ground Floor, 2nd Floor)');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->dateTime('last_used_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->index('mac_address');
            $table->index('status');
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
