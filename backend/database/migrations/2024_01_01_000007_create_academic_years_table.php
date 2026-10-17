<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 9); // e.g. 2026/2027
            $table->string('semester', 10); // ganjil, genap
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->boolean('is_aktif')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique(['nama', 'semester']);
            $table->index('is_aktif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
