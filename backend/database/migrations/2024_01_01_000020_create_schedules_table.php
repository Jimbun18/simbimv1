<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->string('hari', 10); // senin, selasa, ..., sabtu
            $table->foreignId('time_slot_id')->constrained('time_slots')->restrictOnDelete();
            $table->string('status', 20)->default('aktif'); // aktif, nonaktif
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Kelas tidak boleh bentrok pada hari + jam yang sama
            $table->unique(
                ['class_id', 'hari', 'time_slot_id', 'academic_year_id'],
                'schedule_class_day_slot_unique'
            );
            // Guru tidak boleh bentrok: ditegakkan di service layer (karena MySQL unique index tidak support partial/conditional)
            $table->index(['teacher_id', 'hari', 'time_slot_id'], 'schedule_teacher_day_slot_index');
            // Ruang tidak boleh bentrok
            $table->index(['room_id', 'hari', 'time_slot_id'], 'schedule_room_day_slot_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
