<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_card_grades', function (Blueprint $table) {
            if (!Schema::hasColumn('report_card_grades', 'nilai_uh')) {
                $table->json('nilai_uh')->nullable()->after('sub_aspek')->comment('Daftar nilai ulangan harian siswa (UH 1, UH 2, dst)');
            }
            if (!Schema::hasColumn('report_card_grades', 'rata_uh')) {
                $table->decimal('rata_uh', 5, 2)->nullable()->after('nilai_uh')->comment('Rata-rata Ulangan Harian (RUH)');
            }
            if (!Schema::hasColumn('report_card_grades', 'nilai_sas')) {
                $table->decimal('nilai_sas', 5, 2)->nullable()->after('rata_uh')->comment('Penilaian Sumatif Akhir Semester (PSAS)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_card_grades', function (Blueprint $table) {
            if (Schema::hasColumn('report_card_grades', 'nilai_sas')) {
                $table->dropColumn('nilai_sas');
            }
            if (Schema::hasColumn('report_card_grades', 'rata_uh')) {
                $table->dropColumn('rata_uh');
            }
            if (Schema::hasColumn('report_card_grades', 'nilai_uh')) {
                $table->dropColumn('nilai_uh');
            }
        });
    }
};
