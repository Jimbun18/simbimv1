<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_card_grades', function (Blueprint $table) {
            if (!Schema::hasColumn('report_card_grades', 'nilai_psts')) {
                $table->decimal('nilai_psts', 5, 2)
                    ->nullable()
                    ->after('nilai_uh')
                    ->comment('Penilaian Sumatif Tengah Semester (PSTS), setara UH dalam perhitungan RUH');
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_card_grades', function (Blueprint $table) {
            if (Schema::hasColumn('report_card_grades', 'nilai_psts')) {
                $table->dropColumn('nilai_psts');
            }
        });
    }
};
