<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->renameColumn('subject', 'subject_text');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_text');
            $table->foreign('subject_id')->references('id')->on('subjects')->nullOnDelete();
        });

        DB::table('teachers')->update([
            'subject_id' => DB::raw('(select s.id from subjects s where s.tenant_id = teachers.tenant_id and lower(s.name) = lower(teachers.subject_text) order by s.id limit 1)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropColumn('subject_id');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->renameColumn('subject_text', 'subject');
        });
    }
};
