<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Pada record presensi yang memiliki teacher_id = 11 DAN staff_id = 2, lepas staff_id menjadi null
        DB::table('teacher_attendance')
            ->where('teacher_id', 11)
            ->where('staff_id', 2)
            ->update(['staff_id' => null]);

        // 2. Pada record presensi historis duplikat yang hanya memiliki staff_id = 2,
        // hapus karena sudah ada record yang sama untuk teacher_id = 11 pada tanggal tersebut
        DB::table('teacher_attendance')
            ->whereNull('teacher_id')
            ->where('staff_id', 2)
            ->delete();

        // 3. Hapus data staf duplikat Bayu (id = 2) dari tabel staff
        // karena Bayu sudah terdaftar resmi sebagai PTK di tabel teachers (id = 11)
        DB::table('staff')->where('id', 2)->delete();
    }

    public function down(): void
    {
        // No-op rollback
    }
};
