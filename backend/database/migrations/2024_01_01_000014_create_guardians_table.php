<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hubungan', 10); // ayah, ibu, wali
            $table->string('nama_lengkap', 150);
            $table->char('nik', 16)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('status_hidup', 15)->nullable(); // hidup, meninggal
            $table->foreignId('agama')->nullable()->constrained('ref_religions')->nullOnDelete();
            $table->foreignId('pendidikan_terakhir')->nullable()->constrained('ref_education')->nullOnDelete();
            $table->foreignId('pekerjaan')->nullable()->constrained('ref_occupations')->nullOnDelete();
            $table->foreignId('penghasilan_bulanan')->nullable()->constrained('ref_income_ranges')->nullOnDelete();
            $table->string('no_hp', 16);
            $table->string('no_whatsapp', 16)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('alamat_jalan', 255)->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('desa_kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kab_kota', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->char('kode_pos', 5)->nullable();
            $table->string('foto_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index('hubungan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
