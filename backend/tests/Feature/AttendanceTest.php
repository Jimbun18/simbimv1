<?php

use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\Religion;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SchoolProfile;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\TeachingEvidence;
use App\Models\TimeSlot;
use App\Models\User;
use Database\Seeders\ReferenceTableSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SchoolProfileSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('public');

    $this->seed(RoleSeeder::class);
    $this->seed(ReferenceTableSeeder::class);
    $this->seed(SchoolProfileSeeder::class);
    $this->seed(SettingSeeder::class);

    $roleAdmin = Role::where('nama', Role::STAF_ADMIN)->first();
    $roleKepala = Role::where('nama', Role::KEPALA_SEKOLAH)->first();
    $roleGuru = Role::where('nama', Role::GURU)->first();
    $roleWali = Role::where('nama', Role::WALI)->first();

    // Admin
    $this->admin = User::create([
        'nama' => 'Staf Tata Usaha',
        'username' => 'stafadmin',
        'password' => Hash::make('password123'),
        'role_id' => $roleAdmin->id,
        'status' => 'aktif',
    ]);

    // Kepala Sekolah
    $this->kepala = User::create([
        'nama' => 'Kepala Sekolah',
        'username' => 'kepsek',
        'password' => Hash::make('password123'),
        'role_id' => $roleKepala->id,
        'status' => 'aktif',
    ]);

    // Guru User & Teacher
    $this->userGuru = User::create([
        'nama' => 'Ustadzah Fatimah',
        'username' => 'fatimah',
        'password' => Hash::make('password123'),
        'role_id' => $roleGuru->id,
        'status' => 'aktif',
    ]);

    $this->teacher = Teacher::create([
        'user_id' => $this->userGuru->id,
        'nik' => '3302015505920001',
        'nama_lengkap' => 'Ustadzah Fatimah',
        'jenis_kelamin' => 'P',
        'tempat_lahir' => 'Banyumas',
        'tgl_lahir' => '1992-05-15',
        'alamat_jalan' => 'Jl. Masjid No. 5',
        'kab_kota' => 'Banyumas',
        'no_hp' => '+6281234567010',
        'jenis_ptk' => 'guru_kelas',
        'status_kepegawaian' => 'gty',
        'tgl_mulai_tugas' => '2021-07-01',
        'status' => 'aktif',
    ]);

    // Wali User & Guardian
    $this->userWali = User::create([
        'nama' => 'Bapak Budi Santoso',
        'username' => 'budisantoso',
        'password' => Hash::make('password123'),
        'role_id' => $roleWali->id,
        'status' => 'aktif',
    ]);

    $this->guardian = Guardian::create([
        'user_id' => $this->userWali->id,
        'nik' => '3302011005800001',
        'nama_lengkap' => 'Bapak Budi Santoso',
        'hubungan' => 'ayah',
        'jenis_kelamin' => 'L',
        'alamat_jalan' => 'Jl. Kenanga No. 10',
        'kab_kota' => 'Banyumas',
        'no_hp' => '+6281234567020',
        'status' => 'aktif',
    ]);

    // Tahun Ajaran, Semester, Kelas, Mapel, Siswa
    $this->year = AcademicYear::create([
        'nama' => '2026/2027',
        'tgl_mulai' => '2026-07-01',
        'tgl_selesai' => '2027-06-30',
        'status' => 'aktif',
    ]);

    \App\Models\Semester::create([
        'academic_year_id' => $this->year->id,
        'semester' => 'gasal',
        'tgl_mulai' => '2026-07-01',
        'tgl_selesai' => '2026-12-31',
        'status' => 'aktif',
    ]);

    $this->room = Room::create(['kode' => 'R101', 'nama' => 'Ruang 1A', 'kapasitas' => 30, 'jenis' => 'kelas']);
    $this->class = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'kode' => '1A',
        'tingkat' => 1,
        'nama' => '1A Abu Bakar',
        'wali_kelas_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'kapasitas' => 28,
        'status' => 'aktif',
    ]);

    $this->subject = Subject::create([
        'kode' => 'PAI-1',
        'nama' => 'Pendidikan Agama Islam',
        'kelompok' => 'A',
        'kkm_default' => 75,
        'is_aktif' => true,
    ]);

    $this->student = Student::create([
        'nis' => '26001',
        'nisn' => '0012345678',
        'nik' => '3302012001150001',
        'nama_lengkap' => 'Aisyah Putri',
        'jenis_kelamin' => 'P',
        'tempat_lahir' => 'Purwokerto',
        'tgl_lahir' => '2019-01-20',
        'agama' => Religion::first()->id,
        'alamat_jalan' => 'Jl. Kenanga No. 10',
        'kab_kota' => 'Banyumas',
        'tgl_masuk' => '2026-07-15',
        'tahun_masuk' => 2026,
        'class_id' => $this->class->id,
        'status' => 'aktif',
    ]);

    $this->student->guardians()->attach($this->guardian->id, [
        'hubungan' => 'ayah',
        'is_kontak_utama' => true,
        'boleh_lihat_keuangan' => true,
        'boleh_jemput' => true,
    ]);

    $dayMap = [0 => 'minggu', 1 => 'senin', 2 => 'selasa', 3 => 'rabu', 4 => 'kamis', 5 => 'jumat', 6 => 'sabtu'];
    $todayDay = $dayMap[now()->dayOfWeek] ?? 'senin';

    $this->timeSlot = TimeSlot::create([
        'nama' => 'Jam Ke-1',
        'hari' => $todayDay,
        'urutan' => 1,
        'jam_mulai' => '07:30:00',
        'jam_selesai' => '08:45:00',
        'jenis' => 'belajar',
    ]);

    $this->schedule = Schedule::create([
        'academic_year_id' => $this->year->id,
        'class_id' => $this->class->id,
        'subject_id' => $this->subject->id,
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'time_slot_id' => $this->timeSlot->id,
        'hari' => $todayDay,
        'is_aktif' => true,
    ]);
});

