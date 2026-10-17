<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->string('kode', 10); // e.g. 3A
            $table->string('nama', 50); // e.g. Kelas 3 A
            $table->unsignedTinyInteger('tingkat'); // 1-6
            $table->string('paralel', 5)->nullable(); // A, B, C
            $table->foreignId('wali_kelas_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->unsignedSmallInteger('kapasitas')->nullable();
            $table->string('status', 20)->default('aktif'); // aktif, nonaktif
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique(['kode', 'academic_year_id']);
            $table->index('tingkat');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
