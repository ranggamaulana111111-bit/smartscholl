<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendance_student_type_date_unique');
            $table->unique(
                ['student_id', 'schedule_id', 'date'],
                'attendance_student_schedule_date_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendance_student_schedule_date_unique');
            $table->unique(
                ['student_id', 'type', 'date'],
                'attendance_student_type_date_unique'
            );
        });
    }
};
