<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('clearance_items');
        Schema::dropIfExists('resignation_requests');
        Schema::dropIfExists('employee_documents');
    }

    public function down(): void
    {
        // The removed feature is not restored by rollback.
    }
};