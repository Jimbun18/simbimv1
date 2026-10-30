<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ppdb_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pendaftaran', 50)->unique();
            $table->string('tahun_ajaran', 20)->default('2026/2027');
            $table->string('gelombang', 50)->nullable()->default('Gelombang 1');
            $table->string('jenjang', 20); // 'KB', 'TK A', 'TK B', 'SD'

            // Identitas Calon Peserta Didik
            $table->string('nama_lengkap', 150);
            $table->string('nama_panggilan', 50);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir', 100);
            $table->date('tanggal_lahir');
            $table->string('usia_daftar', 50)->nullable(); // e.g. "5 tahun 7 bulan"
            $table->unsignedSmallInteger('jumlah_saudara')->nullable()->default(0);
            $table->unsignedSmallInteger('anak_ke')->nullable()->default(1);
            $table->text('alamat_lengkap');
            $table->string('tinggal_dengan', 50)->default('Ayah & Ibu'); // Ayah & Ibu, Ibu, Ayah, Kakek & Nenek, Bibi, Uwa, Wali
            $table->string('asal_sekolah', 150)->nullable();
            $table->text('alamat_asal_sekolah')->nullable();
            $table->string('pindah_ke_kelas', 50)->nullable(); // Diisi untuk siswa pindahan
            $table->text('alasan_pindah')->nullable();

            // Data Ayah
            $table->string('nama_ayah', 150);
            $table->string('ttl_ayah', 150)->nullable();
            $table->string('pendidikan_ayah', 50)->nullable();
            $table->string('pekerjaan_ayah', 100)->nullable();
            $table->string('penghasilan_ayah', 100)->nullable();
            $table->text('alamat_ayah')->nullable();
            $table->string('no_hp_ayah', 30);

            // Data Ibu
            $table->string('nama_ibu', 150);
            $table->string('ttl_ibu', 150)->nullable();
            $table->string('pendidikan_ibu', 50)->nullable();
            $table->string('pekerjaan_ibu', 100)->nullable();
            $table->string('penghasilan_ibu', 100)->nullable();
            $table->text('alamat_ibu')->nullable();
            $table->string('no_hp_ibu', 30);

            // Berkas / Lampiran
            $table->string('pas_foto_path', 255)->nullable();
            $table->string('akta_kelahiran_path', 255)->nullable();
            $table->string('kartu_keluarga_path', 255)->nullable();

            // Status Pendaftaran
            $table->enum('status', ['menunggu', 'diterima', 'cadangan', 'ditolak'])->default('menunggu');
            $table->text('catatan_admin')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['jenjang', 'status']);
            $table->index('tahun_ajaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppdb_registrations');
    }
};

