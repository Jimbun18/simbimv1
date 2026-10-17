<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Identitas
            $table->string('nip', 18)->nullable()->unique();
            $table->char('nuptk', 16)->nullable()->unique();
            $table->char('nik', 16)->unique();
            $table->string('nama_lengkap', 150);
            $table->string('gelar_depan', 30)->nullable();
            $table->string('gelar_belakang', 30)->nullable();
            $table->char('jenis_kelamin', 1); // L, P
            $table->string('tempat_lahir', 100);
            $table->date('tgl_lahir');
            $table->foreignId('agama')->nullable()->constrained('ref_religions')->nullOnDelete();
            $table->string('status_pernikahan', 20)->nullable(); // belum_menikah, menikah, cerai
            // Alamat & kontak
            $table->string('alamat_jalan', 255);
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('desa_kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kab_kota', 100);
            $table->string('provinsi', 100)->nullable();
            $table->char('kode_pos', 5)->nullable();
            $table->string('no_hp', 16);
            $table->string('email', 150)->nullable();
            $table->string('kontak_darurat_nama', 150)->nullable();
            $table->string('kontak_darurat_hp', 16)->nullable();
            $table->string('kontak_darurat_hubungan', 50)->nullable();
            // Kepegawaian
            $table->string('jenis_ptk', 30); // guru_kelas, guru_mapel, guru_agama, guru_olahraga, kepala_sekolah
            $table->string('status_kepegawaian', 20); // pns, pppk, gty, gtt, honorer
            $table->date('tgl_mulai_tugas');
            $table->date('tgl_akhir_tugas')->nullable();
            $table->string('jabatan', 100)->nullable();
            $table->string('tugas_tambahan', 150)->nullable();
            $table->unsignedSmallInteger('jam_mengajar_per_minggu')->nullable();
            $table->string('status', 20)->default('aktif'); // aktif, cuti, nonaktif, pensiun
            // Pendidikan
            $table->foreignId('pendidikan_terakhir')->nullable()->constrained('ref_education')->nullOnDelete();
            $table->string('jurusan_prodi', 100)->nullable();
            $table->string('institusi', 150)->nullable();
            $table->year('tahun_lulus')->nullable();
            // Sertifikasi
            $table->string('status_sertifikasi', 10)->nullable(); // sudah, belum
            $table->string('no_sertifikat_pendidik', 50)->nullable();
            $table->year('tahun_sertifikasi')->nullable();
            // Administratif (sensitif)
            $table->string('npwp', 20)->nullable();
            $table->string('nama_bank', 50)->nullable();
            $table->string('no_rekening', 30)->nullable();
            $table->string('atas_nama', 150)->nullable();
            // Lainnya
            $table->string('foto_path')->nullable();
            $table->string('tanda_tangan_path')->nullable();
            $table->unsignedBigInteger('import_batch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index('status');
            $table->index('jenis_ptk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
