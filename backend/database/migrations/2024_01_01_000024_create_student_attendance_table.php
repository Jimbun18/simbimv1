<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->date('tanggal');
            $table->string('status', 15); // hadir, izin, sakit, alpa
            $table->text('keterangan')->nullable();
            $table->foreignId('dicatat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique(['student_id', 'schedule_id', 'tanggal'], 'student_attendance_unique');
            $table->index('tanggal');
            $table->index('status');
            $table->index('class_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance');
    }
};
