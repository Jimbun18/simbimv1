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
        Schema::table('student_observations', function (Blueprint $table) {
            $table->timestamp('dilihat_bk_at')->nullable()->after('rekomendasi_bk_at');
            $table->foreignId('dilihat_bk_by')->nullable()->after('dilihat_bk_at')->constrained('teachers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_observations', function (Blueprint $table) {
            $table->dropForeign(['dilihat_bk_by']);
            $table->dropColumn(['dilihat_bk_at', 'dilihat_bk_by']);
        });
    }
};
