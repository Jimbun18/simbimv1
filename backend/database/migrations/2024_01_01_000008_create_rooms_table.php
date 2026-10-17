<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 15)->unique();
            $table->string('nama', 100);
            $table->string('jenis', 30); // ruang_kelas, perpustakaan, lab, aula, mushola, lainnya
            $table->string('gedung_lantai', 50)->nullable();
            $table->unsignedSmallInteger('kapasitas')->nullable();
            $table->string('kondisi', 20)->nullable(); // baik, rusak_ringan, rusak_berat
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
