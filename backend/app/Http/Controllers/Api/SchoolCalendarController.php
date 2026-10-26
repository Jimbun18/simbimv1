<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolCalendar;
use App\Models\Semester;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolCalendarController extends Controller
{
    /**
     * Ambil daftar agenda kalender & hari libur
     */
    public function index(Request $request): JsonResponse
    {
        $query = SchoolCalendar::with(['academicYear', 'semester', 'creator:id,nama']);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->query('academic_year_id'));
        }

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->query('semester_id'));
        }

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->query('tipe'));
        }

        if ($request->has('is_libur') && $request->query('is_libur') !== '') {
            $query->where('is_libur', $request->boolean('is_libur'));
        }

        // Filter rentang tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = $request->query('start_date');
            $end = $request->query('end_date');
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('tanggal_mulai', [$start, $end])
                  ->orWhereBetween('tanggal_selesai', [$start, $end])
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->where('tanggal_mulai', '<=', $start)
                          ->where('tanggal_selesai', '>=', $end);
                  });
            });
        } elseif ($request->filled('bulan') && $request->filled('tahun')) {
            $tahun = (int) $request->query('tahun');
            $bulan = (int) $request->query('bulan');
            $start = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $end = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->toDateString();
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('tanggal_mulai', [$start, $end])
                  ->orWhereBetween('tanggal_selesai', [$start, $end])
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->where('tanggal_mulai', '<=', $start)
                          ->where('tanggal_selesai', '>=', $end);
                  });
            });
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $events = $query->orderBy('tanggal_mulai', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data kalender akademik berhasil dimuat.',
            'data' => $events,
        ]);
    }

    /**
     * Cek status hari ini / tanggal tertentu (apakah libur / KBM normal)
     */
    public function checkDate(Request $request): JsonResponse
    {
        $tanggal = $request->query('tanggal', now()->toDateString());
        $status = SchoolCalendar::checkHoliday($tanggal);

        // Ambil juga semua agenda di tanggal ini (termasuk kegiatan non-libur)
        $eventsToday = SchoolCalendar::where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Status tanggal berhasil diperiksa.',
            'data' => [
                'tanggal' => $tanggal,
                'is_holiday' => $status['is_holiday'],
                'is_sunday' => $status['is_sunday'],
                'holiday_name' => $status['name'],
                'holiday_type' => $status['type'],
                'events' => $eventsToday,
            ],
        ]);
    }

    /**
     * Tambah agenda / hari libur baru
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'tipe' => ['required', 'string', 'in:libur_nasional,libur_sekolah,libur_semester,kbm_khusus,kegiatan_sekolah'],
            'is_libur' => ['boolean'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'warna' => ['nullable', 'string', 'max:30'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester_id' => ['nullable', 'exists:semesters,id'],
        ]);

        $validated['tanggal_selesai'] = $validated['tanggal_selesai'] ?? $validated['tanggal_mulai'];
        $validated['is_libur'] = $request->boolean('is_libur', in_array($validated['tipe'], ['libur_nasional', 'libur_sekolah', 'libur_semester']));

        // Warna default berdasarkan tipe jika tidak diisi
        if (empty($validated['warna'])) {
            $defaultColors = [
                'libur_nasional' => '#ef4444', // Merah
                'libur_sekolah' => '#f97316',  // Oranye
                'libur_semester' => '#dc2626', // Merah Tua
                'kbm_khusus' => '#10b981',     // Hijau Emerald
                'kegiatan_sekolah' => '#3b82f6', // Biru
            ];
            $validated['warna'] = $defaultColors[$validated['tipe']] ?? '#64748b';
        }

        // Deteksi academic year & semester jika kosong
        if (empty($validated['academic_year_id'])) {
            $activeYear = AcademicYear::getActive();
            $validated['academic_year_id'] = $activeYear?->id;
        }

        if (empty($validated['semester_id'])) {
            $sem = Semester::findByDate($validated['tanggal_mulai']);
            $validated['semester_id'] = $sem?->id;
        }

        $validated['created_by'] = $request->user()->id;

        $event = SchoolCalendar::create($validated);

        ActivityLogService::log(
            aksi: 'create_calendar_event',
            modul: 'school_calendar',
            targetType: SchoolCalendar::class,
            targetId: $event->id,
            dataSebelum: null,
            dataSesudah: $event->toArray(),
            keterangan: "Menambahkan agenda kalender: {$event->judul} (" . ($event->is_libur ? 'Hari Libur' : 'Kegiatan') . ")."
        );

        return response()->json([
            'success' => true,
            'message' => 'Agenda kalender akademik berhasil ditambahkan.',
            'data' => $event->load(['academicYear', 'semester']),
        ], 201);
    }

    /**
     * Perbarui agenda kalender
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $event = SchoolCalendar::findOrFail($id);

        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'tipe' => ['required', 'string', 'in:libur_nasional,libur_sekolah,libur_semester,kbm_khusus,kegiatan_sekolah'],
            'is_libur' => ['boolean'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'warna' => ['nullable', 'string', 'max:30'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester_id' => ['nullable', 'exists:semesters,id'],
        ]);

        $validated['tanggal_selesai'] = $validated['tanggal_selesai'] ?? $validated['tanggal_mulai'];
        $validated['is_libur'] = $request->boolean('is_libur', in_array($validated['tipe'], ['libur_nasional', 'libur_sekolah', 'libur_semester']));
        $validated['updated_by'] = $request->user()->id;

        $oldData = $event->toArray();
        $event->update($validated);

        ActivityLogService::log(
            aksi: 'update_calendar_event',
            modul: 'school_calendar',
            targetType: SchoolCalendar::class,
            targetId: $event->id,
            dataSebelum: $oldData,
            dataSesudah: $event->fresh()->toArray(),
            keterangan: "Memperbarui agenda kalender: {$event->judul}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Agenda kalender akademik berhasil diperbarui.',
            'data' => $event->fresh(['academicYear', 'semester']),
        ]);
    }

    /**
     * Hapus agenda kalender
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $event = SchoolCalendar::findOrFail($id);
        $oldData = $event->toArray();
        $judul = $event->judul;

        $event->delete();

        ActivityLogService::log(
            aksi: 'delete_calendar_event',
            modul: 'school_calendar',
            targetType: SchoolCalendar::class,
            targetId: $id,
            dataSebelum: $oldData,
            dataSesudah: null,
            keterangan: "Menghapus agenda kalender: {$judul}."
        );

        return response()->json([
            'success' => true,
            'message' => "Agenda '{$judul}' berhasil dihapus dari kalender.",
        ]);
    }

    /**
     * Seed Template Hari Libur Nasional & Libur Sekolah Resmi untuk Tahun Aktif
     */
    public function seedTemplate(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', now()->year);
        $userId = $request->user()?->id;
        $activeYear = AcademicYear::getActive();

        // Template hari libur umum & pendidikan di Indonesia (Tahun Masehi & Perkiraan Hijriyah)
        $templates = [
            // Semester Gasal (Juli - Desember)
            ['judul' => 'Tahun Baru Islam (1 Muharram)', 'tanggal' => "{$year}-07-07", 'tipe' => 'libur_nasional', 'is_libur' => true, 'warna' => '#ef4444'],
            ['judul' => 'Hari Kemerdekaan RI Ke-'.($year - 1945), 'tanggal' => "{$year}-08-17", 'tipe' => 'libur_nasional', 'is_libur' => true, 'warna' => '#ef4444'],
            ['judul' => 'Maulid Nabi Muhammad SAW', 'tanggal' => "{$year}-09-15", 'tipe' => 'libur_nasional', 'is_libur' => true, 'warna' => '#ef4444'],
            ['judul' => 'Penilaian Sumatif Tengah Semester (ASTS) Gasal', 'tanggal_mulai' => "{$year}-09-23", 'tanggal_selesai' => "{$year}-09-28", 'tipe' => 'kbm_khusus', 'is_libur' => false, 'warna' => '#10b981'],
            ['judul' => 'Hari Guru Nasional & Milad SDI BIM', 'tanggal' => "{$year}-11-25", 'tipe' => 'kegiatan_sekolah', 'is_libur' => false, 'warna' => '#3b82f6'],
            ['judul' => 'Penilaian Sumatif Akhir Semester (ASAS) Gasal', 'tanggal_mulai' => "{$year}-12-02", 'tanggal_selesai' => "{$year}-12-07", 'tipe' => 'kbm_khusus', 'is_libur' => false, 'warna' => '#10b981'],
            ['judul' => 'Pembagian Rapor Semester Gasal', 'tanggal' => "{$year}-12-20", 'tipe' => 'kegiatan_sekolah', 'is_libur' => false, 'warna' => '#8b5cf6'],
            ['judul' => 'Libur Akhir Semester Gasal & Cuti Bersama', 'tanggal_mulai' => "{$year}-12-23", 'tanggal_selesai' => "{$year}-12-31", 'tipe' => 'libur_semester', 'is_libur' => true, 'warna' => '#dc2626'],
            ['judul' => 'Hari Raya Natal', 'tanggal' => "{$year}-12-25", 'tipe' => 'libur_nasional', 'is_libur' => true, 'warna' => '#ef4444'],

            // Semester Genap (Januari - Juni tahun berikutnya)
            ['judul' => 'Tahun Baru Masehi', 'tanggal' => ($year + 1).'-01-01', 'tipe' => 'libur_nasional', 'is_libur' => true, 'warna' => '#ef4444'],
            ['judul' => 'Isra Mi\'raj Nabi Muhammad SAW', 'tanggal' => ($year + 1).'-01-27', 'tipe' => 'libur_nasional', 'is_libur' => true, 'warna' => '#ef4444'],
            ['judul' => 'Libur Awal Ramadhan 1448 H', 'tanggal_mulai' => ($year + 1).'-03-01', 'tanggal_selesai' => ($year + 1).'-03-03', 'tipe' => 'libur_sekolah', 'is_libur' => true, 'warna' => '#f97316'],
            ['judul' => 'Pesantren Ramadhan SDI BIM', 'tanggal_mulai' => ($year + 1).'-03-10', 'tanggal_selesai' => ($year + 1).'-03-15', 'tipe' => 'kbm_khusus', 'is_libur' => false, 'warna' => '#10b981'],
            ['judul' => 'Hari Raya Idul Fitri & Cuti Bersama', 'tanggal_mulai' => ($year + 1).'-03-29', 'tanggal_selesai' => ($year + 1).'-04-06', 'tipe' => 'libur_nasional', 'is_libur' => true, 'warna' => '#ef4444'],
            ['judul' => 'Hari Buruh Internasional', 'tanggal' => ($year + 1).'-05-01', 'tipe' => 'libur_nasional', 'is_libur' => true, 'warna' => '#ef4444'],
            ['judul' => 'Hari Pendidikan Nasional', 'tanggal' => ($year + 1).'-05-02', 'tipe' => 'kegiatan_sekolah', 'is_libur' => false, 'warna' => '#3b82f6'],
            ['judul' => 'Kenaikan Isa Almasih', 'tanggal' => ($year + 1).'-05-29', 'tipe' => 'libur_nasional', 'is_libur' => true, 'warna' => '#ef4444'],
            ['judul' => 'Hari Lahir Pancasila', 'tanggal' => ($year + 1).'-06-01', 'tipe' => 'libur_nasional', 'is_libur' => true, 'warna' => '#ef4444'],
            ['judul' => 'Hari Raya Idul Adha 1448 H', 'tanggal' => ($year + 1).'-06-06', 'tipe' => 'libur_nasional', 'is_libur' => true, 'warna' => '#ef4444'],
            ['judul' => 'Penilaian Akhir Tahun (PAT) / ASAS Genap', 'tanggal_mulai' => ($year + 1).'-06-09', 'tanggal_selesai' => ($year + 1).'-06-14', 'tipe' => 'kbm_khusus', 'is_libur' => false, 'warna' => '#10b981'],
            ['judul' => 'Pembagian Rapor Semester Genap & Kenaikan Kelas', 'tanggal' => ($year + 1).'-06-21', 'tipe' => 'kegiatan_sekolah', 'is_libur' => false, 'warna' => '#8b5cf6'],
            ['judul' => 'Libur Akhir Tahun Ajaran', 'tanggal_mulai' => ($year + 1).'-06-23', 'tanggal_selesai' => ($year + 1).'-07-12', 'tipe' => 'libur_semester', 'is_libur' => true, 'warna' => '#dc2626'],
        ];

        $insertedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($templates as $tpl) {
                $tglMulai = $tpl['tanggal_mulai'] ?? $tpl['tanggal'];
                $tglSelesai = $tpl['tanggal_selesai'] ?? $tpl['tanggal'];

                // Hindari duplikasi jika judul dan tanggal sama
                $exists = SchoolCalendar::where('judul', $tpl['judul'])
                    ->where('tanggal_mulai', $tglMulai)
                    ->exists();

                if (!$exists) {
                    $sem = Semester::findByDate($tglMulai);

                    SchoolCalendar::create([
                        'judul' => $tpl['judul'],
                        'tanggal_mulai' => $tglMulai,
                        'tanggal_selesai' => $tglSelesai,
                        'tipe' => $tpl['tipe'],
                        'is_libur' => $tpl['is_libur'],
                        'warna' => $tpl['warna'],
                        'academic_year_id' => $activeYear?->id,
                        'semester_id' => $sem?->id,
                        'deskripsi' => 'Agenda otomatis kalender pendidikan SDI Bina Insan Mandiri',
                        'created_by' => $userId,
                    ]);
                    $insertedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menambahkan {$insertedCount} agenda & hari libur standar kalender akademik.",
                'data' => [
                    'inserted_count' => $insertedCount,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat template kalender: ' . $e->getMessage(),
            ], 500);
        }
    }
}
