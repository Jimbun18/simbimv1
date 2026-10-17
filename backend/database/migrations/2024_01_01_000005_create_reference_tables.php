<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['ref_religions', 'ref_education', 'ref_occupations', 'ref_income_ranges', 'ref_transport'];

        foreach ($tables as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('nama', 100)->unique();
                $table->unsignedSmallInteger('urutan')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_transport');
        Schema::dropIfExists('ref_income_ranges');
        Schema::dropIfExists('ref_occupations');
        Schema::dropIfExists('ref_education');
        Schema::dropIfExists('ref_religions');
    }
};
