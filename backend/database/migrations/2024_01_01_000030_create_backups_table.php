<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->enum('tipe', ['otomatis', 'manual'])->default('manual');
            $table->unsignedBigInteger('ukuran')->default(0)->comment('Ukuran file dalam bytes');
            $table->string('lokasi');
            $table->enum('status', ['sukses', 'gagal', 'dipulihkan'])->default('sukses');
            $table->string('checksum', 64)->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
