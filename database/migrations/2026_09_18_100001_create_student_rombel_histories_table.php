<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_rombel_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rombel_id')->nullable()->constrained()->nullOnDelete();
            $table->date('entered_at');
            $table->date('left_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'student_id']);
            $table->index(['tenant_id', 'left_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_rombel_histories');
    }
};
