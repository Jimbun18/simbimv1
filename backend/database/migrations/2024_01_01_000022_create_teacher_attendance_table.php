<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            $table->string('status', 15); // hadir, izin, sakit, alpa, dinas
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->decimal('lat_keluar', 10, 7)->nullable();
            $table->decimal('lng_keluar', 10, 7)->nullable();
            $table->integer('jarak_masuk_m')->nullable();
            $table->integer('jarak_keluar_m')->nullable();
            $table->string('foto_masuk')->nullable();
            $table->string('foto_pulang')->nullable();
            $table->boolean('is_terlambat')->default(false);
            $table->integer('menit_keterlambatan')->default(0);
            $table->string('surat_izin_path')->nullable();
            $table->string('status_persetujuan', 20)->default('disetujui'); // diajukan, disetujui, ditolak
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan_koreksi')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique(['teacher_id', 'tanggal'], 'unique_teacher_daily_attendance');
            $table->unique(['staff_id', 'tanggal'], 'unique_staff_daily_attendance');
            $table->index('tanggal');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_attendance');
    }
};
