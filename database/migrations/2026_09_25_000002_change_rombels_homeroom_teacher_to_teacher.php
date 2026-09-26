<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rombels', function (Blueprint $table): void {
            $table->dropForeign(['homeroom_teacher_id']);
        });

        DB::table('rombels')
            ->select(['id', 'tenant_id', 'homeroom_teacher_id'])
            ->whereNotNull('homeroom_teacher_id')
            ->orderBy('id')
            ->chunkById(100, function ($rombels): void {
                foreach ($rombels as $rombel) {
                    $teacherId = DB::table('teachers')
                        ->where('tenant_id', $rombel->tenant_id)
                        ->where('user_id', $rombel->homeroom_teacher_id)
                        ->value('id');

                    DB::table('rombels')
                        ->where('id', $rombel->id)
                        ->update(['homeroom_teacher_id' => $teacherId]);
                }
            });

        Schema::table('rombels', function (Blueprint $table): void {
            $table->index('homeroom_teacher_id');
            $table->foreign('homeroom_teacher_id')->references('id')->on('teachers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rombels', function (Blueprint $table): void {
            $table->dropForeign(['homeroom_teacher_id']);
        });

        DB::table('rombels')
            ->select(['id', 'tenant_id', 'homeroom_teacher_id'])
            ->whereNotNull('homeroom_teacher_id')
            ->orderBy('id')
            ->chunkById(100, function ($rombels): void {
                foreach ($rombels as $rombel) {
                    $userId = DB::table('teachers')
                        ->where('tenant_id', $rombel->tenant_id)
                        ->where('id', $rombel->homeroom_teacher_id)
                        ->value('user_id');

                    DB::table('rombels')
                        ->where('id', $rombel->id)
                        ->update(['homeroom_teacher_id' => $userId]);
                }
            });

        Schema::table('rombels', function (Blueprint $table): void {
            $table->dropIndex(['homeroom_teacher_id']);
            $table->foreign('homeroom_teacher_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
