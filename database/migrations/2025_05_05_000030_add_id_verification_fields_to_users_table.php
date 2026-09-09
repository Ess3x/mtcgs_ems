<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'id_verification_status')) {
                $table->enum('id_verification_status', ['pending', 'approved', 'rejected'])->default('pending')->after('role');
            }
            if (!Schema::hasColumn('users', 'id_document_path')) {
                $table->string('id_document_path')->nullable()->after('id_verification_status');
            }
            if (!Schema::hasColumn('users', 'id_document_type')) {
                $table->string('id_document_type')->nullable()->after('id_document_path');
            }
            if (!Schema::hasColumn('users', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('id_document_type');
            }
            if (!Schema::hasColumn('users', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('rejection_reason');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'id_verification_status', 'id_document_path', 'id_document_type',
                'rejection_reason', 'is_verified'
            ]);
        });
    }
};
