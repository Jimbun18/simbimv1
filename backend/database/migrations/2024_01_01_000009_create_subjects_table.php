<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 15)->unique();
            $table->string('nama', 100);
            $table->string('kelompok', 30); // umum, agama, muatan_lokal, ekstrakurikuler
            $table->string('tingkat_berlaku', 20)->nullable(); // e.g. "1-6"
            $table->unsignedTinyInteger('jam_per_minggu')->nullable();
            $table->unsignedTinyInteger('kkm')->nullable(); // 0-100
            $table->string('status', 20)->default('aktif'); // aktif, nonaktif
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
