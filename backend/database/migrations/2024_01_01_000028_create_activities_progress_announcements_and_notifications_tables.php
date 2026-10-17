<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Kegiatan Harian (Daily Activities)
        Schema::create('daily_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('judul', 150);
            $table->text('deskripsi');
            $table->string('kategori', 50)->default('kbm'); // kbm, karakter, ekskul, sholat, lainnya
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['class_id', 'tanggal']);
            $table->index('tanggal');
        });

        // 2. Progres Belajar & Karakter (Learning Progress)
        Schema::create('learning_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->date('tanggal');
            $table->string('kategori', 50)->default('akademik'); // akademik, tahfidz, sholat, karakter, ekskul
            $table->string('capaian', 255);
            $table->text('catatan')->nullable();
            $table->string('nilai', 20)->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['student_id', 'tanggal']);
            $table->index(['student_id', 'kategori']);
            $table->index('tanggal');
        });

        // 3. Pengumuman (Announcements)
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 200);
            $table->text('isi');
            $table->string('kategori', 50)->default('umum'); // umum, libur, kegiatan, akademik, penting
            $table->string('target_role', 50)->default('semua'); // semua, guru, wali, staf_admin, kepala_sekolah
            $table->string('lampiran_path')->nullable();
            $table->date('tgl_terbit');
            $table->date('tgl_berakhir')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_aktif', 'target_role']);
            $table->index('tgl_terbit');
        });

        // 4. Notifikasi In-App (Notifications)
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul', 150);
            $table->text('isi');
            $table->string('tipe', 50)->default('info'); // pengumuman, absensi, progres, kegiatan, info
            $table->string('target_url')->nullable();
            $table->json('data')->nullable();
            $table->boolean('dibaca')->default(false);
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'dibaca']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('learning_progress');
        Schema::dropIfExists('daily_activities');
    }
};
