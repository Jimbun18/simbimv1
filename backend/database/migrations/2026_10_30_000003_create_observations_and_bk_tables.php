<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Catatan Observasi Perilaku Siswa di Kelas (Bisa diakses semua Guru)
        Schema::create('student_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete(); // Guru observer / pencatat
            $table->date('tanggal');
            $table->string('waktu', 100)->nullable(); // e.g. "Jam Lentera", "09.40 - 11.15"
            $table->string('tempat', 150)->nullable(); // e.g. "Ruang Tema 5", "Perpustakaan"
            $table->text('situasi_sebelum')->nullable(); // Antecedent: duduk anteng, melamun, dll.
            $table->text('perilaku_diamati'); // Behavior: deskripsi kejadian faktual
            $table->text('respons_lingkungan')->nullable(); // Consequence: respon teman/guru
            
            // Kolaborasi dengan BK
            $table->text('rekomendasi_bk')->nullable(); // Tanggapan / analisis Guru BK
            $table->foreignId('rekomendasi_bk_by')->nullable()->constrained('teachers')->nullOnDelete();
            $table->timestamp('rekomendasi_bk_at')->nullable();
            $table->text('saran_untuk_guru')->nullable(); // Yang bisa dilakukan Wali Kelas dan/atau Guru Tema
            $table->text('catatan_wali_kelas')->nullable(); // Feedback / catatan pemantauan wali kelas
            $table->string('status_pemantauan', 50)->default('Baru'); // Baru, Dalam Pemantauan, Menunjukkan Perkembangan, Sesuai Harapan, Selesai
            $table->boolean('is_escalated_to_bk')->default(false); // Apakah sudah dijadikan kasus di Analisis & Monitoring BK
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Analisis & Monitoring Kasus BK (Khusus Guru BK)
        Schema::create('bk_counseling_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('wali_kelas_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('guru_bk_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->date('tanggal_masuk');
            $table->string('sumber_awal', 100)->default('Wali Kelas'); // Wali Kelas, Guru Tema, Guru BK, Orang Tua, Siswa, dll.
            $table->string('fokus_pemantauan', 100)->default('Pribadi'); // Pribadi, Sosial, Belajar, Perkembangan, Emosi/Regulasi Diri, Perilaku, Kedisiplinan, Kehadiran, Potensi/Bakat, Lainnya
            $table->string('status_saat_ini', 50)->default('Dalam Pemantauan'); // Dalam Pemantauan, Menunjukkan Perkembangan, Sesuai Harapan, Memerlukan Perhatian
            $table->date('review_terakhir')->nullable();
            $table->date('review_berikutnya')->nullable();
            $table->text('catatan_umum')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Log Jurnal / Riwayat Pemantauan & Tahapan Intervensi BK Siswa
        Schema::create('bk_case_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bk_counseling_case_id')->constrained('bk_counseling_cases')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('tahap', 100)->default('Pemantauan'); // Informasi Masuk, Identifikasi, Observasi BK, Telaah BK, Kebutuhan & Tujuan, Keputusan & Tindak Lanjut, Hasil & Evaluasi, Keputusan Berikutnya, Ringkasan Kondisi, Pemantauan
            $table->string('sumber_data', 100)->nullable(); // Wali kelas, Guru Tema, Guru BK, Orang Tua, Siswa
            $table->text('observasi_temuan'); // Catatan temuan / fakta
            $table->text('telaah_bk')->nullable(); // Analisis profesional / asesmen psikologis BK
            $table->text('kebutuhan_tujuan')->nullable(); // Kebutuhan dan target yang ditetapkan
            $table->text('tindak_lanjut')->nullable(); // Intervensi, coaching, konseling, langkah yang diambil
            $table->text('hasil_evaluasi')->nullable(); // Hasil observasi pasca tindak lanjut
            $table->string('perubahan_pola', 50)->default('Belum Cukup Data'); // Belum Cukup Data, Masih Berulang, Mulai Berkurang, Mulai Berubah, Tidak Lagi Terlihat
            $table->string('status', 50)->default('Dalam Pemantauan'); // Dalam Pemantauan, Menunjukkan Perkembangan, Sesuai Harapan, Memerlukan Perhatian
            $table->text('catatan_konteks')->nullable(); // Informasi relevan lainnya

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bk_case_logs');
        Schema::dropIfExists('bk_counseling_cases');
        Schema::dropIfExists('student_observations');
    }
};