test('teacher can check-in within geofence and is rejected outside geofence', function () {
    $profile = SchoolProfile::first();
    $schoolLat = (float) $profile->latitude;
    $schoolLng = (float) $profile->longitude;

    Sanctum::actingAs($this->userGuru);

    // 1. Outside geofence (approx 10km away) -> rejected
    $outsideLat = $schoolLat + 0.1;
    $outsideLng = $schoolLng + 0.1;

    $resOutside = $this->postJson('/api/v1/attendance/teachers/check-in', [
        'lat' => $outsideLat,
        'lng' => $outsideLng,
        'foto' => UploadedFile::fake()->image('selfie.jpg', 640, 480),
    ]);

    $resOutside->assertStatus(422);
    expect($resOutside->json('message'))->toContain('di luar radius');

    // 2. Inside geofence (at exact school coordinates, on-time at 06:45) -> success
    $this->travelTo(now()->setTime(6, 45, 0));
    $resInside = $this->postJson('/api/v1/attendance/teachers/check-in', [
        'lat' => $schoolLat,
        'lng' => $schoolLng,
        'foto' => UploadedFile::fake()->image('selfie.jpg', 640, 480),
    ]);

    $resInside->assertOk();
    expect($resInside->json('data.status'))->toBe('hadir');
    expect($resInside->json('data.jam_masuk'))->not->toBeNull();
    expect($resInside->json('data.foto_masuk'))->not->toBeNull();

    // 3. Repeated check-in on the same day -> rejected
    $resRepeat = $this->postJson('/api/v1/attendance/teachers/check-in', [
        'lat' => $schoolLat,
        'lng' => $schoolLng,
        'foto' => UploadedFile::fake()->image('selfie.jpg', 640, 480),
    ]);

    $resRepeat->assertStatus(422);
    expect($resRepeat->json('message'))->toContain('sudah melakukan absen masuk');
});

