<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('nisn');
            $table->string('rfid_uid', 50)->nullable()->after('qr_token');

            $table->unique(['tenant_id', 'rfid_uid']);
        });

        DB::table('students')
            ->whereNull('qr_token')
            ->orderBy('id')
            ->chunkById(500, function ($students): void {
                foreach ($students as $student) {
                    DB::table('students')
                        ->where('id', $student->id)
                        ->update(['qr_token' => Str::random(32)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'rfid_uid']);
            $table->dropColumn(['qr_token', 'rfid_uid']);
        });
    }
};
