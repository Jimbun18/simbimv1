<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('semester', 20)->default('Gasal'); // Gasal / Genap
            $table->string('fase', 10)->default('A'); // A (Kelas 1-2), B (Kelas 3-4), C (Kelas 5-6)
            $table->string('tempat_tanggal', 100)->nullable()->default('Purwokerto, 20 Desember 2025');
            $table->date('tanggal_rapor')->nullable();
            
            // Pengesahan
            $table->string('wali_kelas_nama', 150)->nullable();
            $table->string('wali_kelas_nip', 50)->nullable();
            $table->string('kepala_sekolah_nama', 150)->nullable();
            $table->string('kepala_sekolah_nip', 50)->nullable();

            // Kokurikuler / P5 / Deep Learning
            $table->string('kokurikuler_tema', 255)->nullable();
            $table->string('kokurikuler_dimensi', 150)->nullable();
            $table->string('kokurikuler_target', 255)->nullable();
            $table->string('kokurikuler_capaian', 100)->nullable(); // e.g. Sangat Baik, Berkembang Sesuai Harapan, Perlu Bimbingan
            $table->text('kokurikuler_deskripsi')->nullable();

            // Rekap Kehadiran
            $table->unsignedSmallInteger('sakit')->default(0);
            $table->unsignedSmallInteger('izin')->default(0);
            $table->unsignedSmallInteger('alpa')->default(0);

            // Catatan & Respon
            $table->text('catatan_wali_kelas')->nullable();
            $table->text('tanggapan_orang_tua')->nullable();

            // Status & Timestamps
            $table->string('status', 20)->default('draft'); // draft, final
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_id', 'academic_year_id', 'semester'], 'uniq_student_report');
        });

        Schema::create('report_card_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained('report_cards')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('nama_mapel', 150);
            $table->string('kelompok', 50)->default('Umum'); // Umum, Agama, Muatan Lokal, Ekstrakurikuler, Pilihan
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->text('capaian_kompetensi')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('report_card_extracurriculars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained('report_cards')->cascadeOnDelete();
            $table->string('kegiatan', 150);
            $table->string('predikat', 50)->default('Baik'); // Sangat Baik, Baik, Cukup, Kurang
            $table->string('keterangan', 255)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('report_card_learning_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('nama_mapel', 150);
            $table->unsignedTinyInteger('tingkat')->nullable(); // Kelas 1-6
            $table->string('semester', 20)->nullable();
            $table->string('kode_tp', 20); // TP 1, TP 2, dsb
            $table->text('deskripsi_tp');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_learning_objectives');
        Schema::dropIfExists('report_card_extracurriculars');
        Schema::dropIfExists('report_card_grades');
        Schema::dropIfExists('report_cards');
    }
};
