<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('report_cards', 'jenis_rapor')) {
                $table->string('jenis_rapor', 50)->default('kurikulum_merdeka')->after('semester_id');
            }
            if (!Schema::hasColumn('report_cards', 'periode')) {
                $table->string('periode', 50)->nullable()->default('Semester Gasal')->after('semester');
            }
            if (!Schema::hasColumn('report_cards', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('report_cards', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('report_cards', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('submitted_by');
            }
            if (!Schema::hasColumn('report_cards', 'validated_by')) {
                $table->foreignId('validated_by')->nullable()->after('validated_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('report_cards', 'validation_notes')) {
                $table->text('validation_notes')->nullable()->after('validated_by');
            }

            // Create explicit foreign index for student_id so uniq_student_report can be dropped
            $table->index('student_id', 'idx_rc_student_fk');
        });

        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropUnique('uniq_student_report');
            $table->unique(['student_id', 'academic_year_id', 'semester', 'jenis_rapor', 'periode'], 'uniq_student_report_type_period');
        });

        Schema::table('report_card_grades', function (Blueprint $table) {
            if (!Schema::hasColumn('report_card_grades', 'tema')) {
                $table->string('tema', 150)->nullable()->after('kelompok');
            }
            if (!Schema::hasColumn('report_card_grades', 'aspek')) {
                $table->string('aspek', 150)->nullable()->after('tema');
            }
            if (!Schema::hasColumn('report_card_grades', 'sub_aspek')) {
                $table->string('sub_aspek', 150)->nullable()->after('aspek');
            }
            if (!Schema::hasColumn('report_card_grades', 'predikat')) {
                $table->string('predikat', 20)->nullable()->after('nilai_akhir');
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_card_grades', function (Blueprint $table) {
            $table->dropColumn(['tema', 'aspek', 'sub_aspek', 'predikat']);
        });

        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropUnique('uniq_student_report_type_period');
            $table->unique(['student_id', 'academic_year_id', 'semester'], 'uniq_student_report');
            $table->dropIndex('idx_rc_student_fk');
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['validated_by']);
            $table->dropColumn([
                'jenis_rapor',
                'periode',
                'submitted_at',
                'submitted_by',
                'validated_at',
                'validated_by',
                'validation_notes',
            ]);
        });
    }
};
