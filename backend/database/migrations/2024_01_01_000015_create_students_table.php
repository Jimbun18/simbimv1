<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            // Identitas
            $table->string('nis', 20)->unique();
            $table->char('nisn', 10)->nullable()->unique();
            $table->char('nik', 16)->nullable()->unique();
            $table->char('no_kk', 16)->nullable();
            $table->string('no_akta_lahir', 40)->nullable();
            $table->string('nama_lengkap', 150);
            $table->string('nama_panggilan', 50)->nullable();
            $table->char('jenis_kelamin', 1); // L, P
            $table->string('tempat_lahir', 100);
            $table->date('tgl_lahir');
            $table->foreignId('agama')->constrained('ref_religions')->restrictOnDelete();
            $table->string('kewarganegaraan', 5)->default('WNI'); // WNI, WNA
            $table->unsignedTinyInteger('anak_ke')->nullable();
            $table->unsignedTinyInteger('jumlah_saudara')->nullable();
            $table->string('bahasa_sehari_hari', 50)->nullable();
            // Alamat
            $table->string('alamat_jalan', 255);
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('desa_kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kab_kota', 100);
            $table->string('provinsi', 100)->nullable();
            $table->char('kode_pos', 5)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('tinggal_bersama', 20)->nullable(); // orang_tua, wali, asrama, lainnya
            $table->foreignId('transportasi')->nullable()->constrained('ref_transport')->nullOnDelete();
            $table->decimal('jarak_ke_sekolah_km', 4, 1)->nullable();
            $table->unsignedSmallInteger('waktu_tempuh_menit')->nullable();
            // Kontak
            $table->string('no_hp_siswa', 16)->nullable();
            $table->string('email', 150)->nullable();
            // Fisik & kesehatan (sensitif)
            $table->decimal('tinggi_cm', 5, 1)->nullable();
            $table->decimal('berat_kg', 5, 1)->nullable();
            $table->string('golongan_darah', 12)->nullable(); // A, B, AB, O, tidak_tahu
            $table->text('riwayat_penyakit')->nullable();
            $table->text('alergi')->nullable();
            $table->text('kebutuhan_khusus')->nullable();
            // Asal & masuk
            $table->string('asal_sekolah_tk', 150)->nullable();
            $table->string('jenis_pendaftaran', 15)->nullable(); // siswa_baru, pindahan
            $table->date('tgl_masuk');
            $table->year('tahun_masuk');
            $table->unsignedTinyInteger('kelas_saat_masuk')->nullable();
            // Akademik
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('status', 20)->default('aktif'); // aktif, pindah, lulus, keluar, nonaktif
            // Lainnya
            $table->string('foto_path')->nullable();
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('import_batch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index('status');
            $table->index('class_id');
            $table->index('tahun_masuk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
