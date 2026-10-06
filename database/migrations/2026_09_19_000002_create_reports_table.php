<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 20);
            $table->string('title', 120);
            $table->string('location', 120);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('submitted')->index();
            $table->text('admin_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('report_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('thumb_path')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->unsignedTinyInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('report_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_status_logs');
        Schema::dropIfExists('report_photos');
        Schema::dropIfExists('reports');
    }
};
