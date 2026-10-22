<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menit keterlambatan yang dihitung saat pencatatan, bukan saat dibaca.
     *
     * Disimpan sebagai angka, bukan diturunkan ulang dari jadwal, karena
     * toleransi lateinit per sekolah bisa berubah di kemudian hari. Nilai lama
     * harus tetap mencerminkan aturan yang berlaku saat siswa datang.
     * Kolom NULL berarti tidak relevan: scan gerbang tidak punya jam mulai,
     * dan absensi manual belum tentu punya jadwal.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedSmallInteger('late_minutes')->nullable()->after('time');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('late_minutes');
        });
    }
};
