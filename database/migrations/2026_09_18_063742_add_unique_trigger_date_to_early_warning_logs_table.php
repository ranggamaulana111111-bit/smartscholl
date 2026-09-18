<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $keep = DB::table('early_warning_logs')
            ->selectRaw('MIN(id) as id')
            ->groupBy('student_id', 'type', 'trigger_date')
            ->pluck('id');

        if ($keep->isNotEmpty()) {
            DB::table('early_warning_logs')
                ->whereNotIn('id', $keep)
                ->delete();
        }

        Schema::table('early_warning_logs', function (Blueprint $table) {
            $table->unique(['student_id', 'type', 'trigger_date'], 'uq_early_warning_logs_student_type_date');
        });
    }

    public function down(): void
    {
        Schema::table('early_warning_logs', function (Blueprint $table) {
            $table->dropUnique('uq_early_warning_logs_student_type_date');
        });
    }
};
