<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->change();
            $table->foreignId('teacher_id')->nullable()->change();
            $table->string('judul_kegiatan', 100)->nullable()->after('room_id');
            $table->string('tipe_kegiatan', 30)->default('kbm')->after('judul_kegiatan'); // kbm, pembiasaan, istirahat, ekskul
            $table->string('catatan', 255)->nullable()->after('tipe_kegiatan');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['judul_kegiatan', 'tipe_kegiatan', 'catatan']);
        });
    }
};
