<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TimeSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::where('is_aktif', true)->first() ?? AcademicYear::first();
        if (!$year) {
            $this->command->error('Tahun ajaran tidak ditemukan.');
            return;
        }

        // Temukan Guru
        $rasiti = Teacher::where('nama_lengkap', 'like', '%Rasiti%')->first();
        $hilda  = Teacher::where('nama_lengkap', 'like', '%Hilda%')->first();
        $elsa   = Teacher::where('nama_lengkap', 'like', '%Elsa%')->first();
        $rusdyah = Teacher::where('nama_lengkap', 'like', '%Rusdyah%')->first();
        $rosyid = Teacher::where('nama_lengkap', 'like', '%Rosyid%')->first();
        $bayu   = Teacher::where('nama_lengkap', 'like', '%Bayu%')->first();
        $lulu   = Teacher::where('nama_lengkap', 'like', '%Lulu%')->first();

        // Temukan Tema
        $tema1 = Subject::where('is_tematik', true)->where('tema_nomor', 1)->first();
        $tema2 = Subject::where('is_tematik', true)->where('tema_nomor', 2)->first();
        $tema3 = Subject::where('is_tematik', true)->where('tema_nomor', 3)->first();
        $tema4 = Subject::where('is_tematik', true)->where('tema_nomor', 4)->first();
        $tema5 = Subject::where('is_tematik', true)->where('tema_nomor', 5)->first();

        // Temukan Kelas 1-4
        $kelas1 = SchoolClass::where('tingkat', 1)->first();
        $kelas2 = SchoolClass::where('tingkat', 2)->first();
        $kelas3 = SchoolClass::where('tingkat', 3)->first();
        $kelas4 = SchoolClass::where('tingkat', 4)->first();

        if (!$kelas1 || !$kelas2 || !$kelas3 || !$kelas4) {
            $this->command->error('Kelas 1-4 tidak lengkap di database.');
            return;
        }

        // 1. DAFTAR PENUGASAN MENGAJAR RESMI (TEACHER ASSIGNMENTS)
        $assignmentsData = [
            // Rasiti: Tema 5 kelas 1 dan 2
            ['teacher' => $rasiti, 'subject' => $tema5, 'class' => $kelas1, 'keterangan' => 'Reguler'],
            ['teacher' => $rasiti, 'subject' => $tema5, 'class' => $kelas2, 'keterangan' => 'Reguler'],

            // Hilda: Tema 2 kelas 1 - 3
            ['teacher' => $hilda, 'subject' => $tema2, 'class' => $kelas1, 'keterangan' => 'Reguler'],
            ['teacher' => $hilda, 'subject' => $tema2, 'class' => $kelas2, 'keterangan' => 'Reguler'],
            ['teacher' => $hilda, 'subject' => $tema2, 'class' => $kelas3, 'keterangan' => 'Reguler'],

            // Lulu: Tema 5 kelas 3 dan 4, tema 1 kelas 4, tema 2 kelas 4
            ['teacher' => $lulu, 'subject' => $tema5, 'class' => $kelas3, 'keterangan' => 'Reguler'],
            ['teacher' => $lulu, 'subject' => $tema5, 'class' => $kelas4, 'keterangan' => 'Reguler'],
            ['teacher' => $lulu, 'subject' => $tema1, 'class' => $kelas4, 'keterangan' => 'Reguler'],
            ['teacher' => $lulu, 'subject' => $tema2, 'class' => $kelas4, 'keterangan' => 'Reguler'],

            // Rosyid: Tema 4 kelas 1-4 (sebelum istirahat / sesi pagi)
            ['teacher' => $rosyid, 'subject' => $tema4, 'class' => $kelas1, 'keterangan' => 'Sesi Pagi (Sebelum Istirahat)'],
            ['teacher' => $rosyid, 'subject' => $tema4, 'class' => $kelas2, 'keterangan' => 'Sesi Pagi (Sebelum Istirahat)'],
            ['teacher' => $rosyid, 'subject' => $tema4, 'class' => $kelas3, 'keterangan' => 'Sesi Pagi (Sebelum Istirahat)'],
            ['teacher' => $rosyid, 'subject' => $tema4, 'class' => $kelas4, 'keterangan' => 'Sesi Pagi (Sebelum Istirahat)'],

            // Bayu: Tema 4 kelas 1-4 (sesudah istirahat / sesi siang)
            ['teacher' => $bayu, 'subject' => $tema4, 'class' => $kelas1, 'keterangan' => 'Sesi Siang (Sesudah Istirahat)'],
            ['teacher' => $bayu, 'subject' => $tema4, 'class' => $kelas2, 'keterangan' => 'Sesi Siang (Sesudah Istirahat)'],
            ['teacher' => $bayu, 'subject' => $tema4, 'class' => $kelas3, 'keterangan' => 'Sesi Siang (Sesudah Istirahat)'],
            ['teacher' => $bayu, 'subject' => $tema4, 'class' => $kelas4, 'keterangan' => 'Sesi Siang (Sesudah Istirahat)'],

            // Elsa: Tema 3 kelas 1-4
            ['teacher' => $elsa, 'subject' => $tema3, 'class' => $kelas1, 'keterangan' => 'Reguler'],
            ['teacher' => $elsa, 'subject' => $tema3, 'class' => $kelas2, 'keterangan' => 'Reguler'],
            ['teacher' => $elsa, 'subject' => $tema3, 'class' => $kelas3, 'keterangan' => 'Reguler'],
            ['teacher' => $elsa, 'subject' => $tema3, 'class' => $kelas4, 'keterangan' => 'Reguler'],

            // Rusdyah: Tema 1 kelas 1-3
            ['teacher' => $rusdyah, 'subject' => $tema1, 'class' => $kelas1, 'keterangan' => 'Reguler'],
            ['teacher' => $rusdyah, 'subject' => $tema1, 'class' => $kelas2, 'keterangan' => 'Reguler'],
            ['teacher' => $rusdyah, 'subject' => $tema1, 'class' => $kelas3, 'keterangan' => 'Reguler'],
        ];

        foreach ($assignmentsData as $a) {
            if ($a['teacher'] && $a['subject'] && $a['class']) {
                TeacherAssignment::updateOrCreate(
                    [
                        'teacher_id' => $a['teacher']->id,
                        'subject_id' => $a['subject']->id,
                        'class_id' => $a['class']->id,
                        'academic_year_id' => $year->id,
                    ],
                    [
                        'jam_per_minggu' => 4,
                        'keterangan' => $a['keterangan'] ?? 'Reguler',
                    ]
                );
            }
        }

        // 2. SAMBUNGKAN KE JADWAL MINGGUAN (SCHEDULES KELAS 1-4)
        // Ambil Time Slots untuk Senin s/d Jumat
        $days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
        $timeSlotsByDay = [];
        foreach ($days as $day) {
            $timeSlotsByDay[$day] = TimeSlot::where('hari', $day)
                ->where('jenis', 'belajar')
                ->orderBy('urutan')
                ->get();
        }

        // Pola Mata Pelajaran per Hari untuk masing-masing Kelas
        // Memastikan sesuai dengan penugasan:
        // Kelas 1: Senin(Tema 1), Selasa(Tema 2), Rabu(Tema 3), Kamis(Tema 4: Pagi Rosyid, Siang Bayu), Jumat(Tema 5)
        // Kelas 2: Senin(Tema 2), Selasa(Tema 1), Rabu(Tema 5), Kamis(Tema 4: Pagi Rosyid, Siang Bayu), Jumat(Tema 3)
        // Kelas 3: Senin(Tema 3), Selasa(Tema 5), Rabu(Tema 1), Kamis(Tema 4: Pagi Rosyid, Siang Bayu), Jumat(Tema 2)
        // Kelas 4: Senin(Tema 4: Pagi Rosyid, Siang Bayu), Selasa(Tema 3), Rabu(Tema 2), Kamis(Tema 1), Jumat(Tema 5)
        $schedulePattern = [
            $kelas1->id => [
                'senin'  => ['subject' => $tema1, 'teacher' => $rusdyah],
                'selasa' => ['subject' => $tema2, 'teacher' => $hilda],
                'rabu'   => ['subject' => $tema3, 'teacher' => $elsa],
                'kamis'  => ['subject' => $tema4, 'teacher_pagi' => $rosyid, 'teacher_siang' => $bayu],
                'jumat'  => ['subject' => $tema5, 'teacher' => $rasiti],
            ],
            $kelas2->id => [
                'senin'  => ['subject' => $tema2, 'teacher' => $hilda],
                'selasa' => ['subject' => $tema1, 'teacher' => $rusdyah],
                'rabu'   => ['subject' => $tema5, 'teacher' => $rasiti],
                'kamis'  => ['subject' => $tema4, 'teacher_pagi' => $rosyid, 'teacher_siang' => $bayu],
                'jumat'  => ['subject' => $tema3, 'teacher' => $elsa],
            ],
            $kelas3->id => [
                'senin'  => ['subject' => $tema3, 'teacher' => $elsa],
                'selasa' => ['subject' => $tema5, 'teacher' => $lulu],
                'rabu'   => ['subject' => $tema1, 'teacher' => $rusdyah],
                'kamis'  => ['subject' => $tema4, 'teacher_pagi' => $rosyid, 'teacher_siang' => $bayu],
                'jumat'  => ['subject' => $tema2, 'teacher' => $hilda],
            ],
            $kelas4->id => [
                'senin'  => ['subject' => $tema4, 'teacher_pagi' => $rosyid, 'teacher_siang' => $bayu],
                'selasa' => ['subject' => $tema3, 'teacher' => $elsa],
                'rabu'   => ['subject' => $tema2, 'teacher' => $lulu],
                'kamis'  => ['subject' => $tema1, 'teacher' => $lulu],
                'jumat'  => ['subject' => $tema5, 'teacher' => $lulu],
            ],
        ];

        // Hapus jadwal tematik lama di Kelas 1-4 agar jadwal baru bersih dan sinkron
        Schedule::whereIn('class_id', [$kelas1->id, $kelas2->id, $kelas3->id, $kelas4->id])
            ->where('academic_year_id', $year->id)
            ->forceDelete();

        foreach ($schedulePattern as $classId => $daySchedule) {
            $class = SchoolClass::find($classId);

            foreach ($days as $day) {
                if (!isset($daySchedule[$day]) || !isset($timeSlotsByDay[$day])) continue;
                $config = $daySchedule[$day];
                $slots = $timeSlotsByDay[$day];

                foreach ($slots as $slot) {
                    $teacher = null;

                    // Cek jika hari ini Tema 4 (ada pembagian pagi & siang)
                    if (isset($config['teacher_pagi']) && isset($config['teacher_siang'])) {
                        // Sesi 1 s/d 4 (jam mulai < 09:10:00) = Sebelum Istirahat (Pak Rosyid)
                        // Sesi setelah jam 09:10:00 = Sesudah Istirahat (Pak Bayu)
                        if ($slot->jam_mulai < '09:20:00') {
                            $teacher = $config['teacher_pagi'];
                        } else {
                            $teacher = $config['teacher_siang'];
                        }
                    } else {
                        $teacher = $config['teacher'] ?? null;
                    }

                    if ($teacher && isset($config['subject'])) {
                        Schedule::create([
                            'academic_year_id' => $year->id,
                            'class_id' => $classId,
                            'subject_id' => $config['subject']->id,
                            'teacher_id' => $teacher->id,
                            'room_id' => $class->room_id,
                            'hari' => $day,
                            'time_slot_id' => $slot->id,
                            'status' => 'aktif',
                        ]);
                    }
                }
            }
        }
    }
}
