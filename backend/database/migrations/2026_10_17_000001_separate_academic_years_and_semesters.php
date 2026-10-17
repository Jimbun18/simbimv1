<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat tabel semesters baru
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->enum('semester', ['gasal', 'genap']);
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->enum('status', ['rencana', 'aktif', 'terkunci'])->default('rencana');
            $table->timestamp('tutup_at')->nullable();
            $table->foreignId('tutup_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopen_at')->nullable();
            $table->timestamp('reopen_until')->nullable();
            $table->foreignId('reopen_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique(['academic_year_id', 'semester'], 'uniq_academic_year_semester');
            $table->index('status');
            $table->index(['tgl_mulai', 'tgl_selesai']);
        });

        // 2. Migrasi data lama academic_years ke struktur baru
        // Ambil data tahun ajaran dan semester lama
        $oldYears = DB::table('academic_years')->orderBy('id')->get();
        $yearMap = [];

        foreach ($oldYears as $row) {
            $nama = $row->nama;
            if (!isset($yearMap[$nama])) {
                // Baris pertama menjadi master Tahun Ajaran
                $yearMap[$nama] = [
                    'primary_id' => $row->id,
                    'tgl_mulai' => $row->tgl_mulai,
                    'tgl_selesai' => $row->tgl_selesai,
                    'status' => $row->is_aktif ? 'aktif' : 'rencana',
                    'semesters' => []
                ];
            } else {
                // Update tanggal selesai tahun ajaran mencakup semester genap
                if ($row->tgl_selesai > $yearMap[$nama]['tgl_selesai']) {
                    $yearMap[$nama]['tgl_selesai'] = $row->tgl_selesai;
                }
            }

            $semType = (strtolower($row->semester) === 'genap') ? 'genap' : 'gasal';
            $semStatus = $row->is_aktif ? 'aktif' : 'rencana';

            $yearMap[$nama]['semesters'][] = [
                'old_id' => $row->id,
                'semester' => $semType,
                'tgl_mulai' => $row->tgl_mulai,
                'tgl_selesai' => $row->tgl_selesai,
                'status' => $semStatus,
            ];
        }

        // Simpan semester baru & catat pemetaan old_academic_year_id -> new semester_id
        $oldYearToSemesterMap = [];
        foreach ($yearMap as $nama => $info) {
            foreach ($info['semesters'] as $semData) {
                $newSemId = DB::table('semesters')->insertGetId([
                    'academic_year_id' => $info['primary_id'],
                    'semester' => $semData['semester'],
                    'tgl_mulai' => $semData['tgl_mulai'],
                    'tgl_selesai' => $semData['tgl_selesai'],
                    'status' => $semData['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $oldYearToSemesterMap[$semData['old_id']] = $newSemId;
            }

            // Update baris primary academic_years
            DB::table('academic_years')
                ->where('id', $info['primary_id'])
                ->update([
                    'tgl_mulai' => $info['tgl_mulai'],
                    'tgl_selesai' => $info['tgl_selesai'],
                ]);
        }

        // Hapus baris sekunder academic_years yang lama (misal baris genap id=2 yang sekarang sudah masuk semesters)
        // Pastikan foreign keys yang merujuk ke id sekunder dialihkan ke primary_id jika ada
        foreach ($yearMap as $nama => $info) {
            $primaryId = $info['primary_id'];
            foreach ($info['semesters'] as $semData) {
                if ($semData['old_id'] !== $primaryId) {
                    $secId = $semData['old_id'];
                    DB::table('classes')->where('academic_year_id', $secId)->update(['academic_year_id' => $primaryId]);
                    DB::table('student_enrollments')->where('academic_year_id', $secId)->update(['academic_year_id' => $primaryId]);
                    DB::table('academic_years')->where('id', $secId)->delete();
                }
            }
        }

        // 3. Modifikasi kolom tabel academic_years (tambah status, hapus semester & is_aktif)
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS academic_years_is_aktif_index');
            DB::statement('DROP INDEX IF EXISTS academic_years_nama_semester_unique');
        }

        Schema::table('academic_years', function (Blueprint $table) {
            $table->enum('status', ['rencana', 'aktif', 'selesai'])->default('aktif')->after('tgl_selesai');
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropIndex(['is_aktif']);
                $table->dropUnique(['nama', 'semester']);
            }
            $table->dropColumn(['semester', 'is_aktif']);
            $table->unique('nama', 'uniq_academic_year_nama');
        });

        // 4. Tambah semester_id pada schedules
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->after('academic_year_id')->constrained('semesters')->cascadeOnDelete();
        });

        // Petakan schedules ke semester_id
        foreach ($oldYearToSemesterMap as $oldYearId => $newSemesterId) {
            DB::table('schedules')->where('academic_year_id', $oldYearId)->update(['semester_id' => $newSemesterId]);
        }
        // Fallback jika ada yang belum terisi
        $defaultSemId = DB::table('semesters')->where('status', 'aktif')->value('id') ?? DB::table('semesters')->value('id');
        if ($defaultSemId) {
            DB::table('schedules')->whereNull('semester_id')->update(['semester_id' => $defaultSemId]);
        }

        // 5. Tambah semester_id pada teacher_assignments
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->after('academic_year_id')->constrained('semesters')->cascadeOnDelete();
        });

        foreach ($oldYearToSemesterMap as $oldYearId => $newSemesterId) {
            DB::table('teacher_assignments')->where('academic_year_id', $oldYearId)->update(['semester_id' => $newSemesterId]);
        }
        if ($defaultSemId) {
            DB::table('teacher_assignments')->whereNull('semester_id')->update(['semester_id' => $defaultSemId]);
        }

        // 6. Tambah semester_id & versioning pada report_cards
        Schema::table('report_cards', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->after('academic_year_id')->constrained('semesters')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1)->after('status');
            $table->timestamp('revised_at')->nullable()->after('version');
            $table->foreignId('revised_by')->nullable()->after('revised_at')->constrained('users')->nullOnDelete();
            $table->text('revision_notes')->nullable()->after('revised_by');
        });

        // 7. Buat tabel report_card_revisions untuk audit arsip rapor final
        Schema::create('report_card_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained('report_cards')->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->json('snapshot_data');
            $table->foreignId('revised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revised_at')->nullable();
            $table->text('revision_notes')->nullable();
            $table->timestamps();

            $table->index(['report_card_id', 'version']);
        });

        // 8. Pasang Database Triggers untuk Proteksi Massal (Bulk / Raw SQL)
        $this->createDatabaseTriggers();
    }

    public function down(): void
    {
        $this->dropDatabaseTriggers();

        Schema::dropIfExists('report_card_revisions');

        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropForeign(['revised_by']);
            $table->dropColumn(['semester_id', 'version', 'revised_at', 'revised_by', 'revision_notes']);
        });

        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
        });

        Schema::table('academic_years', function (Blueprint $table) {
            $table->string('semester', 10)->default('ganjil')->after('nama');
            $table->boolean('is_aktif')->default(true)->after('tgl_selesai');
            $table->dropUnique('uniq_academic_year_nama');
            $table->dropColumn('status');
            $table->unique(['nama', 'semester']);
        });

        // Kembalikan baris genap jika perlu
        $semesters = DB::table('semesters')->get();
        foreach ($semesters as $s) {
            if ($s->semester === 'genap') {
                $ay = DB::table('academic_years')->where('id', $s->academic_year_id)->first();
                if ($ay) {
                    DB::table('academic_years')->insert([
                        'nama' => $ay->nama,
                        'semester' => 'genap',
                        'tgl_mulai' => $s->tgl_mulai,
                        'tgl_selesai' => $s->tgl_selesai,
                        'is_aktif' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        Schema::dropIfExists('semesters');
    }

    private function createDatabaseTriggers(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Trigger proteksi absensi siswa (insert & update)
        DB::unprepared("
            CREATE TRIGGER trg_protect_student_attendance_insert
            BEFORE INSERT ON student_attendance
            FOR EACH ROW
            BEGIN
                DECLARE v_status VARCHAR(20);
                DECLARE v_reopen_until DATETIME;
                DECLARE v_count INT;

                IF @SIMBIM_BYPASS_TRIGGER IS NULL OR @SIMBIM_BYPASS_TRIGGER = 0 THEN
                    -- Cek apakah tanggal berada dalam rentang semester yang terdaftar
                    SELECT COUNT(*), MAX(status), MAX(reopen_until) 
                    INTO v_count, v_status, v_reopen_until
                    FROM semesters
                    WHERE NEW.tanggal BETWEEN tgl_mulai AND tgl_selesai;

                    IF v_count = 0 THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'OUTSIDE_SEMESTER_RANGE: Tanggal berada di luar rentang semester terdaftar (Masa Libur).';
                    ELSEIF v_status = 'terkunci' AND (v_reopen_until IS NULL OR NOW() > v_reopen_until) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'SEMESTER_LOCKED: Absensi siswa tidak dapat disimpan karena semester telah terkunci.';
                    END IF;
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_protect_student_attendance_update
            BEFORE UPDATE ON student_attendance
            FOR EACH ROW
            BEGIN
                DECLARE v_status VARCHAR(20);
                DECLARE v_reopen_until DATETIME;
                DECLARE v_count INT;

                IF @SIMBIM_BYPASS_TRIGGER IS NULL OR @SIMBIM_BYPASS_TRIGGER = 0 THEN
                    SELECT COUNT(*), MAX(status), MAX(reopen_until) 
                    INTO v_count, v_status, v_reopen_until
                    FROM semesters
                    WHERE NEW.tanggal BETWEEN tgl_mulai AND tgl_selesai;

                    IF v_count = 0 THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'OUTSIDE_SEMESTER_RANGE: Tanggal berada di luar rentang semester terdaftar (Masa Libur).';
                    ELSEIF v_status = 'terkunci' AND (v_reopen_until IS NULL OR NOW() > v_reopen_until) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'SEMESTER_LOCKED: Absensi siswa tidak dapat diubah karena semester telah terkunci.';
                    END IF;
                END IF;
            END
        ");

        // Trigger proteksi absensi guru
        DB::unprepared("
            CREATE TRIGGER trg_protect_teacher_attendance_insert
            BEFORE INSERT ON teacher_attendance
            FOR EACH ROW
            BEGIN
                DECLARE v_status VARCHAR(20);
                DECLARE v_reopen_until DATETIME;
                DECLARE v_count INT;

                IF @SIMBIM_BYPASS_TRIGGER IS NULL OR @SIMBIM_BYPASS_TRIGGER = 0 THEN
                    SELECT COUNT(*), MAX(status), MAX(reopen_until) 
                    INTO v_count, v_status, v_reopen_until
                    FROM semesters
                    WHERE NEW.tanggal BETWEEN tgl_mulai AND tgl_selesai;

                    IF v_count = 0 THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'OUTSIDE_SEMESTER_RANGE: Tanggal berada di luar rentang semester terdaftar (Masa Libur).';
                    ELSEIF v_status = 'terkunci' AND (v_reopen_until IS NULL OR NOW() > v_reopen_until) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'SEMESTER_LOCKED: Absensi guru tidak dapat disimpan karena semester telah terkunci.';
                    END IF;
                END IF;
            END
        ");

        // Trigger proteksi rapor final (mencegah overwrite tanpa revisi)
        DB::unprepared("
            CREATE TRIGGER trg_protect_report_cards_update
            BEFORE UPDATE ON report_cards
            FOR EACH ROW
            BEGIN
                DECLARE v_status VARCHAR(20);
                DECLARE v_reopen_until DATETIME;

                IF @SIMBIM_BYPASS_TRIGGER IS NULL OR @SIMBIM_BYPASS_TRIGGER = 0 THEN
                    SELECT status, reopen_until INTO v_status, v_reopen_until
                    FROM semesters WHERE id = OLD.semester_id;

                    IF v_status = 'terkunci' AND (v_reopen_until IS NULL OR NOW() > v_reopen_until) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'SEMESTER_LOCKED: Rapor tidak dapat diubah karena semester telah terkunci.';
                    END IF;
                END IF;
            END
        ");
    }

    private function dropDatabaseTriggers(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared("DROP TRIGGER IF EXISTS trg_protect_student_attendance_insert");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_protect_student_attendance_update");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_protect_teacher_attendance_insert");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_protect_report_cards_update");
    }
};
