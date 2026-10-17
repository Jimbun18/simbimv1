<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->char('nik', 16)->unique();
            $table->string('nama_lengkap', 150);
            $table->char('jenis_kelamin', 1); // L, P
            $table->string('tempat_lahir', 100);
            $table->date('tgl_lahir');
            $table->foreignId('agama')->nullable()->constrained('ref_religions')->nullOnDelete();
            $table->string('alamat_jalan', 255);
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('desa_kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kab_kota', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->char('kode_pos', 5)->nullable();
            $table->string('no_hp', 16);
            $table->string('email', 150)->nullable();
            $table->string('jabatan', 100); // operator, bendahara, tata_usaha, petugas_sarpras, dll.
            $table->string('status_kepegawaian', 20)->nullable(); // pns, pppk, honorer, kontrak
            $table->date('tgl_mulai_tugas');
            $table->string('status', 20)->default('aktif'); // aktif, nonaktif
            $table->foreignId('pendidikan_terakhir')->nullable()->constrained('ref_education')->nullOnDelete();
            $table->string('foto_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