test('teacher check-in after tolerance is marked as terlambat with correct minutes', function () {
    $profile = SchoolProfile::first();
    $schoolLat = (float) $profile->latitude;
    $schoolLng = (float) $profile->longitude;

    Sanctum::actingAs($this->userGuru);

    // School starts at 07:00, tolerance 15 min. Check in at 07:35 (35 mins late)
    $this->travelTo(now()->setTime(7, 35, 0));

    $res = $this->postJson('/api/v1/attendance/teachers/check-in', [
        'lat' => $schoolLat,
        'lng' => $schoolLng,
        'foto' => UploadedFile::fake()->image('selfie_late.jpg', 640, 480),
    ]);

    $res->assertOk();
    expect($res->json('data.status'))->toBe('terlambat');
    expect($res->json('data.is_terlambat'))->toBeTrue();
    expect($res->json('data.menit_keterlambatan'))->toBe(35);
    expect($res->json('data.keterangan'))->toContain('Terlambat 35 menit');
});

test('teacher can check-out after checking in', function () {
    $profile = SchoolProfile::first();
    $schoolLat = (float) $profile->latitude;
    $schoolLng = (float) $profile->longitude;

    Sanctum::actingAs($this->userGuru);

    // Check-in first
    $this->postJson('/api/v1/attendance/teachers/check-in', [
        'lat' => $schoolLat,
        'lng' => $schoolLng,
        'foto' => UploadedFile::fake()->image('masuk.jpg', 640, 480),
    ])->assertOk();

    // Check-out
    $resOut = $this->postJson('/api/v1/attendance/teachers/check-out', [
        'lat' => $schoolLat,
        'lng' => $schoolLng,
        'foto' => UploadedFile::fake()->image('pulang.jpg', 640, 480),
    ]);

    $resOut->assertOk();
    expect($resOut->json('data.jam_keluar'))->not->toBeNull();
    expect($resOut->json('data.foto_pulang'))->not->toBeNull();

    // Check today endpoint
    $todayRes = $this->getJson('/api/v1/attendance/teachers/today');
    $todayRes->assertOk();
    expect($todayRes->json('data.has_checked_in'))->toBeTrue();
    expect($todayRes->json('data.has_checked_out'))->toBeTrue();
});

test('teacher can submit leave request and headmaster can approve it', function () {
    Sanctum::actingAs($this->userGuru);

    $tomorrow = now()->addDay()->toDateString();
    $dayAfter = now()->addDays(2)->toDateString();

    $leaveRes = $this->postJson('/api/v1/attendance/teachers/leave', [
        'status' => 'sakit',
        'tanggal_mulai' => $tomorrow,
        'tanggal_selesai' => $dayAfter,
        'keterangan' => 'Demam dan flu, istirahat dokter.',
        'lampiran' => UploadedFile::fake()->create('surat_dokter.pdf', 300, 'application/pdf'),
    ]);

    $leaveRes->assertCreated();
    $attendanceId = $leaveRes->json('data.0.id');

    // Headmaster approves leave
    Sanctum::actingAs($this->kepala);
    $approveRes = $this->postJson("/api/v1/attendance/teachers/{$attendanceId}/approve-leave", [
        'status_persetujuan' => 'disetujui',
        'alasan' => 'Semoga lekas sembuh Ustadzah.',
    ]);

    $approveRes->assertOk();
    expect($approveRes->json('data.status_persetujuan'))->toBe('disetujui');
});

