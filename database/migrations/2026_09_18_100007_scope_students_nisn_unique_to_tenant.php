<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NISN hanya unik di dalam satu sekolah (tenant), bukan lintas tenant.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['nisn']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->unique(['tenant_id', 'nisn'], 'students_tenant_nisn_unique');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_tenant_nisn_unique');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->unique('nisn');
        });
    }
};
