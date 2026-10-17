<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('photo_path');
            $table->string('materi_ajar')->nullable();
            $table->text('catatan')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->unique(['schedule_id', 'teacher_id', 'tanggal'], 'evidence_unique');
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_evidence');
    }
};
