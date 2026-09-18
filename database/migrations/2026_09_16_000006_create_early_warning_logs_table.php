<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('early_warning_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['absence_streak', 'attendance_rate', 'low_score'])->default('low_score');
            $table->string('description');
            $table->date('trigger_date');
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'trigger_date']);
            $table->index(['student_id', 'is_resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('early_warning_logs');
    }
};
