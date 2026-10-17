<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_profile', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sekolah', 150);
            $table->char('npsn', 8)->nullable();
            $table->string('nss', 20)->nullable();
            $table->string('jenjang', 10)->default('SD'); // SD, MI
            $table->string('status_sekolah', 10)->nullable(); // Negeri, Swasta
            $table->string('yayasan', 150)->nullable();
            $table->string('alamat_jalan', 255);
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('desa_kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kab_kota', 100);
            $table->string('provinsi', 100);
            $table->char('kode_pos', 5)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 255)->nullable();
            $table->unsignedBigInteger('kepala_sekolah_id')->nullable();
            $table->string('akreditasi', 5)->nullable();
            $table->year('tahun_akreditasi')->nullable();
            $table->year('tahun_berdiri')->nullable();
            $table->string('logo_path')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('radius_geofence_m')->nullable()->default(100);
            $table->time('jam_masuk_default')->nullable()->default('07:00');
            $table->time('jam_pulang_default')->nullable()->default('14:00');
            $table->unsignedTinyInteger('toleransi_terlambat_menit')->nullable()->default(15);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_profile');
    }
};
