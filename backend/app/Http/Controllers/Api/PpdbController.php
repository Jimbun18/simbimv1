<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Education;
use App\Models\Guardian;
use App\Models\IncomeRange;
use App\Models\Occupation;
use App\Models\PpdbRegistration;
use App\Models\Religion;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\ActivityLogService;
use App\Services\ImageCompressionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PpdbController extends Controller
{
    /**
     * Submit Formulir Pendaftaran PPDB Online (Publik Tanpa Login)
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jenjang' => ['required', 'string', 'in:KB,TK A,TK B,SD'],
            'tahun_ajaran' => ['nullable', 'string', 'max:20'],
            'gelombang' => ['nullable', 'string', 'max:50'],

            // Identitas Calon Peserta Didik
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nama_panggilan' => ['required', 'string', 'max:50'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],
            'usia_daftar' => ['nullable', 'string', 'max:50'],
            'jumlah_saudara' => ['nullable', 'integer', 'min:0'],
            'anak_ke' => ['nullable', 'integer', 'min:1'],
            'alamat_lengkap' => ['required', 'string'],
            'tinggal_dengan' => ['required', 'string', 'max:50'],
            'asal_sekolah' => ['nullable', 'string', 'max:150'],
            'alamat_asal_sekolah' => ['nullable', 'string'],
            'pindah_ke_kelas' => ['nullable', 'string', 'max:50'],
            'alasan_pindah' => ['nullable', 'string'],

            // Data Ayah
            'nama_ayah' => ['required', 'string', 'max:150'],
            'ttl_ayah' => ['nullable', 'string', 'max:150'],
            'pendidikan_ayah' => ['nullable', 'string', 'max:50'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:100'],
            'penghasilan_ayah' => ['nullable', 'string', 'max:100'],
            'alamat_ayah' => ['nullable', 'string'],
            'no_hp_ayah' => ['required', 'string', 'max:30'],

            // Data Ibu
            'nama_ibu' => ['required', 'string', 'max:150'],
            'ttl_ibu' => ['nullable', 'string', 'max:150'],
            'pendidikan_ibu' => ['nullable', 'string', 'max:50'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:100'],
            'penghasilan_ibu' => ['nullable', 'string', 'max:100'],
            'alamat_ibu' => ['nullable', 'string'],
            'no_hp_ibu' => ['required', 'string', 'max:30'],

            // Berkas Upload
            'pas_foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'akta_kelahiran' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'kartu_keluarga' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'required' => ':attribute wajib diisi.',
            'in' => 'Pilihan :attribute tidak valid.',
            'mimes' => 'Format file :attribute harus berupa format yang diizinkan (JPG, PNG, WebP, atau PDF).',
            'max.file' => 'Ukuran berkas :attribute tidak boleh melebihi 5MB.',
        ], [
            'jenjang' => 'Jenjang Sekolah',
            'nama_lengkap' => 'Nama Lengkap Calon Siswa',
            'nama_panggilan' => 'Nama Panggilan',
            'jenis_kelamin' => 'Jenis Kelamin',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'alamat_lengkap' => 'Alamat Tempat Tinggal',
            'tinggal_dengan' => 'Tinggal Bersama',
            'nama_ayah' => 'Nama Ayah',
            'no_hp_ayah' => 'No. Handphone / WhatsApp Ayah',
            'nama_ibu' => 'Nama Ibu',
            'no_hp_ibu' => 'No. Handphone / WhatsApp Ibu',
            'pas_foto' => 'Pas Foto Calon Siswa',
            'akta_kelahiran' => 'Akta Kelahiran',
            'kartu_keluarga' => 'Kartu Keluarga',
        ]);

        // Hitung Usia Otomatis jika belum ada
        if (empty($validated['usia_daftar']) && !empty($validated['tanggal_lahir'])) {
            $birth = Carbon::parse($validated['tanggal_lahir']);
            $now = Carbon::now();
            $diff = $birth->diff($now);
            $validated['usia_daftar'] = "{$diff->y} tahun {$diff->m} bulan";
        }

        // Generate Nomor Pendaftaran Otomatis: PPDB-YYYY-JENJANG-XXXX
        $year = date('Y');
        $jenjangCode = match ($validated['jenjang']) {
            'SD' => 'SD',
            'TK A' => 'TKA',
            'TK B' => 'TKB',
            'KB' => 'KB',
            default => 'REG',
        };

        $prefix = "PPDB-{$year}-{$jenjangCode}-";
        $lastRegistration = PpdbRegistration::where('nomor_pendaftaran', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($lastRegistration) {
            $parts = explode('-', $lastRegistration->nomor_pendaftaran);
            $lastNum = (int) end($parts);
            $nextSeq = $lastNum + 1;
        }

        $validated['nomor_pendaftaran'] = $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        $validated['status'] = 'menunggu';
        $validated['tahun_ajaran'] = $validated['tahun_ajaran'] ?? '2026/2027';
        $validated['gelombang'] = $validated['gelombang'] ?? 'Gelombang 1';

        // Simpan Berkas Unggahan
        if ($request->hasFile('pas_foto')) {
            $file = $request->file('pas_foto');
            // Jika image compressor tersedia dan format image
            if (in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'webp'])) {
                $validated['pas_foto_path'] = app(ImageCompressionService::class)->compressAndStore(
                    $file,
                    'ppdb/photos'
                );
            } else {
                $validated['pas_foto_path'] = $file->store('ppdb/photos', 'public');
            }
        }

        if ($request->hasFile('akta_kelahiran')) {
            $file = $request->file('akta_kelahiran');
            $validated['akta_kelahiran_path'] = $file->store('ppdb/documents', 'public');
        }

        if ($request->hasFile('kartu_keluarga')) {
            $file = $request->file('kartu_keluarga');
            $validated['kartu_keluarga_path'] = $file->store('ppdb/documents', 'public');
        }

        // Hapus file dari validated array sebelum create model
        unset($validated['pas_foto'], $validated['akta_kelahiran'], $validated['kartu_keluarga']);

        $registration = PpdbRegistration::create($validated);

        // Catat Log Aktivitas
        ActivityLogService::log(
            aksi: 'ppdb_submit',
            modul: 'ppdb',
            targetType: PpdbRegistration::class,
            targetId: $registration->id,
            dataSesudah: $registration->toArray(),
            keterangan: "Pendaftaran PPDB Baru: {$registration->nama_lengkap} ({$registration->nomor_pendaftaran}) jenjang {$registration->jenjang}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Alhamdulillah, formulir pendaftaran PPDB berhasil dikirim!',
            'data' => [
                'id' => $registration->id,
                'nomor_pendaftaran' => $registration->nomor_pendaftaran,
                'nama_lengkap' => $registration->nama_lengkap,
                'jenjang' => $registration->jenjang,
                'status' => $registration->status,
                'status_label' => $registration->status_label,
                'tanggal_daftar' => $registration->created_at->format('d M Y H:i'),
            ],
        ], 201);
    }

    /**
     * Cek Status Pendaftaran PPDB oleh Orang Tua (Publik Tanpa Login)
     */
    public function checkStatus(string $nomorPendaftaran): JsonResponse
    {
        $nomor = trim($nomorPendaftaran);
        $registration = PpdbRegistration::where('nomor_pendaftaran', $nomor)
            ->orWhere('id', is_numeric($nomor) ? (int)$nomor : 0)
            ->first();

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor registrasi PPDB tidak ditemukan. Mohon periksa kembali nomor pendaftaran Anda.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nomor_pendaftaran' => $registration->nomor_pendaftaran,
                'nama_lengkap' => $registration->nama_lengkap,
                'nama_panggilan' => $registration->nama_panggilan,
                'jenjang' => $registration->jenjang,
                'tahun_ajaran' => $registration->tahun_ajaran,
                'gelombang' => $registration->gelombang,
                'status' => $registration->status,
                'status_label' => $registration->status_label,
                'catatan_panitia' => $registration->catatan_admin,
                'tanggal_daftar' => $registration->created_at->format('d M Y H:i'),
                'pas_foto_url' => $registration->pas_foto_url,
            ],
        ]);
    }

    /**
     * ADMIN: List Pendaftar PPDB (Filter, Pencarian, & Pagination)
     */
    public function index(Request $request): JsonResponse
    {
        $query = PpdbRegistration::with(['verifiedBy:id,nama', 'student:id,nis,nama_lengkap,class_id,status'])
            ->orderByDesc('created_at');

        // Pencarian Nama, No Pendaftaran, Orang Tua, No WA
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nama_panggilan', 'like', "%{$search}%")
                  ->orWhere('nomor_pendaftaran', 'like', "%{$search}%")
                  ->orWhere('nama_ayah', 'like', "%{$search}%")
                  ->orWhere('nama_ibu', 'like', "%{$search}%")
                  ->orWhere('no_hp_ayah', 'like', "%{$search}%")
                  ->orWhere('no_hp_ibu', 'like', "%{$search}%")
                  ->orWhere('asal_sekolah', 'like', "%{$search}%");
            });
        }

        // Filter Jenjang (KB, TK A, TK B, SD)
        if ($request->filled('jenjang') && $request->input('jenjang') !== 'all') {
            $query->where('jenjang', $request->input('jenjang'));
        }

        // Filter Status (menunggu, diterima, cadangan, ditolak)
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Filter Tahun Ajaran
        if ($request->filled('tahun_ajaran') && $request->input('tahun_ajaran') !== 'all') {
            $query->where('tahun_ajaran', $request->input('tahun_ajaran'));
        }

        // Filter Rentang Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $registrations = $query->paginate($perPage);

        // Ringkasan Statistik
        $summary = [
            'total' => PpdbRegistration::count(),
            'kb' => PpdbRegistration::where('jenjang', 'KB')->count(),
            'tka' => PpdbRegistration::where('jenjang', 'TK A')->count(),
            'tkb' => PpdbRegistration::where('jenjang', 'TK B')->count(),
            'sd' => PpdbRegistration::where('jenjang', 'SD')->count(),
            'menunggu' => PpdbRegistration::where('status', 'menunggu')->count(),
            'diterima' => PpdbRegistration::where('status', 'diterima')->count(),
            'cadangan' => PpdbRegistration::where('status', 'cadangan')->count(),
            'ditolak' => PpdbRegistration::where('status', 'ditolak')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data pendaftar PPDB berhasil diambil.',
            'data' => $registrations,
            'summary' => $summary,
        ]);
    }

    /**
     * ADMIN: Detail Pendaftar
     */
    public function show(int $id): JsonResponse
    {
        $registration = PpdbRegistration::with(['verifiedBy:id,nama', 'student:id,nis,nama_lengkap,class_id,status'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $registration,
        ]);
    }

    /**
     * ADMIN: Update Status & Verifikasi Pendaftaran
     * Catatan: Jika status menjadi 'diterima', otomatis masuk ke Data Master Siswa & Orang Tua.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:menunggu,diterima,cadangan,ditolak'],
            'catatan_admin' => ['nullable', 'string'],
        ]);

        $registration = PpdbRegistration::findOrFail($id);
        $oldData = $registration->toArray();
        $newStatus = $request->input('status');

        $registration->update([
            'status' => $newStatus,
            'catatan_admin' => $request->input('catatan_admin'),
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $createdStudent = null;
        if ($newStatus === 'diterima') {
            // Otomatis masukkan / aktifkan di Data Master Siswa & Orang Tua
            $createdStudent = $this->syncToMasterStudent($registration);
        } elseif ($registration->student_id) {
            // Jika status dibatalkan dari diterima, jadikan status siswa nonaktif
            Student::where('id', $registration->student_id)->update(['status' => 'nonaktif']);
        }

        ActivityLogService::log(
            aksi: 'ppdb_update_status',
            modul: 'ppdb',
            targetType: PpdbRegistration::class,
            targetId: $registration->id,
            dataSebelum: $oldData,
            dataSesudah: $registration->fresh()->toArray(),
            keterangan: "Admin memperbarui status PPDB {$registration->nama_lengkap} ({$registration->nomor_pendaftaran}) menjadi {$registration->status_label}"
        );

        $message = ($newStatus === 'diterima' && $createdStudent)
            ? "Status pendaftaran diterima. Data siswa dan orang tua berhasil otomatis masuk ke Data Master (NIS: {$createdStudent->nis})."
            : 'Status pendaftaran PPDB berhasil diperbarui.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $registration->fresh()->load(['verifiedBy:id,nama', 'student:id,nis,nama_lengkap,class_id,status']),
        ]);
    }

    /**
     * Otomatis Sinkronkan Data Pendaftar Diterima ke Master Siswa & Orang Tua
     */
    protected function syncToMasterStudent(PpdbRegistration $registration): Student
    {
        return DB::transaction(function () use ($registration) {
            $student = null;

            if ($registration->student_id) {
                $student = Student::find($registration->student_id);
            }

            if (!$student) {
                $student = Student::where('nama_lengkap', $registration->nama_lengkap)
                    ->whereDate('tgl_lahir', $registration->tanggal_lahir)
                    ->first();
            }

            $religionId = Religion::where('nama', 'like', '%Islam%')->value('id') ?? 1;

            $tinggal = 'orang_tua';
            $tdLower = strtolower($registration->tinggal_dengan ?? '');
            if (str_contains($tdLower, 'wali')) {
                $tinggal = 'wali';
            } elseif (str_contains($tdLower, 'asrama')) {
                $tinggal = 'asrama';
            } elseif (str_contains($tdLower, 'kakek') || str_contains($tdLower, 'bibi') || str_contains($tdLower, 'uwa')) {
                $tinggal = 'lainnya';
            }

            $tingkat = null;
            if (!empty($registration->pindah_ke_kelas)) {
                $tingkat = (int) $registration->pindah_ke_kelas;
            } elseif ($registration->jenjang === 'SD') {
                $tingkat = 1;
            }

            $classId = null;
            if ($tingkat) {
                $classId = SchoolClass::where('tingkat', $tingkat)->where('status', 'aktif')->value('id');
            }

            preg_match('/(\d{4})/', $registration->tahun_ajaran, $yrMatch);
            $tahunMasuk = isset($yrMatch[1]) ? (int) $yrMatch[1] : (int) date('Y');
            $jenisPendaftaran = !empty($registration->pindah_ke_kelas) ? 'pindahan' : 'siswa_baru';

            if (!$student) {
                $nis = $this->generateNextNis($registration->tahun_ajaran, $registration->jenjang);

                $student = Student::create([
                    'nis' => $nis,
                    'nama_lengkap' => $registration->nama_lengkap,
                    'nama_panggilan' => $registration->nama_panggilan,
                    'jenis_kelamin' => $registration->jenis_kelamin,
                    'tempat_lahir' => $registration->tempat_lahir,
                    'tgl_lahir' => $registration->tanggal_lahir,
                    'agama' => $religionId,
                    'kewarganegaraan' => 'WNI',
                    'anak_ke' => $registration->anak_ke ?: 1,
                    'jumlah_saudara' => $registration->jumlah_saudara ?: 0,
                    'alamat_jalan' => $registration->alamat_lengkap,
                    'kab_kota' => 'Kabupaten Banyumas',
                    'provinsi' => 'Jawa Tengah',
                    'tinggal_bersama' => $tinggal,
                    'asal_sekolah_tk' => $registration->asal_sekolah,
                    'jenis_pendaftaran' => $jenisPendaftaran,
                    'tgl_masuk' => now()->toDateString(),
                    'tahun_masuk' => $tahunMasuk,
                    'kelas_saat_masuk' => $tingkat,
                    'class_id' => $classId,
                    'status' => 'aktif',
                    'foto_path' => $registration->pas_foto_path,
                    'catatan' => "Siswa diterima dari PPDB Online: {$registration->nomor_pendaftaran} ({$registration->gelombang} - Jenjang {$registration->jenjang})",
                    'created_by' => auth()->id(),
                ]);

                $registration->update(['student_id' => $student->id]);
            } else {
                $student->update([
                    'status' => 'aktif',
                    'class_id' => $student->class_id ?: $classId,
                    'foto_path' => $student->foto_path ?: $registration->pas_foto_path,
                    'alamat_jalan' => $registration->alamat_lengkap,
                ]);

                if ($registration->student_id !== $student->id) {
                    $registration->update(['student_id' => $student->id]);
                }
            }

            // Hubungkan Orang Tua (Ayah & Ibu)
            $this->syncStudentGuardians($student, $registration);

            // Enroll ke kelas jika ada class_id
            if ($student->class_id) {
                $activeYear = AcademicYear::getActive() ?? AcademicYear::latest()->first();
                if ($activeYear) {
                    StudentEnrollment::firstOrCreate([
                        'student_id' => $student->id,
                        'academic_year_id' => $activeYear->id,
                    ], [
                        'class_id' => $student->class_id,
                        'tgl_masuk_kelas' => $student->tgl_masuk ?: now()->toDateString(),
                        'status_kelas' => 'aktif',
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            ActivityLogService::log(
                aksi: 'create',
                modul: 'students',
                targetType: Student::class,
                targetId: $student->id,
                dataSesudah: $student->fresh()->toArray(),
                keterangan: "Siswa {$student->nama_lengkap} (NIS: {$student->nis}) otomatis dimasukkan ke Data Master Siswa dari PPDB Online ({$registration->nomor_pendaftaran})."
            );

            return $student;
        });
    }

    /**
     * Generate NIS Baru Otomatis untuk Siswa Baru
     */
    protected function generateNextNis(string $tahunAjaran = '2026/2027', string $jenjang = 'SD'): string
    {
        preg_match('/(\d{4})/', $tahunAjaran, $matches);
        $yearPrefix = isset($matches[1]) ? substr($matches[1], -2) : date('y');

        $levelCode = match (strtoupper(trim($jenjang))) {
            'SD' => '01',
            'TK B' => '02',
            'TK A' => '03',
            'KB' => '04',
            default => '01',
        };

        $prefix = $yearPrefix . $levelCode; // e.g. 2601

        $lastNis = Student::where('nis', 'like', "{$prefix}%")
            ->orderByRaw('LENGTH(nis) DESC')
            ->orderBy('nis', 'desc')
            ->value('nis');

        if ($lastNis && strlen($lastNis) >= 6) {
            $sequence = (int) substr($lastNis, 4) + 1;
        } else {
            $sequence = 1;
        }

        $candidate = $prefix . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);

        while (Student::where('nis', $candidate)->exists()) {
            $sequence++;
            $candidate = $prefix . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    /**
     * Hubungkan Data Ayah & Ibu ke Tabel Master Guardians & Student_Guardian
     */
    protected function syncStudentGuardians(Student $student, PpdbRegistration $registration): void
    {
        // AYAH
        if (!empty($registration->nama_ayah)) {
            $occId = null;
            if ($registration->pekerjaan_ayah) {
                $occId = Occupation::where('nama', 'like', '%' . trim($registration->pekerjaan_ayah) . '%')->value('id');
            }
            $eduId = null;
            if ($registration->pendidikan_ayah) {
                $eduId = Education::where('nama', 'like', '%' . trim($registration->pendidikan_ayah) . '%')->value('id');
            }
            $incId = null;
            if ($registration->penghasilan_ayah) {
                $cleanInc = str_replace(['Rp', '.', ' '], '', $registration->penghasilan_ayah);
                $incId = IncomeRange::where('nama', 'like', '%' . $cleanInc . '%')->value('id');
            }

            $cleanPhone = substr(preg_replace('/[^0-9]/', '', $registration->no_hp_ayah), 0, 16);

            $ayah = $student->guardians()->wherePivot('hubungan', 'ayah')->first();
            if (!$ayah && $cleanPhone) {
                $ayah = Guardian::where('no_hp', $cleanPhone)->where('nama_lengkap', $registration->nama_ayah)->first();
            }

            if ($ayah) {
                $ayah->update([
                    'nama_lengkap' => $registration->nama_ayah,
                    'pekerjaan' => $occId ?: $ayah->pekerjaan,
                    'pendidikan_terakhir' => $eduId ?: $ayah->pendidikan_terakhir,
                    'penghasilan_bulanan' => $incId ?: $ayah->penghasilan_bulanan,
                    'no_hp' => $cleanPhone ?: $ayah->no_hp,
                    'no_whatsapp' => $cleanPhone ?: $ayah->no_whatsapp,
                    'alamat_jalan' => $registration->alamat_ayah ?: $registration->alamat_lengkap,
                ]);
            } else {
                $ayah = Guardian::create([
                    'hubungan' => 'ayah',
                    'nama_lengkap' => $registration->nama_ayah,
                    'pekerjaan' => $occId,
                    'pendidikan_terakhir' => $eduId,
                    'penghasilan_bulanan' => $incId,
                    'no_hp' => $cleanPhone ?: '0812' . rand(10000000, 99999999),
                    'no_whatsapp' => $cleanPhone ?: '0812' . rand(10000000, 99999999),
                    'alamat_jalan' => $registration->alamat_ayah ?: $registration->alamat_lengkap,
                    'kab_kota' => 'Kabupaten Banyumas',
                    'provinsi' => 'Jawa Tengah',
                    'created_by' => auth()->id(),
                ]);
            }

            if (!$student->guardians()->where('guardians.id', $ayah->id)->exists()) {
                $student->guardians()->attach($ayah->id, [
                    'hubungan' => 'ayah',
                    'is_kontak_utama' => true,
                    'boleh_lihat_keuangan' => true,
                    'boleh_jemput' => true,
                ]);
            }
        }

        // IBU
        if (!empty($registration->nama_ibu)) {
            $occId = null;
            if ($registration->pekerjaan_ibu) {
                $occId = Occupation::where('nama', 'like', '%' . trim($registration->pekerjaan_ibu) . '%')->value('id');
                if (!$occId && (stripos($registration->pekerjaan_ibu, 'rumah tangga') !== false || stripos($registration->pekerjaan_ibu, 'irt') !== false)) {
                    $occId = Occupation::where('nama', 'like', '%Ibu Rumah Tangga%')->value('id');
                }
            }
            $eduId = null;
            if ($registration->pendidikan_ibu) {
                $eduId = Education::where('nama', 'like', '%' . trim($registration->pendidikan_ibu) . '%')->value('id');
            }
            $incId = null;
            if ($registration->penghasilan_ibu) {
                $cleanInc = str_replace(['Rp', '.', ' '], '', $registration->penghasilan_ibu);
                $incId = IncomeRange::where('nama', 'like', '%' . $cleanInc . '%')->value('id');
            }

            $cleanPhone = substr(preg_replace('/[^0-9]/', '', $registration->no_hp_ibu), 0, 16);

            $ibu = $student->guardians()->wherePivot('hubungan', 'ibu')->first();
            if (!$ibu && $cleanPhone) {
                $ibu = Guardian::where('no_hp', $cleanPhone)->where('nama_lengkap', $registration->nama_ibu)->first();
            }

            if ($ibu) {
                $ibu->update([
                    'nama_lengkap' => $registration->nama_ibu,
                    'pekerjaan' => $occId ?: $ibu->pekerjaan,
                    'pendidikan_terakhir' => $eduId ?: $ibu->pendidikan_terakhir,
                    'penghasilan_bulanan' => $incId ?: $ibu->penghasilan_bulanan,
                    'no_hp' => $cleanPhone ?: $ibu->no_hp,
                    'no_whatsapp' => $cleanPhone ?: $ibu->no_whatsapp,
                    'alamat_jalan' => $registration->alamat_ibu ?: $registration->alamat_lengkap,
                ]);
            } else {
                $ibu = Guardian::create([
                    'hubungan' => 'ibu',
                    'nama_lengkap' => $registration->nama_ibu,
                    'pekerjaan' => $occId,
                    'pendidikan_terakhir' => $eduId,
                    'penghasilan_bulanan' => $incId,
                    'no_hp' => $cleanPhone ?: '0812' . rand(10000000, 99999999),
                    'no_whatsapp' => $cleanPhone ?: '0812' . rand(10000000, 99999999),
                    'alamat_jalan' => $registration->alamat_ibu ?: $registration->alamat_lengkap,
                    'kab_kota' => 'Kabupaten Banyumas',
                    'provinsi' => 'Jawa Tengah',
                    'created_by' => auth()->id(),
                ]);
            }

            if (!$student->guardians()->where('guardians.id', $ibu->id)->exists()) {
                $student->guardians()->attach($ibu->id, [
                    'hubungan' => 'ibu',
                    'is_kontak_utama' => empty($registration->nama_ayah),
                    'boleh_lihat_keuangan' => true,
                    'boleh_jemput' => true,
                ]);
            }
        }
    }

    /**
     * ADMIN: Hapus Pendaftar
     */
    public function destroy(int $id): JsonResponse
    {
        $registration = PpdbRegistration::findOrFail($id);
        $oldData = $registration->toArray();

        // Hapus file dokumen
        if ($registration->pas_foto_path && Storage::disk('public')->exists($registration->pas_foto_path)) {
            Storage::disk('public')->delete($registration->pas_foto_path);
        }
        if ($registration->akta_kelahiran_path && Storage::disk('public')->exists($registration->akta_kelahiran_path)) {
            Storage::disk('public')->delete($registration->akta_kelahiran_path);
        }
        if ($registration->kartu_keluarga_path && Storage::disk('public')->exists($registration->kartu_keluarga_path)) {
            Storage::disk('public')->delete($registration->kartu_keluarga_path);
        }

        // Jika terhubung ke siswa master, nonaktifkan atau hapus
        if ($registration->student_id) {
            Student::where('id', $registration->student_id)->delete();
        }

        $registration->delete();

        ActivityLogService::log(
            aksi: 'ppdb_hapus',
            modul: 'ppdb',
            targetType: PpdbRegistration::class,
            targetId: $id,
            dataSebelum: $oldData,
            keterangan: "Admin menghapus data PPDB {$registration->nama_lengkap} ({$registration->nomor_pendaftaran})"
        );

        return response()->json([
            'success' => true,
            'message' => 'Data pendaftar PPDB berhasil dihapus.',
        ]);
    }

    /**
     * ADMIN: Export Data PPDB ke File Excel
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $query = PpdbRegistration::orderBy('id', 'asc');

        if ($request->filled('jenjang') && $request->input('jenjang') !== 'all') {
            $query->where('jenjang', $request->input('jenjang'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nomor_pendaftaran', 'like', "%{$search}%")
                  ->orWhere('nama_ayah', 'like', "%{$search}%")
                  ->orWhere('nama_ibu', 'like', "%{$search}%");
            });
        }

        $records = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data PPDB Online');

        // Header Dokumen
        $sheet->setCellValue('A1', 'DATA PENDAFTARAN PESERTA DIDIK BARU (PPDB) ONLINE');
        $sheet->setCellValue('A2', 'SEKOLAH ISLAM TERPADU BINA INSAN MANDIRI PURWOKERTO');
        $sheet->setCellValue('A3', 'Diekspor pada: ' . date('d F Y H:i:s') . ' WIB');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);

        // Header Kolom Tabel
        $headers = [
            'A5' => 'No',
            'B5' => 'No. Pendaftaran',
            'C5' => 'Tanggal Daftar',
            'D5' => 'Jenjang',
            'E5' => 'Nama Lengkap',
            'F5' => 'Nama Panggilan',
            'G5' => 'Jenis Kelamin',
            'H5' => 'Tempat Lahir',
            'I5' => 'Tanggal Lahir',
            'J5' => 'Usia Saat Daftar',
            'K5' => 'Anak Ke',
            'L5' => 'Jml Sdr',
            'M5' => 'Alamat Lengkap',
            'N5' => 'Tinggal Bersama',
            'O5' => 'Asal Sekolah',
            'P5' => 'Alamat Asal Sekolah',
            'Q5' => 'Pindahan / Mutasi',
            'R5' => 'Nama Ayah',
            'S5' => 'Pendidikan Ayah',
            'T5' => 'Pekerjaan Ayah',
            'U5' => 'Penghasilan Ayah',
            'V5' => 'No. HP Ayah',
            'W5' => 'Nama Ibu',
            'X5' => 'Pendidikan Ibu',
            'Y5' => 'Pekerjaan Ibu',
            'Z5' => 'No. HP Ibu',
            'AA5' => 'Status Pendaftaran',
            'AB5' => 'Catatan Panitia',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerRange = 'A5:AB5';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('007A5E');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(5)->setRowHeight(28);

        // Isi Data
        $row = 6;
        $no = 1;
        foreach ($records as $reg) {
            $pindahanInfo = $reg->pindah_ke_kelas ? "Kelas {$reg->pindah_ke_kelas} (" . ($reg->alasan_pindah ?: '-') . ")" : '-';

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $reg->nomor_pendaftaran);
            $sheet->setCellValue('C' . $row, $reg->created_at->format('d/m/Y'));
            $sheet->setCellValue('D' . $row, $reg->jenjang);
            $sheet->setCellValue('E' . $row, $reg->nama_lengkap);
            $sheet->setCellValue('F' . $row, $reg->nama_panggilan);
            $sheet->setCellValue('G' . $row, $reg->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan');
            $sheet->setCellValue('H' . $row, $reg->tempat_lahir);
            $sheet->setCellValue('I' . $row, $reg->tanggal_lahir ? $reg->tanggal_lahir->format('d/m/Y') : '-');
            $sheet->setCellValue('J' . $row, $reg->usia_daftar ?: '-');
            $sheet->setCellValue('K' . $row, $reg->anak_ke);
            $sheet->setCellValue('L' . $row, $reg->jumlah_saudara);
            $sheet->setCellValue('M' . $row, $reg->alamat_lengkap);
            $sheet->setCellValue('N' . $row, $reg->tinggal_dengan);
            $sheet->setCellValue('O' . $row, $reg->asal_sekolah ?: '-');
            $sheet->setCellValue('P' . $row, $reg->alamat_asal_sekolah ?: '-');
            $sheet->setCellValue('Q' . $row, $pindahanInfo);
            $sheet->setCellValue('R' . $row, $reg->nama_ayah);
            $sheet->setCellValue('S' . $row, $reg->pendidikan_ayah ?: '-');
            $sheet->setCellValue('T' . $row, $reg->pekerjaan_ayah ?: '-');
            $sheet->setCellValue('U' . $row, $reg->penghasilan_ayah ?: '-');
            $sheet->setCellValueExplicit('V' . $row, $reg->no_hp_ayah, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('W' . $row, $reg->nama_ibu);
            $sheet->setCellValue('X' . $row, $reg->pendidikan_ibu ?: '-');
            $sheet->setCellValue('Y' . $row, $reg->pekerjaan_ibu ?: '-');
            $sheet->setCellValueExplicit('Z' . $row, $reg->no_hp_ibu, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('AA' . $row, $reg->status_label);
            $sheet->setCellValue('AB' . $row, $reg->catatan_admin ?: '-');

            // Format baris ganjil/genap
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:AB{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F9FBF9');
            }

            // Align center untuk beberapa kolom
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AA{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // Border Tabel
        $lastRow = max($row - 1, 5);
        $tableRange = 'A5:AB' . $lastRow;
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('CCCCCC');

        // Auto width kolom
        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('AA')->setAutoSize(true);
        $sheet->getColumnDimension('AB')->setAutoSize(true);

        $fileName = 'Data_PPDB_BIM_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
