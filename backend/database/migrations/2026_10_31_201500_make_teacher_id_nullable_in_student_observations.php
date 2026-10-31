<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_observations', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('student_observations', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable(false)->change();
        });
    }
};

