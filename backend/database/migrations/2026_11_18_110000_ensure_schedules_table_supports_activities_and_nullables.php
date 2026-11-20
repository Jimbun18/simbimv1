<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bersihkan record soft-deleted lama yang mungkin mengunci slot unique index
        try {
            DB::table('schedules')->whereNotNull('deleted_at')->delete();
        } catch (\Throwable $e) {
            // abaikan jika tabel belum ada
        }

        // 2. Pastikan kolom kegiatan ada di tabel schedules
        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'judul_kegiatan')) {
                $table->string('judul_kegiatan', 100)->nullable()->after('room_id');
            }
            if (!Schema::hasColumn('schedules', 'tipe_kegiatan')) {
                $table->string('tipe_kegiatan', 30)->default('kbm')->after('judul_kegiatan');
            }
            if (!Schema::hasColumn('schedules', 'catatan')) {
                $table->string('catatan', 255)->nullable()->after('tipe_kegiatan');
            }
            if (!Schema::hasColumn('schedules', 'semester_id')) {
                $table->foreignId('semester_id')->nullable()->after('academic_year_id')->constrained('semesters')->cascadeOnDelete();
            }
        });

        // 3. Pastikan subject_id & teacher_id nullable pada level MySQL
        // Menggunakan native ALTER agar tidak tergantung doctrine/dbal di cPanel
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement("ALTER TABLE schedules MODIFY COLUMN subject_id BIGINT UNSIGNED NULL;");
            } catch (\Throwable $e) {
                // fallback schema change
            }
            try {
                DB::statement("ALTER TABLE schedules MODIFY COLUMN teacher_id BIGINT UNSIGNED NULL;");
            } catch (\Throwable $e) {
                // fallback schema change
            }
            try {
                DB::statement("ALTER TABLE schedules MODIFY COLUMN room_id BIGINT UNSIGNED NULL;");
            } catch (\Throwable $e) {
                // fallback
            }
        }
    }

    public function down(): void
    {
        // No-op
    }
};
