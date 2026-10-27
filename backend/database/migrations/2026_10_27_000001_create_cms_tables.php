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
        // 1. Galeri Kegiatan Siswa (Foto + Jenis Kegiatan)
        Schema::create('cms_galleries', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');
            $table->string('activity_type', 100)->default('Kegiatan Siswa');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Prestasi Siswa (Foto + Judul)
        Schema::create('cms_achievements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_path');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Artikel / Warta Sekolah (Foto + Judul + Keterangan)
        Schema::create('cms_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description');
            $table->string('image_path');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_articles');
        Schema::dropIfExists('cms_achievements');
        Schema::dropIfExists('cms_galleries');
    }
};
