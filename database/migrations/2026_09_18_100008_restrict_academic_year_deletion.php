<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['rombels', 'schedules', 'journals', 'assessments'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['academic_year_id']);
            });

            Schema::table($table, function (Blueprint $t) {
                $t->foreign('academic_year_id')
                    ->references('id')
                    ->on('academic_years')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['rombels', 'schedules', 'journals', 'assessments'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['academic_year_id']);
            });

            Schema::table($table, function (Blueprint $t) {
                $t->foreign('academic_year_id')
                    ->references('id')
                    ->on('academic_years')
                    ->cascadeOnDelete();
            });
        }
    }
};
