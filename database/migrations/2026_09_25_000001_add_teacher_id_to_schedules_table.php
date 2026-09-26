<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('teachers')
            ->whereNull('subject_id')
            ->whereNotNull('tenant_id')
            ->whereNotNull('subject_text')
            ->where('subject_text', '!=', '')
            ->orderBy('id')
            ->eachById(function (object $teacher) use ($now): void {
                $subject = DB::table('subjects')
                    ->where('tenant_id', $teacher->tenant_id)
                    ->whereRaw('lower(name) = lower(?)', [$teacher->subject_text])
                    ->first();

                $subjectId = $subject?->id ?? DB::table('subjects')->insertGetId([
                    'tenant_id' => $teacher->tenant_id,
                    'name' => $teacher->subject_text,
                    'code' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('teachers')
                    ->where('id', $teacher->id)
                    ->update(['subject_id' => $subjectId]);
            });

        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->after('user_id');
        });

        DB::table('schedules')
            ->whereNull('teacher_id')
            ->whereNotNull('user_id')
            ->update([
                'teacher_id' => DB::raw('(select teachers.id from teachers where teachers.user_id = schedules.user_id and teachers.tenant_id = schedules.tenant_id limit 1)'),
            ]);

        $teacherSubjectIds = DB::table('teachers')
            ->whereNotNull('subject_id')
            ->pluck('subject_id', 'id');

        DB::table('schedules')
            ->whereNotNull('teacher_id')
            ->orderBy('id')
            ->eachById(function (object $schedule) use ($teacherSubjectIds): void {
                $subjectId = $teacherSubjectIds->get($schedule->teacher_id);

                if ($subjectId !== null && (int) $schedule->subject_id !== (int) $subjectId) {
                    DB::table('schedules')
                        ->where('id', $schedule->id)
                        ->update(['subject_id' => $subjectId]);
                }
            });

        Schema::table('schedules', function (Blueprint $table) {
            $table->foreign('teacher_id')->references('id')->on('teachers')->restrictOnDelete();
            $table->index(['tenant_id', 'teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropIndex(['tenant_id', 'teacher_id']);
            $table->dropColumn('teacher_id');
        });
    }
};
