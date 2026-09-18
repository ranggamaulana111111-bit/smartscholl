<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_rombel_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('open_student_id')
                ->nullable()
                ->virtualAs('case when left_at is null then student_id else null end')
                ->after('left_at');
            $table->unique('open_student_id', 'student_rombel_histories_open_student_unique');
        });
    }

    public function down(): void
    {
        Schema::table('student_rombel_histories', function (Blueprint $table) {
            $table->dropUnique('student_rombel_histories_open_student_unique');
            $table->dropColumn('open_student_id');
        });
    }
};
