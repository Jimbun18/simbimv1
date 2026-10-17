<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_guardian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained('guardians')->cascadeOnDelete();
            $table->string('hubungan', 10); // ayah, ibu, wali
            $table->boolean('is_kontak_utama')->default(false);
            $table->boolean('boleh_lihat_keuangan')->default(true);
            $table->boolean('boleh_jemput')->default(true);
            $table->timestamps();

            $table->unique(['student_id', 'guardian_id']);
            $table->index('is_kontak_utama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_guardian');
    }
};
