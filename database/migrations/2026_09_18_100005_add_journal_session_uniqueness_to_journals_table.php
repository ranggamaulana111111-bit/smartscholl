<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->unsignedBigInteger('schedule_key')
                ->virtualAs('coalesce(schedule_id, 0)')
                ->after('schedule_id');
            $table->unsignedBigInteger('subject_key')
                ->virtualAs('coalesce(subject_id, 0)')
                ->after('subject_id');

            $table->unique(
                ['user_id', 'date', 'rombel_id', 'schedule_key', 'subject_key'],
                'journal_session_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropUnique('journal_session_unique');
            $table->dropColumn(['schedule_key', 'subject_key']);
        });
    }
};
