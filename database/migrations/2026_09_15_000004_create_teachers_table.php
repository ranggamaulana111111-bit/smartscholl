<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('nuptk', 16)->nullable()->unique();
            $table->string('name');
            $table->string('nip', 18)->nullable();
            $table->string('subject', 100)->nullable();
            $table->enum('employment_status', ['gty', 'ptt', 'asn'])->default('gty');
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'employment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
