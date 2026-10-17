<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('jenis', 30); // masuk, naik_kelas, pindah_keluar, lulus, keluar, aktif_kembali
            $table->date('tanggal');
            $table->string('sekolah_asal_tujuan', 150)->nullable();
            $table->text('alasan')->nullable();
            $table->string('no_surat', 50)->nullable();
            $table->string('dokumen_path')->nullable();
            $table->foreignId('dicatat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['student_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_status_history');
    }
};
