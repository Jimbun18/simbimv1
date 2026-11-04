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
        Schema::create('grade_book_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('semester', 20)->default('Gasal'); // Gasal / Genap
            $table->string('subject_name', 150);
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->string('status', 30)->default('draft'); // draft, submitted, revised
            $table->timestamp('submitted_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['class_id', 'academic_year_id', 'semester', 'subject_name'],
                'uniq_grade_book_submission'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_book_submissions');
    }
};
