<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->date('tgl_masuk_kelas');
            $table->date('tgl_keluar_kelas')->nullable();
            $table->string('status_kelas', 20)->default('aktif'); // aktif, naik, tinggal_kelas, pindah
            $table->unsignedSmallInteger('nomor_absen')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique(['student_id', 'academic_year_id'], 'enrollment_student_year_unique');
            $table->index('status_kelas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
