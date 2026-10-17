<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('jenis_data', 30); // students, teachers, classes, guardians, subjects
            $table->string('nama_file', 255);
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('berhasil')->default(0);
            $table->unsignedInteger('gagal')->default(0);
            $table->string('status', 20)->default('pending'); // pending, validating, preview, committed, rolled_back, failed
            $table->json('column_mapping')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('jenis_data');
        });

        Schema::create('import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->unsignedInteger('nomor_baris');
            $table->string('kolom', 50)->nullable();
            $table->text('pesan');
            $table->timestamp('created_at')->useCurrent();

            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_errors');
        Schema::dropIfExists('import_batches');
    }
};
