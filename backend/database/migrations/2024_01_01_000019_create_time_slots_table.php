<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 30); // Jam 1, Istirahat 1, dll.
            $table->string('jenis', 15); // belajar, istirahat
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->unsignedTinyInteger('urutan');
            $table->string('hari', 10); // senin, selasa, ..., sabtu
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['hari', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
