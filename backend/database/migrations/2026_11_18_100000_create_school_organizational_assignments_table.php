<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_organizational_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('posisi_kode', 50)->index();
            $table->string('posisi_nama', 150);
            $table->string('posisi_kategori', 50)->default('struktural'); // eksekutif, operasional, wakil_kepala, pelaksana
            $table->string('parent_kode', 50)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('no_sk', 100)->nullable();
            $table->text('keterangan')->nullable();
            $table->json('menu_permissions')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // Seed 9 posisi standar sesuai bagan struktur organisasi resmi
        $defaultPositions = [
            [
                'posisi_kode' => 'kepala_sekolah',
                'posisi_nama' => 'Kepala Sekolah',
                'posisi_kategori' => 'eksekutif',
                'parent_kode' => null,
                'menu_permissions' => json_encode(['all']),
                'keterangan' => 'Pimpinan tertinggi satuan pendidikan SDI Bina Insan Mandiri Purwokerto.',
            ],
            [
                'posisi_kode' => 'bk',
                'posisi_nama' => 'Bimbingan Konseling (BK)',
                'posisi_kategori' => 'operasional',
                'parent_kode' => 'kepala_sekolah',
                'menu_permissions' => json_encode(['bk_monitoring', 'observations', 'students']),
                'keterangan' => 'Layanan bimbingan konseling, asesmen perkembangan, dan intervensi peserta didik.',
            ],
            [
                'posisi_kode' => 'bendahara',
                'posisi_nama' => 'Bendahara Sekolah',
                'posisi_kategori' => 'operasional',
                'parent_kode' => 'kepala_sekolah',
                'menu_permissions' => json_encode(['finance', 'invoices', 'payments', 'finance_transactions', 'fee_types']),
                'keterangan' => 'Pengelolaan SPP, kasir kuitansi pembayaran siswa, dan buku kas operasional sekolah.',
            ],
            [
                'posisi_kode' => 'kepala_tu',
                'posisi_nama' => 'Kepala Tata Usaha',
                'posisi_kategori' => 'operasional',
                'parent_kode' => 'kepala_sekolah',
                'menu_permissions' => json_encode(['assets', 'teachers', 'excel_imports', 'users_settings', 'backup']),
                'keterangan' => 'Pengelolaan administrasi kepegawaian, sarana prasarana/aset, dan sistem sekolah.',
            ],
            [
                'posisi_kode' => 'waka_kurikulum',
                'posisi_nama' => 'Waka Kurikulum',
                'posisi_kategori' => 'wakil_kepala',
                'parent_kode' => 'kepala_sekolah',
                'menu_permissions' => json_encode(['classes_schedules', 'calendar', 'teaching_assignments', 'buku_nilai', 'rapor']),
                'keterangan' => 'Perencanaan & pengendalian KBM, rombel kelas, jadwal pelajaran mingguan, dan buku nilai.',
            ],
            [
                'posisi_kode' => 'waka_kesiswaan',
                'posisi_nama' => 'Waka Kesiswaan',
                'posisi_kategori' => 'wakil_kepala',
                'parent_kode' => 'kepala_sekolah',
                'menu_permissions' => json_encode(['students', 'guardians', 'ppdb_management', 'progress_activities', 'observations']),
                'keterangan' => 'Pengembangan karakter, kesiswaan, PPDB, ekstrakurikuler, dan kedisiplinan santri.',
            ],
            [
                'posisi_kode' => 'bina_kesiswaan',
                'posisi_nama' => 'Bina Kesiswaan',
                'posisi_kategori' => 'pelaksana',
                'parent_kode' => 'waka_kesiswaan',
                'menu_permissions' => json_encode(['students', 'observations', 'progress_activities']),
                'keterangan' => 'Pembinaan kedisiplinan, tata tertib, dan monitoring kegiatan harian siswa.',
            ],
            [
                'posisi_kode' => 'bina_potensi',
                'posisi_nama' => 'Bina Potensi',
                'posisi_kategori' => 'pelaksana',
                'parent_kode' => 'waka_kesiswaan',
                'menu_permissions' => json_encode(['progress_activities', 'students']),
                'keterangan' => 'Pengembangan minat, bakat, ekstrakurikuler, tahfidz, dan life skill siswa.',
            ],
            [
                'posisi_kode' => 'bina_prestasi',
                'posisi_nama' => 'Bina Prestasi',
                'posisi_kategori' => 'pelaksana',
                'parent_kode' => 'waka_kesiswaan',
                'menu_permissions' => json_encode(['progress_activities', 'cms', 'students']),
                'keterangan' => 'Pendampingan kejuaraan, rekam jejak prestasi siswa, dan publikasi apresiasi.',
            ],
        ];

        foreach ($defaultPositions as $pos) {
            DB::table('school_organizational_assignments')->insert([
                'posisi_kode' => $pos['posisi_kode'],
                'posisi_nama' => $pos['posisi_nama'],
                'posisi_kategori' => $pos['posisi_kategori'],
                'parent_kode' => $pos['parent_kode'],
                'user_id' => null,
                'teacher_id' => null,
                'staff_id' => null,
                'academic_year_id' => null,
                'no_sk' => null,
                'keterangan' => $pos['keterangan'],
                'menu_permissions' => $pos['menu_permissions'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_organizational_assignments');
    }
};
