<?php

namespace Database\Seeders;

use App\Models\Guardian;
use App\Models\Role;
use App\Models\SchoolProfile;
use App\Models\Staff;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roleKepsek = Role::where('nama', Role::KEPALA_SEKOLAH)->firstOrFail();
        $roleAdmin = Role::where('nama', Role::STAF_ADMIN)->firstOrFail();
        $roleGuru = Role::where('nama', Role::GURU)->firstOrFail();
        $roleWali = Role::where('nama', Role::WALI)->firstOrFail();

        // 1. Kepala Sekolah
        $userKepsek = User::firstOrCreate(
            ['username' => 'kepsek'],
            [
                'nama' => 'Drs. H. Ahmad Fauzi, M.Pd.',
                'email' => 'kepsek@simbim.sch.id',
                'no_hp' => '+6281200000001',
                'password' => Hash::make('Password123!'),
                'role_id' => $roleKepsek->id,
                'status' => 'aktif',
                'must_change_password' => false,
            ]
        );

        $teacherKepsek = Teacher::firstOrCreate(
            ['user_id' => $userKepsek->id],
            [
                'nip' => '197505122000031001',
                'nuptk' => '1234567890123456',
                'nik' => '3302011205750001',
                'nama_lengkap' => 'Ahmad Fauzi',
                'gelar_depan' => 'Drs. H.',
                'gelar_belakang' => 'M.Pd.',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Banyumas',
                'tgl_lahir' => '1975-05-12',
                'alamat_jalan' => 'Jl. Overste Isdiman No. 10',
                'kab_kota' => 'Banyumas',
                'provinsi' => 'Jawa Tengah',
                'no_hp' => '+6281200000001',
                'email' => 'kepsek@simbim.sch.id',
                'jenis_ptk' => 'kepala_sekolah',
                'status_kepegawaian' => 'gty',
                'tgl_mulai_tugas' => '2012-07-01',
                'jabatan' => 'Kepala Sekolah',
                'status' => 'aktif',
            ]
        );

        // Update school profile kepala_sekolah_id
        SchoolProfile::where('id', 1)->update(['kepala_sekolah_id' => $teacherKepsek->id]);

        // 2. Staf Admin
        $userAdmin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'nama' => 'Siti Nurhaliza, S.Kom.',
                'email' => 'admin@simbim.sch.id',
                'no_hp' => '+6281200000002',
                'password' => Hash::make('Password123!'),
                'role_id' => $roleAdmin->id,
                'status' => 'aktif',
                'must_change_password' => false,
            ]
        );

        Staff::firstOrCreate(
            ['user_id' => $userAdmin->id],
            [
                'nik' => '3302015508920002',
                'nama_lengkap' => 'Siti Nurhaliza, S.Kom.',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Purwokerto',
                'tgl_lahir' => '1992-08-15',
                'alamat_jalan' => 'Jl. HR Boenyamin No. 45',
                'kab_kota' => 'Banyumas',
                'provinsi' => 'Jawa Tengah',
                'no_hp' => '+6281200000002',
                'email' => 'admin@simbim.sch.id',
                'jabatan' => 'Operator & Tata Usaha',
                'status_kepegawaian' => 'kontrak',
                'tgl_mulai_tugas' => '2018-01-02',
                'status' => 'aktif',
            ]
        );

        // 3. Guru
        $userGuru = User::firstOrCreate(
            ['username' => 'guru'],
            [
                'nama' => 'Budi Santoso, S.Pd.',
                'email' => 'guru@simbim.sch.id',
                'no_hp' => '+6281200000003',
                'password' => Hash::make('Password123!'),
                'role_id' => $roleGuru->id,
                'status' => 'aktif',
                'must_change_password' => false,
            ]
        );

        Teacher::firstOrCreate(
            ['user_id' => $userGuru->id],
            [
                'nip' => '198803152015041002',
                'nuptk' => '2345678901234567',
                'nik' => '3302011503880003',
                'nama_lengkap' => 'Budi Santoso',
                'gelar_belakang' => 'S.Pd.',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Purwokerto',
                'tgl_lahir' => '1988-03-15',
                'alamat_jalan' => 'Jl. Jenderal Soedirman No. 120',
                'kab_kota' => 'Banyumas',
                'provinsi' => 'Jawa Tengah',
                'no_hp' => '+6281200000003',
                'email' => 'guru@simbim.sch.id',
                'jenis_ptk' => 'guru_kelas',
                'status_kepegawaian' => 'gty',
                'tgl_mulai_tugas' => '2016-07-15',
                'jabatan' => 'Guru Kelas 3',
                'status' => 'aktif',
            ]
        );

        // 4. Wali Murid
        $userWali = User::firstOrCreate(
            ['username' => 'wali'],
            [
                'nama' => 'Hendra Setiawan',
                'email' => 'wali@simbim.sch.id',
                'no_hp' => '+6281200000004',
                'password' => Hash::make('Password123!'),
                'role_id' => $roleWali->id,
                'status' => 'aktif',
                'must_change_password' => false,
            ]
        );

        Guardian::firstOrCreate(
            ['user_id' => $userWali->id],
            [
                'hubungan' => 'ayah',
                'nama_lengkap' => 'Hendra Setiawan',
                'nik' => '3302012010800004',
                'tempat_lahir' => 'Purwokerto',
                'tgl_lahir' => '1980-10-20',
                'status_hidup' => 'hidup',
                'no_hp' => '+6281200000004',
                'no_whatsapp' => '+6281200000004',
                'email' => 'wali@simbim.sch.id',
                'alamat_jalan' => 'Jl. Gerilya No. 88',
                'kab_kota' => 'Banyumas',
                'provinsi' => 'Jawa Tengah',
            ]
        );
    }
}
