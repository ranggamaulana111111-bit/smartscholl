<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak audit untuk setiap kode yang dipindai, berhasil maupun ditolak.
     *
     * Tabel ini menjawab pertanyaan yang tidak bisa dijawab tabel attendances:
     * kode apa yang ditolak, kartu mana yang dipakai di luar kelasnya, dan
     * apakah satu operator memindai kartu orang lain dalam waktu yang tidak
     * wajar. Kode disimpan sebagai hash, bukan nilai mentah, sehingga
     * kebocoran tabel ini tidak langsung berubah menjadi daftar token QR
     * yang masih sah.
     */
    public function up(): void
    {
        Schema::create('attendance_scan_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['gate_in', 'gate_out', 'lesson'])->default('gate_in');
            $table->enum('outcome', [
                'recorded',
                'duplicate',
                'unknown_code',
                'empty_payload',
                'student_not_in_class',
                'outside_window',
            ]);
            $table->string('code_hash', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'created_at'], 'scan_events_tenant_created_index');
            $table->index(['tenant_id', 'outcome', 'created_at'], 'scan_events_tenant_outcome_index');
            $table->index('code_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_scan_events');
    }
};
