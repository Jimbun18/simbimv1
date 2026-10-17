<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'key' => 'geofence_enabled',
                'value' => '1',
                'tipe' => 'boolean',
                'grup' => 'absensi',
                'deskripsi' => 'Aktifkan validasi lokasi radius geofence untuk absensi guru dan tendik',
            ],
            [
                'key' => 'require_selfie_teacher',
                'value' => '0',
                'tipe' => 'boolean',
                'grup' => 'absensi',
                'deskripsi' => 'Wajib upload foto selfie saat melakukan absen masuk atau pulang (0 = opsional, 1 = wajib)',
            ],
            [
                'key' => 'require_teaching_evidence',
                'value' => '1',
                'tipe' => 'boolean',
                'grup' => 'absensi',
                'deskripsi' => 'Wajib upload foto bukti mengajar sebelum mengirim absensi murid',
            ],
            [
                'key' => 'evidence_mode',
                'value' => 'per_session',
                'tipe' => 'string',
                'grup' => 'absensi',
                'deskripsi' => 'Mode foto bukti mengajar: per_session (tiap sesi KBM) atau per_day (harian)',
            ],
            [
                'key' => 'student_attendance_edit_limit_hours',
                'value' => '24',
                'tipe' => 'integer',
                'grup' => 'absensi',
                'deskripsi' => 'Batas waktu (jam) guru dapat mengubah absensi murid sebelum terkunci untuk Admin saja',
            ],
            [
                'key' => 'auto_alpha_cutoff_time',
                'value' => '11:00',
                'tipe' => 'string',
                'grup' => 'absensi',
                'deskripsi' => 'Jam batas penandaan alpa otomatis jika belum ada presensi',
            ],
        ];

        foreach ($defaults as $item) {
            Setting::updateOrCreate(['key' => $item['key']], $item);
        }
    }
}
