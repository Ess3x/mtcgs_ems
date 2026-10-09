<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_slide_archives', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('original_slide_id');
            $table->text('image');
            $table->string('title', 120);
            $table->text('text');
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->useCurrent();
            $table->timestamps();
            $table->index('original_slide_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_slide_archives');
    }
};
