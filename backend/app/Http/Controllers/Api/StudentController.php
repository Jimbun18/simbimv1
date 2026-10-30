<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentStatusHistory;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Export\MasterDataExportService;
use App\Services\PhotoCompressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function exportExcel(Request $request, MasterDataExportService $exportService): StreamedResponse
    {
        return $exportService->exportStudents($request);
    }
    public function index(Request $request): JsonResponse
    {
        $query = Student::with(['currentClass:id,nama,kode', 'schoolClass:id,nama,kode', 'religion:id,nama', 'transport:id,nama'])
            ->with(['guardians' => function ($q) {
                $q->select('guardians.id', 'guardians.nama_lengkap', 'guardians.no_hp', 'guardians.hubungan')
                  ->withPivot(['hubungan', 'is_kontak_utama']);
            }]);

        $user = $request->user();

        // RBAC Filter: Guru can only see students in classes they teach/homeroom (OQ-05 / PRD §7)
        if ($user->isTeacher() && $user->teacher) {
            $teacherId = $user->teacher->id;
            $classIds = DB::table('classes')->where('wali_kelas_id', $teacherId)->pluck('id')
                ->merge(DB::table('teacher_assignments')->where('teacher_id', $teacherId)->pluck('class_id'))
                ->unique()
                ->toArray();

            $query->whereIn('class_id', $classIds);
        }

        // RBAC Filter: Wali can only see their own children (PRD §7)
        if ($user->isGuardian() && $user->guardian) {
            $query->whereHas('guardians', function ($q) use ($user) {
                $q->where('guardians.id', $user->guardian->id);
            });
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        } elseif ($request->filled('current_class_id')) {
            $query->where('class_id', $request->query('current_class_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($request->query('all') === 'true') {
            $studentList = $query->orderBy('nama_lengkap')->get()->transform(function ($student) use ($user) {
                return $this->formatSensitiveFields($student, $user);
            });
            return response()->json([
                'success' => true,
                'message' => 'Daftar seluruh siswa berhasil diambil.',
                'data' => $studentList,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $studentList->count(),
                    'total' => $studentList->count(),
                ],
            ]);
        }

        $students = $query->orderBy('nama_lengkap')->paginate(min((int) $request->query('per_page', 20), 100));

        $students->getCollection()->transform(function ($student) use ($user) {
            return $this->formatSensitiveFields($student, $user);
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar siswa berhasil diambil.',
            'data' => $students->items(),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:20', 'unique:students,nis'],
            'nisn' => ['nullable', 'string', 'size:10', 'regex:/^\d{10}$/', 'unique:students,nisn'],
            'nik' => ['nullable', 'string', 'size:16', 'regex:/^\d{16}$/', 'unique:students,nik'],
            'no_kk' => ['nullable', 'string', 'size:16', 'regex:/^\d{16}$/'],
            'no_akta_lahir' => ['nullable', 'string', 'max:40'],
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nama_panggilan' => ['nullable', 'string', 'max:50'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tgl_lahir' => ['required', 'date', 'before:today'],
            'agama' => ['required', 'exists:ref_religions,id'],
            'kewarganegaraan' => ['sometimes', 'string', 'max:5'],
            'anak_ke' => ['nullable', 'integer', 'min:1'],
            'jumlah_saudara' => ['nullable', 'integer', 'min:0'],
            'bahasa_sehari_hari' => ['nullable', 'string', 'max:50'],
            'alamat_jalan' => ['required', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'desa_kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kab_kota' => ['required', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'size:5'],
            'tinggal_bersama' => ['nullable', 'in:orang_tua,wali,asrama,lainnya'],
            'transportasi' => ['nullable', 'exists:ref_transport,id'],
            'jarak_ke_sekolah_km' => ['nullable', 'numeric', 'min:0'],
            'waktu_tempuh_menit' => ['nullable', 'integer', 'min:0'],
            'no_hp_siswa' => ['nullable', 'string', 'max:16'],
            'email' => ['nullable', 'email', 'max:150'],
            'tinggi_cm' => ['nullable', 'numeric', 'min:50', 'max:250'],
            'berat_kg' => ['nullable', 'numeric', 'min:10', 'max:200'],
            'lingkar_kepala' => ['nullable', 'numeric', 'min:20', 'max:80'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O,tidak_tahu'],
            'riwayat_penyakit' => ['nullable', 'string'],
            'alergi' => ['nullable', 'string'],
            'kebutuhan_khusus' => ['nullable', 'string'],
            'asal_sekolah_tk' => ['nullable', 'string', 'max:150'],
            'dusun' => ['nullable', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'skhun' => ['nullable', 'string', 'max:50'],
            'penerima_kps' => ['nullable', 'string', 'max:10'],
            'no_kps' => ['nullable', 'string', 'max:50'],
            'no_peserta_un' => ['nullable', 'string', 'max:50'],
            'no_seri_ijazah' => ['nullable', 'string', 'max:50'],
            'penerima_kip' => ['nullable', 'string', 'max:10'],
            'nomor_kip' => ['nullable', 'string', 'max:50'],
            'nama_di_kip' => ['nullable', 'string', 'max:100'],
            'nomor_kks' => ['nullable', 'string', 'max:50'],
            'bank' => ['nullable', 'string', 'max:50'],
            'nomor_rekening_bank' => ['nullable', 'string', 'max:50'],
            'rekening_atas_nama' => ['nullable', 'string', 'max:100'],
            'layak_pip' => ['nullable', 'string', 'max:20'],
            'alasan_layak_pip' => ['nullable', 'string'],
            'nama_ayah' => ['nullable', 'string', 'max:150'],
            'nik_ayah' => ['nullable', 'string', 'max:20'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:100'],
            'no_hp_ayah' => ['nullable', 'string', 'max:20'],
            'nama_ibu' => ['nullable', 'string', 'max:150'],
            'nik_ibu' => ['nullable', 'string', 'max:20'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:100'],
            'no_hp_ibu' => ['nullable', 'string', 'max:20'],
            'nama_wali' => ['nullable', 'string', 'max:150'],
            'nik_wali' => ['nullable', 'string', 'max:20'],
            'pekerjaan_wali' => ['nullable', 'string', 'max:100'],
            'no_hp_wali' => ['nullable', 'string', 'max:20'],
            'jenis_pendaftaran' => ['nullable', 'in:siswa_baru,pindahan,baru'],
            'tgl_masuk' => ['required', 'date'],
            'tahun_masuk' => ['required', 'integer', 'digits:4'],
            'kelas_saat_masuk' => ['nullable', 'integer', 'between:1,6'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'current_class_id' => ['nullable', 'exists:classes,id'],
            'status' => ['sometimes', 'in:aktif,lulus,mutasi_keluar,drop_out,meninggal'],
            'foto' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'required' => ':attribute wajib diisi.',
            'unique' => ':attribute sudah terdaftar di sistem. Silakan periksa kembali atau gunakan data lain.',
            'date' => ':attribute harus berupa format tanggal yang valid.',
            'before' => ':attribute harus sebelum hari ini.',
            'mimes' => ':attribute harus berupa file foto berformat JPG, JPEG, PNG, atau WebP.',
            'max.file' => 'Ukuran :attribute tidak boleh lebih dari :max KB.',
            'max.string' => ':attribute tidak boleh lebih dari :max karakter.',
            'size' => ':attribute harus tepat :size digit.',
            'regex' => 'Format :attribute tidak valid.',
            'in' => 'Pilihan :attribute tidak valid.',
            'exists' => ':attribute yang dipilih tidak terdaftar di sistem.',
        ], [
            'nis' => 'Nomor Induk Siswa (NIS)',
            'nisn' => 'NISN',
            'nik' => 'NIK Siswa',
            'nama_lengkap' => 'Nama Lengkap Siswa',
            'nama_panggilan' => 'Nama Panggilan',
            'jenis_kelamin' => 'Jenis Kelamin',
            'tempat_lahir' => 'Tempat Lahir',
            'tgl_lahir' => 'Tanggal Lahir',
            'agama' => 'Agama',
            'alamat_jalan' => 'Alamat Domisili',
            'kab_kota' => 'Kabupaten / Kota',
            'tgl_masuk' => 'Masa Bergabung / Tgl Masuk',
            'tahun_masuk' => 'Tahun Masuk',
            'current_class_id' => 'Rombel Kelas',
            'class_id' => 'Rombel Kelas',
            'status' => 'Status Kesiswaan',
            'foto' => 'File Pas Foto',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto_path'] = app(PhotoCompressionService::class)->compressAndStore(
                $request->file('foto'),
                'students'
            );
        }
        unset($validated['foto']);

        $classId = $validated['class_id'] ?? $validated['current_class_id'] ?? null;
        $validated['class_id'] = $classId;
        unset($validated['current_class_id']);

        // Separate guardian fields before Student::create
        $guardianFields = ['nama_ayah', 'nik_ayah', 'pekerjaan_ayah', 'no_hp_ayah', 'nama_ibu', 'nik_ibu', 'pekerjaan_ibu', 'no_hp_ibu', 'nama_wali', 'nik_wali', 'pekerjaan_wali', 'no_hp_wali'];
        $studentData = array_diff_key($validated, array_flip($guardianFields));

        $studentData['status'] = $studentData['status'] ?? 'aktif';
        $studentData['created_by'] = auth()->id();

        $student = DB::transaction(function () use ($studentData, $classId, $request) {
            $student = Student::create($studentData);

            // Save guardians
            $this->saveStudentGuardians($student, $request);

            // If class is assigned, also record enrollment in active academic year
            if (!empty($classId)) {
                $activeYear = AcademicYear::getActive();
                if ($activeYear) {
                    StudentEnrollment::create([
                        'student_id' => $student->id,
                        'class_id' => $classId,
                        'academic_year_id' => $activeYear->id,
                        'tgl_masuk_kelas' => $studentData['tgl_masuk'],
                        'status_kelas' => 'aktif',
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            return $student;
        });

        ActivityLogService::log(
            aksi: 'create',
            modul: 'students',
            targetType: Student::class,
            targetId: $student->id,
            dataSesudah: $student->toArray(),
            keterangan: "Menambahkan data siswa: {$student->nama_lengkap} (NIS: {$student->nis})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil ditambahkan.',
            'data' => $student->load(['currentClass', 'schoolClass', 'religion', 'transport']),
        ], 201);
    }

    public function show(Request $request, Student $student): JsonResponse
    {
        $student->load([
            'currentClass',
            'schoolClass',
            'religion',
            'transport',
            'guardians',
            'enrollments.schoolClass',
            'enrollments.academicYear',
            'statusHistories',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail data siswa berhasil diambil.',
            'data' => $this->formatSensitiveFields($student, $request->user()),
        ]);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'nis' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('students', 'nis')->ignore($student->id)],
            'nisn' => ['nullable', 'string', 'size:10', 'regex:/^\d{10}$/', Rule::unique('students', 'nisn')->ignore($student->id)],
            'nik' => ['nullable', 'string', 'size:16', 'regex:/^\d{16}$/', Rule::unique('students', 'nik')->ignore($student->id)],
            'no_kk' => ['nullable', 'string', 'size:16', 'regex:/^\d{16}$/'],
            'no_akta_lahir' => ['nullable', 'string', 'max:40'],
            'nama_lengkap' => ['sometimes', 'required', 'string', 'max:150'],
            'nama_panggilan' => ['nullable', 'string', 'max:50'],
            'jenis_kelamin' => ['sometimes', 'required', 'in:L,P'],
            'tempat_lahir' => ['sometimes', 'required', 'string', 'max:100'],
            'tgl_lahir' => ['sometimes', 'required', 'date', 'before:today'],
            'agama' => ['sometimes', 'required', 'exists:ref_religions,id'],
            'kewarganegaraan' => ['sometimes', 'string', 'max:5'],
            'anak_ke' => ['nullable', 'integer', 'min:1'],
            'jumlah_saudara' => ['nullable', 'integer', 'min:0'],
            'bahasa_sehari_hari' => ['nullable', 'string', 'max:50'],
            'alamat_jalan' => ['sometimes', 'required', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'desa_kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kab_kota' => ['sometimes', 'required', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'size:5'],
            'tinggal_bersama' => ['nullable', 'in:orang_tua,wali,asrama,lainnya'],
            'transportasi' => ['nullable', 'exists:ref_transport,id'],
            'jarak_ke_sekolah_km' => ['nullable', 'numeric', 'min:0'],
            'waktu_tempuh_menit' => ['nullable', 'integer', 'min:0'],
            'no_hp_siswa' => ['nullable', 'string', 'max:16'],
            'email' => ['nullable', 'email', 'max:150'],
            'tinggi_cm' => ['nullable', 'numeric', 'min:50', 'max:250'],
            'berat_kg' => ['nullable', 'numeric', 'min:10', 'max:200'],
            'lingkar_kepala' => ['nullable', 'numeric', 'min:20', 'max:80'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O,tidak_tahu'],
            'riwayat_penyakit' => ['nullable', 'string'],
            'alergi' => ['nullable', 'string'],
            'kebutuhan_khusus' => ['nullable', 'string'],
            'asal_sekolah_tk' => ['nullable', 'string', 'max:150'],
            'dusun' => ['nullable', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'skhun' => ['nullable', 'string', 'max:50'],
            'penerima_kps' => ['nullable', 'string', 'max:10'],
            'no_kps' => ['nullable', 'string', 'max:50'],
            'no_peserta_un' => ['nullable', 'string', 'max:50'],
            'no_seri_ijazah' => ['nullable', 'string', 'max:50'],
            'penerima_kip' => ['nullable', 'string', 'max:10'],
            'nomor_kip' => ['nullable', 'string', 'max:50'],
            'nama_di_kip' => ['nullable', 'string', 'max:100'],
            'nomor_kks' => ['nullable', 'string', 'max:50'],
            'bank' => ['nullable', 'string', 'max:50'],
            'nomor_rekening_bank' => ['nullable', 'string', 'max:50'],
            'rekening_atas_nama' => ['nullable', 'string', 'max:100'],
            'layak_pip' => ['nullable', 'string', 'max:20'],
            'alasan_layak_pip' => ['nullable', 'string'],
            'nama_ayah' => ['nullable', 'string', 'max:150'],
            'nik_ayah' => ['nullable', 'string', 'max:20'],
            'pekerjaan_ayah' => ['nullable', 'string', 'max:100'],
            'no_hp_ayah' => ['nullable', 'string', 'max:20'],
            'nama_ibu' => ['nullable', 'string', 'max:150'],
            'nik_ibu' => ['nullable', 'string', 'max:20'],
            'pekerjaan_ibu' => ['nullable', 'string', 'max:100'],
            'no_hp_ibu' => ['nullable', 'string', 'max:20'],
            'nama_wali' => ['nullable', 'string', 'max:150'],
            'nik_wali' => ['nullable', 'string', 'max:20'],
            'pekerjaan_wali' => ['nullable', 'string', 'max:100'],
            'no_hp_wali' => ['nullable', 'string', 'max:20'],
            'jenis_pendaftaran' => ['nullable', 'in:siswa_baru,pindahan,baru'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'current_class_id' => ['nullable', 'exists:classes,id'],
            'tgl_masuk' => ['sometimes', 'required', 'date'],
            'tahun_masuk' => ['nullable', 'integer'],
            'status' => ['sometimes', 'in:aktif,lulus,mutasi_keluar,drop_out,meninggal'],
            'foto' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'required' => ':attribute wajib diisi.',
            'unique' => ':attribute sudah terdaftar di sistem. Silakan periksa kembali atau gunakan data lain.',
            'date' => ':attribute harus berupa format tanggal yang valid.',
            'before' => ':attribute harus sebelum hari ini.',
            'mimes' => ':attribute harus berupa file foto berformat JPG, JPEG, PNG, atau WebP.',
            'max.file' => 'Ukuran :attribute tidak boleh lebih dari :max KB.',
            'max.string' => ':attribute tidak boleh lebih dari :max karakter.',
            'size' => ':attribute harus tepat :size digit.',
            'regex' => 'Format :attribute tidak valid.',
            'in' => 'Pilihan :attribute tidak valid.',
            'exists' => ':attribute yang dipilih tidak terdaftar di sistem.',
        ], [
            'nis' => 'Nomor Induk Siswa (NIS)',
            'nisn' => 'NISN',
            'nik' => 'NIK Siswa',
            'nama_lengkap' => 'Nama Lengkap Siswa',
            'nama_panggilan' => 'Nama Panggilan',
            'jenis_kelamin' => 'Jenis Kelamin',
            'tempat_lahir' => 'Tempat Lahir',
            'tgl_lahir' => 'Tanggal Lahir',
            'agama' => 'Agama',
            'alamat_jalan' => 'Alamat Domisili',
            'kab_kota' => 'Kabupaten / Kota',
            'tgl_masuk' => 'Masa Bergabung / Tgl Masuk',
            'tahun_masuk' => 'Tahun Masuk',
            'current_class_id' => 'Rombel Kelas',
            'class_id' => 'Rombel Kelas',
            'status' => 'Status Kesiswaan',
            'foto' => 'File Pas Foto',
        ]);

        if ($request->boolean('hapus_foto') || $request->boolean('remove_photo')) {
            if ($student->foto_path && Storage::disk('public')->exists($student->foto_path)) {
                Storage::disk('public')->delete($student->foto_path);
            }
            $validated['foto_path'] = null;
        } elseif ($request->hasFile('foto')) {
            if ($student->foto_path && Storage::disk('public')->exists($student->foto_path)) {
                Storage::disk('public')->delete($student->foto_path);
            }
            $validated['foto_path'] = app(PhotoCompressionService::class)->compressAndStore(
                $request->file('foto'),
                'students'
            );
        }
        unset($validated['foto'], $validated['hapus_foto'], $validated['remove_photo']);

        if (array_key_exists('current_class_id', $validated)) {
            $validated['class_id'] = $validated['current_class_id'];
            unset($validated['current_class_id']);
        }

        $guardianFields = ['nama_ayah', 'nik_ayah', 'pekerjaan_ayah', 'no_hp_ayah', 'nama_ibu', 'nik_ibu', 'pekerjaan_ibu', 'no_hp_ibu', 'nama_wali', 'nik_wali', 'pekerjaan_wali', 'no_hp_wali'];
        $studentData = array_diff_key($validated, array_flip($guardianFields));

        $oldData = $student->toArray();
        $studentData['updated_by'] = auth()->id();
        $student->update($studentData);

        // Sync guardians
        $this->saveStudentGuardians($student, $request);

        ActivityLogService::log(
            aksi: 'update',
            modul: 'students',
            targetType: Student::class,
            targetId: $student->id,
            dataSebelum: $oldData,
            dataSesudah: $student->fresh()->toArray(),
            keterangan: "Memperbarui data siswa: {$student->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil diperbarui.',
            'data' => $student->fresh()->load(['currentClass', 'schoolClass', 'religion', 'transport']),
        ]);
    }

    public function deletePhoto(Student $student): JsonResponse
    {
        $oldPhoto = $student->foto_path;
        if ($oldPhoto && Storage::disk('public')->exists($oldPhoto)) {
            Storage::disk('public')->delete($oldPhoto);
        }

        $student->update(['foto_path' => null]);

        ActivityLogService::log(
            aksi: 'update',
            modul: 'students',
            targetType: Student::class,
            targetId: $student->id,
            dataSebelum: ['foto_path' => $oldPhoto],
            dataSesudah: ['foto_path' => null],
            keterangan: "Menghapus foto siswa: {$student->nama_lengkap} (NIS: {$student->nis})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Foto siswa berhasil dihapus.',
            'data' => $student->fresh()->load(['currentClass', 'schoolClass', 'religion', 'transport']),
        ]);
    }

    public function destroy(Student $student): JsonResponse
    {
        if ($student->attendances()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa tidak dapat dihapus karena memiliki riwayat absensi. Ubah status menjadi nonaktif/mutasi.',
                'data' => null,
            ], 422);
        }

        $oldData = $student->toArray();
        $student->update(['status' => 'drop_out']);
        $student->delete();

        ActivityLogService::log(
            aksi: 'delete',
            modul: 'students',
            targetType: Student::class,
            targetId: $student->id,
            dataSebelum: $oldData,
            keterangan: "Menghapus data siswa: {$student->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data siswa berhasil dihapus.',
            'data' => null,
        ]);
    }

    public function syncGuardians(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'guardians' => ['required', 'array', 'min:1'],
            'guardians.*.guardian_id' => ['required', 'exists:guardians,id'],
            'guardians.*.hubungan' => ['required', 'in:ayah,ibu,wali'],
            'guardians.*.is_kontak_utama' => ['required', 'boolean'],
            'guardians.*.urutan' => ['nullable', 'integer'],
        ]);

        // Validate exactly one primary contact (PRD 11.4)
        $primaryCount = collect($validated['guardians'])->where('is_kontak_utama', true)->count();
        if ($primaryCount !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Harus ada tepat satu kontak utama (is_kontak_utama = true) untuk siswa ini.',
                'data' => null,
            ], 422);
        }

        $syncData = [];
        foreach ($validated['guardians'] as $g) {
            $syncData[$g['guardian_id']] = [
                'hubungan' => $g['hubungan'],
                'is_kontak_utama' => $g['is_kontak_utama'],
                'boleh_lihat_keuangan' => $g['boleh_lihat_keuangan'] ?? true,
                'boleh_jemput' => $g['boleh_jemput'] ?? true,
            ];
        }

        $student->guardians()->sync($syncData);

        ActivityLogService::log(
            aksi: 'sync_guardians',
            modul: 'students',
            targetType: Student::class,
            targetId: $student->id,
            keterangan: "Memperbarui relasi wali murid untuk siswa {$student->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Relasi wali murid berhasil diperbarui.',
            'data' => $student->load('guardians'),
        ]);
    }

    public function updateStatus(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'status_baru' => ['required', 'in:aktif,lulus,mutasi_keluar,drop_out,meninggal'],
            'tanggal' => ['required', 'date'],
            'alasan' => ['nullable', 'string', 'max:255'],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'new_class_id' => ['nullable', 'exists:classes,id'],
        ]);

        $statusLama = $student->status;

        $jenisMap = [
            'aktif' => 'aktif_kembali',
            'lulus' => 'lulus',
            'mutasi_keluar' => 'pindah_keluar',
            'drop_out' => 'keluar',
            'meninggal' => 'keluar',
        ];
        $jenis = $jenisMap[$validated['status_baru']] ?? 'keluar';

        DB::transaction(function () use ($student, $validated, $statusLama, $jenis) {
            StudentStatusHistory::create([
                'student_id' => $student->id,
                'jenis' => $jenis,
                'tanggal' => $validated['tanggal'],
                'alasan' => $validated['alasan'] ?? null,
                'no_surat' => $validated['nomor_surat'] ?? null,
                'dicatat_oleh' => auth()->id() ?? 1,
                'created_at' => now(),
            ]);

            $updateData = ['status' => $validated['status_baru']];
            $newClass = $validated['new_class_id'] ?? $validated['class_id'] ?? null;
            if ($newClass) {
                $updateData['class_id'] = $newClass;
            } elseif (in_array($validated['status_baru'], ['mutasi_keluar', 'lulus', 'drop_out', 'meninggal'], true)) {
                $updateData['class_id'] = null;
            }

            $student->update($updateData);
        });

        ActivityLogService::log(
            aksi: 'update_status',
            modul: 'students',
            targetType: Student::class,
            targetId: $student->id,
            keterangan: "Perubahan status siswa {$student->nama_lengkap} dari {$statusLama} menjadi {$validated['status_baru']}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Status siswa berhasil diperbarui.',
            'data' => $student->fresh()->load(['currentClass', 'statusHistories']),
        ]);
    }

    private function saveStudentGuardians(Student $student, Request $request): void
    {
        $phoneDefault = $student->no_hp_siswa ?: '0812' . rand(10000000, 99999999);

        // Ayah
        if ($request->filled('nama_ayah')) {
            $ayah = $student->guardians()->wherePivot('hubungan', 'ayah')->first();
            if ($ayah) {
                $ayah->update([
                    'nama_lengkap' => $request->input('nama_ayah'),
                    'nik' => $request->input('nik_ayah', $ayah->nik),
                    'pekerjaan' => $request->input('pekerjaan_ayah', $ayah->pekerjaan),
                    'pendidikan_terakhir' => $request->input('pendidikan_ayah', $ayah->pendidikan_terakhir),
                    'penghasilan_bulanan' => $request->input('penghasilan_ayah', $ayah->penghasilan_bulanan),
                    'no_hp' => $request->input('no_hp_ayah', $ayah->no_hp) ?: $phoneDefault,
                    'no_whatsapp' => $request->input('no_hp_ayah', $ayah->no_whatsapp) ?: $phoneDefault,
                ]);
            } else {
                $ayah = \App\Models\Guardian::create([
                    'hubungan' => 'ayah',
                    'nama_lengkap' => $request->input('nama_ayah'),
                    'nik' => $request->input('nik_ayah'),
                    'pekerjaan' => $request->input('pekerjaan_ayah'),
                    'pendidikan_terakhir' => $request->input('pendidikan_ayah'),
                    'penghasilan_bulanan' => $request->input('penghasilan_ayah'),
                    'no_hp' => $request->input('no_hp_ayah') ?: $phoneDefault,
                    'no_whatsapp' => $request->input('no_hp_ayah') ?: $phoneDefault,
                    'alamat_jalan' => $student->alamat_jalan,
                    'kab_kota' => $student->kab_kota,
                ]);
                $student->guardians()->attach($ayah->id, [
                    'hubungan' => 'ayah',
                    'is_kontak_utama' => true,
                    'boleh_lihat_keuangan' => true,
                    'boleh_jemput' => true,
                ]);
            }
        }

        // Ibu
        if ($request->filled('nama_ibu')) {
            $ibu = $student->guardians()->wherePivot('hubungan', 'ibu')->first();
            if ($ibu) {
                $ibu->update([
                    'nama_lengkap' => $request->input('nama_ibu'),
                    'nik' => $request->input('nik_ibu', $ibu->nik),
                    'pekerjaan' => $request->input('pekerjaan_ibu', $ibu->pekerjaan),
                    'pendidikan_terakhir' => $request->input('pendidikan_ibu', $ibu->pendidikan_terakhir),
                    'penghasilan_bulanan' => $request->input('penghasilan_ibu', $ibu->penghasilan_bulanan),
                    'no_hp' => $request->input('no_hp_ibu', $ibu->no_hp) ?: $phoneDefault,
                    'no_whatsapp' => $request->input('no_hp_ibu', $ibu->no_whatsapp) ?: $phoneDefault,
                ]);
            } else {
                $ibu = \App\Models\Guardian::create([
                    'hubungan' => 'ibu',
                    'nama_lengkap' => $request->input('nama_ibu'),
                    'nik' => $request->input('nik_ibu'),
                    'pekerjaan' => $request->input('pekerjaan_ibu'),
                    'pendidikan_terakhir' => $request->input('pendidikan_ibu'),
                    'penghasilan_bulanan' => $request->input('penghasilan_ibu'),
                    'no_hp' => $request->input('no_hp_ibu') ?: $phoneDefault,
                    'no_whatsapp' => $request->input('no_hp_ibu') ?: $phoneDefault,
                    'alamat_jalan' => $student->alamat_jalan,
                    'kab_kota' => $student->kab_kota,
                ]);
                $student->guardians()->attach($ibu->id, [
                    'hubungan' => 'ibu',
                    'is_kontak_utama' => !$request->filled('nama_ayah'),
                    'boleh_lihat_keuangan' => true,
                    'boleh_jemput' => true,
                ]);
            }
        }

        // Wali
        if ($request->filled('nama_wali')) {
            $wali = $student->guardians()->wherePivot('hubungan', 'wali')->first();
            if ($wali) {
                $wali->update([
                    'nama_lengkap' => $request->input('nama_wali'),
                    'nik' => $request->input('nik_wali', $wali->nik),
                    'pekerjaan' => $request->input('pekerjaan_wali', $wali->pekerjaan),
                    'pendidikan_terakhir' => $request->input('pendidikan_wali', $wali->pendidikan_terakhir),
                    'penghasilan_bulanan' => $request->input('penghasilan_wali', $wali->penghasilan_bulanan),
                    'no_hp' => $request->input('no_hp_wali', $wali->no_hp) ?: $phoneDefault,
                    'no_whatsapp' => $request->input('no_hp_wali', $wali->no_whatsapp) ?: $phoneDefault,
                ]);
            } else {
                $wali = \App\Models\Guardian::create([
                    'hubungan' => 'wali',
                    'nama_lengkap' => $request->input('nama_wali'),
                    'nik' => $request->input('nik_wali'),
                    'pekerjaan' => $request->input('pekerjaan_wali'),
                    'pendidikan_terakhir' => $request->input('pendidikan_wali'),
                    'penghasilan_bulanan' => $request->input('penghasilan_wali'),
                    'no_hp' => $request->input('no_hp_wali') ?: $phoneDefault,
                    'no_whatsapp' => $request->input('no_hp_wali') ?: $phoneDefault,
                    'alamat_jalan' => $student->alamat_jalan,
                    'kab_kota' => $student->kab_kota,
                ]);
                $student->guardians()->attach($wali->id, [
                    'hubungan' => 'wali',
                    'is_kontak_utama' => (!$request->filled('nama_ayah') && !$request->filled('nama_ibu')),
                    'boleh_lihat_keuangan' => true,
                    'boleh_jemput' => true,
                ]);
            }
        }
    }

    private function formatSensitiveFields(Student $student, User $currentUser): Student
    {
        $isAdmin = $currentUser->isAdmin();
        $isGuardian = $currentUser->isGuardian() && $student->guardians->contains('user_id', $currentUser->id);

        if (!$isAdmin && !$isGuardian) {
            // Mask sensitive health & parent NIK data
            $student->riwayat_penyakit = null;
            $student->alergi = null;
            $student->kebutuhan_khusus = null;
            if ($student->nik) {
                $student->nik = substr($student->nik, 0, 6) . '******' . substr($student->nik, -4);
            }
            if ($student->no_kk) {
                $student->no_kk = substr($student->no_kk, 0, 6) . '******' . substr($student->no_kk, -4);
            }
        }

        return $student;
    }
}