test('student attendance is recorded once daily by homeroom teacher and subject teacher uploads teaching evidence', function () {
    // 1. Another teacher who is not the homeroom teacher cannot submit student attendance -> 403
    $otherTeacherUser = User::create([
        'nama' => 'Ustadz non-wali',
        'username' => 'nonwali',
        'password' => Hash::make('password123'),
        'role_id' => $this->userGuru->role_id,
        'status' => 'aktif',
    ]);
    Teacher::create([
        'user_id' => $otherTeacherUser->id,
        'nik' => '3302015505929999',
        'nama_lengkap' => 'Ustadz non-wali',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Banyumas',
        'tgl_lahir' => '1990-01-01',
        'alamat_jalan' => 'Jl. Kebon No. 1',
        'kab_kota' => 'Banyumas',
        'no_hp' => '+6281234567088',
        'jenis_ptk' => 'guru_mapel',
        'status_kepegawaian' => 'gty',
        'tgl_mulai_tugas' => '2021-07-01',
        'status' => 'aktif',
    ]);

    Sanctum::actingAs($otherTeacherUser);
    $today = now()->toDateString();

    $forbiddenRes = $this->postJson('/api/v1/attendance/students', [
        'class_id' => $this->class->id,
        'tanggal' => $today,
        'attendances' => [
            [
                'student_id' => $this->student->id,
                'status' => 'hadir',
            ],
        ],
    ]);
    $forbiddenRes->assertStatus(403);
    expect($forbiddenRes->json('message'))->toContain('Wali Kelas');

    // 2. Homeroom teacher ($this->userGuru is wali_kelas of $this->class) can submit daily student attendance
    Sanctum::actingAs($this->userGuru);

    $successRes = $this->postJson('/api/v1/attendance/students', [
        'class_id' => $this->class->id,
        'tanggal' => $today,
        'attendances' => [
            [
                'student_id' => $this->student->id,
                'status' => 'hadir',
                'keterangan' => 'Hadir tepat waktu dan aktif',
            ],
        ],
    ]);

    $successRes->assertOk();
    expect($successRes->json('data.total_siswa'))->toBe(1);

    // Verify stored student attendance record
    $stored = StudentAttendance::where('student_id', $this->student->id)->whereDate('tanggal', $today)->first();
    expect($stored)->not->toBeNull();
    expect($stored->status)->toBe('hadir');

    // 3. Subject teacher uploads teaching evidence (KBM journal & photo) separately via /api/v1/attendance/evidence
    $evidenceRes = $this->postJson('/api/v1/attendance/evidence', [
        'schedule_id' => $this->schedule->id,
        'tanggal' => $today,
        'materi_ajar' => 'Surah Al-Fatihah dan adab berdoa',
        'catatan' => 'Siswa sangat aktif dan antusias',
        'foto' => UploadedFile::fake()->image('kbm.jpg', 800, 600),
    ]);

    $evidenceRes->assertCreated();
    expect($evidenceRes->json('data.photo_path'))->not->toBeNull();
});

test('parent can view child attendance history and summary', function () {
    $today = now()->toDateString();

    // Create attendance record
    StudentAttendance::create([
        'student_id' => $this->student->id,
        'schedule_id' => $this->schedule->id,
        'class_id' => $this->class->id,
        'tanggal' => $today,
        'status' => 'hadir',
        'dicatat_oleh' => $this->admin->id,
    ]);

    Sanctum::actingAs($this->userWali);

    $res = $this->getJson("/api/v1/parent/children/{$this->student->id}/attendance");
    $res->assertOk();
    expect($res->json('data.summary.total_pertemuan'))->toBe(1);
    expect($res->json('data.summary.hadir'))->toBe(1);
    expect($res->json('data.summary.persentase_kehadiran'))->toBe(100);
});

