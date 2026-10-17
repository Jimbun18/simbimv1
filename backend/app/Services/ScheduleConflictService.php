<?php

namespace App\Services;

use App\Models\Schedule;
use Illuminate\Validation\ValidationException;

class ScheduleConflictService
{
    /**
     * Check if a proposed schedule conflicts with existing schedules.
     * Throws a descriptive ValidationException if a conflict exists.
     */
    public function validateNoConflict(array $data, ?int $ignoreScheduleId = null): void
    {
        $academicYearId = $data['academic_year_id'];
        $classId = $data['class_id'];
        $teacherId = $data['teacher_id'];
        $roomId = $data['room_id'] ?? null;
        $hari = $data['hari'];
        $timeSlotId = $data['time_slot_id'];

        // 1. Teacher conflict check (only when teacher is assigned)
        if (!empty($teacherId) && ($data['tipe_kegiatan'] ?? 'kbm') === 'kbm') {
            $teacherConflict = Schedule::where('academic_year_id', $academicYearId)
                ->where('teacher_id', $teacherId)
                ->where('hari', $hari)
                ->where('time_slot_id', $timeSlotId)
                ->where('status', 'aktif')
                ->when($ignoreScheduleId, fn ($q) => $q->where('id', '!=', $ignoreScheduleId))
                ->with(['schoolClass', 'subject'])
                ->first();

            if ($teacherConflict) {
                $className = $teacherConflict->schoolClass?->nama ?? "Kelas #{$teacherConflict->class_id}";
                $subjectName = $teacherConflict->subject?->nama ?? ($teacherConflict->judul_kegiatan ?? "Mapel #{$teacherConflict->subject_id}");
                throw ValidationException::withMessages([
                    'teacher_id' => ["Guru yang dipilih sudah memiliki jadwal mengajar ({$subjectName} di {$className}) pada hari dan jam tersebut."],
                ]);
            }
        }

        // 2. Class conflict check
        $classConflict = Schedule::where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('hari', $hari)
            ->where('time_slot_id', $timeSlotId)
            ->where('status', 'aktif')
            ->when($ignoreScheduleId, fn ($q) => $q->where('id', '!=', $ignoreScheduleId))
            ->with(['subject', 'teacher'])
            ->first();

        if ($classConflict) {
            $subjectName = $classConflict->subject?->nama ?? "Mapel #{$classConflict->subject_id}";
            $teacherName = $classConflict->teacher?->nama_lengkap ?? "Guru #{$classConflict->teacher_id}";
            throw ValidationException::withMessages([
                'class_id' => ["Kelas ini sudah memiliki jadwal ({$subjectName} bersama {$teacherName}) pada hari dan jam tersebut."],
            ]);
        }

        // 3. Room conflict check (if room is assigned)
        if ($roomId) {
            $roomConflict = Schedule::where('academic_year_id', $academicYearId)
                ->where('room_id', $roomId)
                ->where('hari', $hari)
                ->where('time_slot_id', $timeSlotId)
                ->where('status', 'aktif')
                ->when($ignoreScheduleId, fn ($q) => $q->where('id', '!=', $ignoreScheduleId))
                ->with(['schoolClass', 'room'])
                ->first();

            if ($roomConflict) {
                $roomName = $roomConflict->room?->nama ?? "Ruang #{$roomId}";
                $className = $roomConflict->schoolClass?->nama ?? "Kelas #{$roomConflict->class_id}";
                throw ValidationException::withMessages([
                    'room_id' => ["Ruangan {$roomName} sudah terpakai oleh {$className} pada hari dan jam tersebut."],
                ]);
            }
        }
    }
}
