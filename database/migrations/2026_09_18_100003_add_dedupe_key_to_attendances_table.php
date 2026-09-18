<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kunci duplikasi yang portabel lintas MySQL/SQLite. Berbeda dengan unique
     * (student_id, schedule_id, date), kolom ini tidak pernah NULL sehingga juga
     * melindungi absensi gerbang (gate) yang schedule_id-nya kosong.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('dedupe_key', 191)->nullable()->after('note');
        });

        DB::table('attendances')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('attendances')
                        ->where('id', $row->id)
                        ->update([
                            'dedupe_key' => $row->student_id.':'.$row->type.':'.($row->schedule_id ?? 0).':'.$row->date,
                        ]);
                }
            });

        Schema::table('attendances', function (Blueprint $table) {
            $table->string('dedupe_key', 191)->nullable(false)->change();
            $table->unique('dedupe_key', 'attendance_dedupe_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendance_dedupe_key_unique');
            $table->dropColumn('dedupe_key');
        });
    }
};