test('admin can view rekap, correct teacher attendance, and update system settings', function () {
    $today = now()->toDateString();

    // Create an attendance record
    $attendance = TeacherAttendance::create([
        'teacher_id' => $this->teacher->id,
        'tanggal' => $today,
        'jam_masuk' => '07:10:00',
        'status' => 'hadir',
        'created_by' => $this->admin->id,
    ]);

    Sanctum::actingAs($this->admin);

    // 1. Rekap Guru
    $rekapRes = $this->getJson('/api/v1/attendance/teachers?tanggal=' . $today);
    $rekapRes->assertOk();
    expect($rekapRes->json('summary.hadir'))->toBe(1);

    // 2. Koreksi Absensi
    $correctRes = $this->putJson("/api/v1/attendance/teachers/{$attendance->id}/correct", [
        'status' => 'izin',
        'alasan_koreksi' => 'Salah input status hadir oleh guru yang sedang izin resmi.',
    ]);

    $correctRes->assertOk();
    expect($correctRes->json('data.status'))->toBe('izin');
    expect($correctRes->json('data.alasan_koreksi'))->toBe('Salah input status hadir oleh guru yang sedang izin resmi.');

    // 3. Update Settings
    $settingRes = $this->putJson('/api/v1/settings', [
        'settings' => [
            ['key' => 'geofence_enabled', 'value' => false],
            ['key' => 'require_teaching_evidence', 'value' => false],
        ],
    ]);

    $settingRes->assertOk();
    expect(Setting::get('geofence_enabled'))->toBeFalse();
    expect(Setting::get('require_teaching_evidence'))->toBeFalse();
});

test('admin and homeroom teacher can view class attendance recap and export to excel', function () {
    Sanctum::actingAs($this->admin);

    $class = SchoolClass::first();

    $res = $this->getJson("/api/v1/attendance/students/recap-class?class_id={$class->id}");
    $res->assertOk();
    expect($res->json('data.class.id'))->toBe($class->id);
    expect($res->json('data.students'))->toBeArray();
    expect($res->json('data.summary'))->toBeArray();

    $exportRes = $this->get("/api/v1/attendance/students/recap-class/export?class_id={$class->id}");
    $exportRes->assertOk();
    $exportRes->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('teacher can only view classes and recap for classes they teach or handle', function () {
    Sanctum::actingAs($this->userGuru);

    // Get classes -> only classes where this teacher is assigned or homeroom
    $classesRes = $this->getJson('/api/v1/classes');
    $classesRes->assertOk();
    $classIds = collect($classesRes->json('data'))->pluck('id')->toArray();
    expect($classIds)->toContain($this->class->id);

    // Create another class with another teacher
    $otherTeacherUser = User::create([
        'nama' => 'Ustadz Ahmad',
        'username' => 'ahmad',
        'password' => Hash::make('password123'),
        'role_id' => $this->userGuru->role_id,
        'status' => 'aktif',
    ]);
    $otherTeacher = Teacher::create([
        'user_id' => $otherTeacherUser->id,
        'nik' => '3302015505920099',
        'nama_lengkap' => 'Ustadz Ahmad',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Banyumas',
        'tgl_lahir' => '1990-01-01',
        'alamat_jalan' => 'Jl. Kebon No. 1',
        'kab_kota' => 'Banyumas',
        'no_hp' => '+6281234567099',
        'jenis_ptk' => 'guru_kelas',
        'status_kepegawaian' => 'gty',
        'tgl_mulai_tugas' => '2021-07-01',
        'status' => 'aktif',
    ]);
    $otherClass = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'wali_kelas_id' => $otherTeacher->id,
        'nama' => 'Kelas Lain 5-B',
        'kode' => 'K5B',
        'tingkat' => 5,
        'status' => 'aktif',
    ]);

    // Teacher fetching classes again should NOT see otherClass
    $classesRes2 = $this->getJson('/api/v1/classes');
    $classesRes2->assertOk();
    $classIds2 = collect($classesRes2->json('data'))->pluck('id')->toArray();
    expect($classIds2)->not->toContain($otherClass->id);

    // Teacher attempting to view recap of otherClass should be rejected with 403
    $unauthRecapRes = $this->getJson("/api/v1/attendance/students/recap-class?class_id={$otherClass->id}");
    $unauthRecapRes->assertStatus(403);
});


