<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 50); // create, update, delete, login, logout, import, export, dll.
            $table->string('modul', 50); // auth, users, students, teachers, attendance, dll.
            $table->string('target_type', 100)->nullable(); // App\Models\Student, dll.
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('data_sebelum')->nullable();
            $table->json('data_sesudah')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['modul', 'aksi']);
            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
