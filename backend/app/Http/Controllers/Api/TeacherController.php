<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\TeacherDocument;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Export\MasterDataExportService;
use App\Services\PhotoCompressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function exportExcel(Request $request, MasterDataExportService $exportService): StreamedResponse
    {
        return $exportService->exportTeachers($request);
    }
    public function index(Request $request): JsonResponse
    {
        $query = Teacher::with([
            'religion',
            'education',
            'user:id,username,email,status',
            'homeroomClasses:id,wali_kelas_id,nama,tingkat,kode',
            'assignments.subject:id,nama,kode',
            'assignments.class:id,nama,tingkat,kode',
        ]);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('nuptk', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        if ($request->filled('jenis_ptk')) {
            $query->where('jenis_ptk', $request->query('jenis_ptk'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $teachers = $query->orderBy('nama_lengkap')->paginate(min((int) $request->query('per_page', 20), 100));

        $user = $request->user();
        $teachers->getCollection()->transform(function ($teacher) use ($user) {
            return $this->formatSensitiveFields($teacher, $user);
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar guru berhasil diambil.',
            'data' => $teachers->items(),
            'meta' => [
                'current_page' => $teachers->currentPage(),
                'last_page' => $teachers->lastPage(),
                'per_page' => $teachers->perPage(),
                'total' => $teachers->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->has('create_user_account')) {
            $val = $request->input('create_user_account');
            $request->merge([
                'create_user_account' => filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ($val === '1' || $val === 1 || $val === 'true'),
            ]);
        }

        $validated = $request->validate([
            'nip' => ['nullable', 'string', 'max:18', 'unique:teachers,nip'],
            'nuptk' => ['nullable', 'string', 'size:16', 'unique:teachers,nuptk'],
            'nik' => ['required', 'string', 'size:16', 'regex:/^\d{16}$/', 'unique:teachers,nik'],
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'gelar_depan' => ['nullable', 'string', 'max:30'],
            'gelar_belakang' => ['nullable', 'string', 'max:30'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tgl_lahir' => ['required', 'date', 'before:today'],
            'agama' => ['nullable', 'exists:ref_religions,id'],
            'status_pernikahan' => ['nullable', 'in:belum_menikah,menikah,cerai'],
            'alamat_jalan' => ['required', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'desa_kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kab_kota' => ['required', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'size:5'],
            'no_hp' => ['required', 'string', 'max:16'],
            'email' => ['nullable', 'email', 'max:150'],
            'jenis_ptk' => ['required', 'in:guru_kelas,guru_mapel,guru_agama,guru_olahraga,kepala_sekolah'],
            'status_kepegawaian' => ['required', 'in:pns,pppk,gty,gtt,honorer'],
            'tgl_mulai_tugas' => ['required', 'date'],
            'tgl_akhir_tugas' => ['nullable', 'date', 'after_or_equal:tgl_mulai_tugas'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'tugas_tambahan' => ['nullable', 'string', 'max:150'],
            'jam_mengajar_per_minggu' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:aktif,cuti,nonaktif,pensiun'],
            'pendidikan_terakhir' => ['nullable', 'exists:ref_education,id'],
            'jurusan_prodi' => ['nullable', 'string', 'max:100'],
            'institusi' => ['nullable', 'string', 'max:150'],
            'tahun_lulus' => ['nullable', 'integer', 'digits:4'],
            'status_sertifikasi' => ['nullable', 'in:sudah,belum'],
            'no_sertifikat_pendidik' => ['nullable', 'string', 'max:50'],
            'tahun_sertifikasi' => ['nullable', 'integer', 'digits:4'],
            'npwp' => ['nullable', 'string', 'max:20'],
            'nama_bank' => ['nullable', 'string', 'max:50'],
            'no_rekening' => ['nullable', 'string', 'max:30'],
            'atas_nama' => ['nullable', 'string', 'max:150'],
            'create_user_account' => ['sometimes', 'boolean'],
            'foto' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'required' => ':attribute wajib diisi.',
            'unique' => ':attribute sudah terdaftar di sistem. Silakan periksa kembali atau gunakan data lain.',
            'date' => ':attribute harus berupa format tanggal yang valid.',
            'before' => ':attribute harus sebelum hari ini.',
            'after_or_equal' => ':attribute harus sama atau sesudah tanggal mulai tugas.',
            'mimes' => ':attribute harus berupa file foto berformat JPG, JPEG, PNG, atau WebP.',
            'max.file' => 'Ukuran :attribute tidak boleh lebih dari :max KB.',
            'max.string' => ':attribute tidak boleh lebih dari :max karakter.',
            'size' => ':attribute harus tepat :size digit.',
            'regex' => 'Format :attribute tidak valid.',
            'boolean' => ':attribute harus bernilai Ya atau Tidak.',
            'in' => 'Pilihan :attribute tidak valid.',
        ], [
            'nik' => 'Nomor Induk Kependudukan (NIK)',
            'nip' => 'NIP / NUPTK',
            'nuptk' => 'NUPTK',
            'nama_lengkap' => 'Nama Lengkap Guru',
            'no_hp' => 'No. WhatsApp / HP',
            'email' => 'Alamat Email',
            'jenis_kelamin' => 'Jenis Kelamin',
            'tempat_lahir' => 'Tempat Lahir',
            'tgl_lahir' => 'Tanggal Lahir',
            'agama' => 'Agama',
            'jenis_ptk' => 'Jenis PTK',
            'status_kepegawaian' => 'Status Kepegawaian',
            'tgl_mulai_tugas' => 'Masa Bergabung / Mulai Tugas',
            'alamat_jalan' => 'Alamat Domisili',
            'kab_kota' => 'Kabupaten / Kota',
            'create_user_account' => 'Pilihan Buat Akun Pengguna',
            'foto' => 'File Pas Foto',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto_path'] = app(PhotoCompressionService::class)->compressAndStore(
                $request->file('foto'),
                'teachers'
            );
        }
        unset($validated['foto']);

        $teacher = DB::transaction(function () use ($validated, $request) {
            $validated['created_by'] = auth()->id();

            // Optionally create user account for teacher
            if (!empty($validated['create_user_account'])) {
                $roleGuru = Role::where('nama', Role::GURU)->firstOrFail();
                $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $validated['nip'] ?? $validated['nik']));
                
                $user = User::create([
                    'nama' => $validated['nama_lengkap'],
                    'username' => $username,
                    'email' => $validated['email'] ?? null,
                    'no_hp' => $validated['no_hp'],
                    'password' => Hash::make('Password123!'),
                    'role_id' => $roleGuru->id,
                    'status' => 'aktif',
                    'must_change_password' => true,
                ]);

                $validated['user_id'] = $user->id;
            }

            unset($validated['create_user_account']);

            return Teacher::create($validated);
        });

        ActivityLogService::log(
            aksi: 'create',
            modul: 'teachers',
            targetType: Teacher::class,
            targetId: $teacher->id,
            dataSesudah: $teacher->toArray(),
            keterangan: "Menambahkan data guru: {$teacher->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data guru berhasil ditambahkan.',
            'data' => $teacher->load(['religion', 'education', 'user']),
        ], 201);
    }

    public function show(Request $request, Teacher $teacher): JsonResponse
    {
        $teacher->load([
            'religion',
            'education',
            'documents',
            'homeroomClasses',
            'assignments.subject',
            'assignments.class',
            'user:id,username,email,status',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail data guru berhasil diambil.',
            'data' => $this->formatSensitiveFields($teacher, $request->user()),
        ]);
    }

    public function update(Request $request, Teacher $teacher): JsonResponse
    {
        $validated = $request->validate([
            'nip' => ['nullable', 'string', 'max:18', Rule::unique('teachers', 'nip')->ignore($teacher->id)],
            'nuptk' => ['nullable', 'string', 'size:16', Rule::unique('teachers', 'nuptk')->ignore($teacher->id)],
            'nik' => ['sometimes', 'required', 'string', 'size:16', 'regex:/^\d{16}$/', Rule::unique('teachers', 'nik')->ignore($teacher->id)],
            'nama_lengkap' => ['sometimes', 'required', 'string', 'max:150'],
            'gelar_depan' => ['nullable', 'string', 'max:30'],
            'gelar_belakang' => ['nullable', 'string', 'max:30'],
            'jenis_kelamin' => ['sometimes', 'required', 'in:L,P'],
            'tempat_lahir' => ['sometimes', 'required', 'string', 'max:100'],
            'tgl_lahir' => ['sometimes', 'required', 'date', 'before:today'],
            'agama' => ['nullable', 'exists:ref_religions,id'],
            'status_pernikahan' => ['nullable', 'in:belum_menikah,menikah,cerai'],
            'alamat_jalan' => ['sometimes', 'required', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'desa_kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kab_kota' => ['sometimes', 'required', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'size:5'],
            'no_hp' => ['sometimes', 'required', 'string', 'max:16'],
            'email' => ['nullable', 'email', 'max:150'],
            'jenis_ptk' => ['sometimes', 'required', 'in:guru_kelas,guru_mapel,guru_agama,guru_olahraga,kepala_sekolah'],
            'status_kepegawaian' => ['sometimes', 'required', 'in:pns,pppk,gty,gtt,honorer'],
            'tgl_mulai_tugas' => ['sometimes', 'required', 'date'],
            'tgl_akhir_tugas' => ['nullable', 'date'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'tugas_tambahan' => ['nullable', 'string', 'max:150'],
            'jam_mengajar_per_minggu' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:aktif,cuti,nonaktif,pensiun'],
            'pendidikan_terakhir' => ['nullable', 'exists:ref_education,id'],
            'jurusan_prodi' => ['nullable', 'string', 'max:100'],
            'institusi' => ['nullable', 'string', 'max:150'],
            'tahun_lulus' => ['nullable', 'integer', 'digits:4'],
            'status_sertifikasi' => ['nullable', 'in:sudah,belum'],
            'no_sertifikat_pendidik' => ['nullable', 'string', 'max:50'],
            'tahun_sertifikasi' => ['nullable', 'integer', 'digits:4'],
            'npwp' => ['nullable', 'string', 'max:20'],
            'nama_bank' => ['nullable', 'string', 'max:50'],
            'no_rekening' => ['nullable', 'string', 'max:30'],
            'atas_nama' => ['nullable', 'string', 'max:150'],
            'foto' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'required' => ':attribute wajib diisi.',
            'unique' => ':attribute sudah terdaftar di sistem. Silakan periksa kembali atau gunakan data lain.',
            'date' => ':attribute harus berupa format tanggal yang valid.',
            'before' => ':attribute harus sebelum hari ini.',
            'after_or_equal' => ':attribute harus sama atau sesudah tanggal mulai tugas.',
            'mimes' => ':attribute harus berupa file foto berformat JPG, JPEG, PNG, atau WebP.',
            'max.file' => 'Ukuran :attribute tidak boleh lebih dari :max KB.',
            'max.string' => ':attribute tidak boleh lebih dari :max karakter.',
            'size' => ':attribute harus tepat :size digit.',
            'regex' => 'Format :attribute tidak valid.',
            'in' => 'Pilihan :attribute tidak valid.',
        ], [
            'nik' => 'Nomor Induk Kependudukan (NIK)',
            'nip' => 'NIP / NUPTK',
            'nuptk' => 'NUPTK',
            'nama_lengkap' => 'Nama Lengkap Guru',
            'no_hp' => 'No. WhatsApp / HP',
            'email' => 'Alamat Email',
            'jenis_kelamin' => 'Jenis Kelamin',
            'tempat_lahir' => 'Tempat Lahir',
            'tgl_lahir' => 'Tanggal Lahir',
            'agama' => 'Agama',
            'jenis_ptk' => 'Jenis PTK',
            'status_kepegawaian' => 'Status Kepegawaian',
            'tgl_mulai_tugas' => 'Masa Bergabung / Mulai Tugas',
            'alamat_jalan' => 'Alamat Domisili',
            'kab_kota' => 'Kabupaten / Kota',
            'foto' => 'File Pas Foto',
        ]);

        if ($request->boolean('hapus_foto') || $request->boolean('remove_photo')) {
            if ($teacher->foto_path && Storage::disk('public')->exists($teacher->foto_path)) {
                Storage::disk('public')->delete($teacher->foto_path);
            }
            $validated['foto_path'] = null;
            if ($teacher->user) {
                $teacher->user->update(['foto_path' => null]);
            }
        } elseif ($request->hasFile('foto')) {
            if ($teacher->foto_path && Storage::disk('public')->exists($teacher->foto_path)) {
                Storage::disk('public')->delete($teacher->foto_path);
            }
            $fotoPath = app(PhotoCompressionService::class)->compressAndStore(
                $request->file('foto'),
                'teachers'
            );
            $validated['foto_path'] = $fotoPath;
            if ($teacher->user) {
                $teacher->user->update(['foto_path' => $fotoPath]);
            }
        }
        unset($validated['foto'], $validated['hapus_foto'], $validated['remove_photo']);

        $oldData = $teacher->toArray();
        $validated['updated_by'] = auth()->id();
        $teacher->update($validated);

        // Sinkronisasi otomatis ke Akun Pengguna (User)
        if ($teacher->user) {
            $userSync = [];
            if (isset($validated['nama_lengkap'])) {
                $userSync['nama'] = $validated['nama_lengkap'];
            }
            if (array_key_exists('email', $validated)) {
                $userSync['email'] = $validated['email'] ?: null;
            }
            if (isset($validated['no_hp'])) {
                $userSync['no_hp'] = $validated['no_hp'] ?: null;
            }
            if (!empty($userSync)) {
                $teacher->user->update($userSync);
            }
        }

        ActivityLogService::log(
            aksi: 'update',
            modul: 'teachers',
            targetType: Teacher::class,
            targetId: $teacher->id,
            dataSebelum: $oldData,
            dataSesudah: $teacher->fresh()->toArray(),
            keterangan: "Memperbarui data guru: {$teacher->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data guru berhasil diperbarui.',
            'data' => $teacher->fresh()->load(['religion', 'education', 'user']),
        ]);
    }

    public function deletePhoto(Teacher $teacher): JsonResponse
    {
        $oldPhoto = $teacher->foto_path;
        if ($oldPhoto && Storage::disk('public')->exists($oldPhoto)) {
            Storage::disk('public')->delete($oldPhoto);
        }

        $teacher->update(['foto_path' => null]);

        if ($teacher->user) {
            $teacher->user->update(['foto_path' => null]);
        }

        ActivityLogService::log(
            aksi: 'update',
            modul: 'teachers',
            targetType: Teacher::class,
            targetId: $teacher->id,
            dataSebelum: ['foto_path' => $oldPhoto],
            dataSesudah: ['foto_path' => null],
            keterangan: "Menghapus foto guru: {$teacher->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Foto guru berhasil dihapus.',
            'data' => $teacher->fresh()->load(['religion', 'education', 'user']),
        ]);
    }

    public function destroy(Teacher $teacher): JsonResponse
    {
        if ($teacher->homeroomClasses()->exists() || $teacher->assignments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Guru tidak dapat dihapus karena masih menjadi wali kelas atau memiliki penugasan mengajar.',
                'data' => null,
            ], 422);
        }

        $oldData = $teacher->toArray();
        $teacher->update(['status' => 'nonaktif']);
        $teacher->delete();

        ActivityLogService::log(
            aksi: 'delete',
            modul: 'teachers',
            targetType: Teacher::class,
            targetId: $teacher->id,
            dataSebelum: $oldData,
            keterangan: "Menghapus data guru: {$teacher->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data guru berhasil dihapus.',
            'data' => null,
        ]);
    }

    public function uploadDocument(Request $request, Teacher $teacher): JsonResponse
    {
        $validated = $request->validate([
            'jenis' => ['nullable', 'string', 'max:30'],
            'jenis_dokumen' => ['nullable', 'string', 'max:50'],
            'nama_dokumen' => ['nullable', 'string', 'max:150'],
            'nomor_dokumen' => ['nullable', 'string', 'max:100'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // max 5MB
            'tgl_terbit' => ['nullable', 'date'],
            'tgl_kedaluwarsa' => ['nullable', 'date'],
        ]);

        $path = $request->file('file')->store("teachers/{$teacher->id}/documents", 'public');

        $jenis = $validated['jenis'] ?? $validated['jenis_dokumen'] ?? 'lainnya';
        $namaDokumen = $validated['nama_dokumen'] ?? $validated['nomor_dokumen'] ?? $request->file('file')->getClientOriginalName();

        $doc = TeacherDocument::create([
            'teacher_id' => $teacher->id,
            'jenis' => strtolower($jenis),
            'nama_dokumen' => $namaDokumen,
            'file_path' => $path,
            'tgl_terbit' => $validated['tgl_terbit'] ?? null,
            'tgl_kedaluwarsa' => $validated['tgl_kedaluwarsa'] ?? null,
            'created_by' => auth()->id(),
        ]);

        ActivityLogService::log(
            aksi: 'upload_document',
            modul: 'teachers',
            targetType: Teacher::class,
            targetId: $teacher->id,
            keterangan: "Mengunggah dokumen {$doc->nama_dokumen} untuk guru {$teacher->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Dokumen guru berhasil diunggah.',
            'data' => $doc,
        ], 201);
    }

    public function deleteDocument(Teacher $teacher, TeacherDocument $document): JsonResponse
    {
        if ($document->teacher_id !== $teacher->id) {
            return response()->json(['success' => false, 'message' => 'Dokumen tidak ditemukan pada guru ini.'], 404);
        }

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil dihapus.',
            'data' => null,
        ]);
    }

    private function formatSensitiveFields(Teacher $teacher, User $currentUser): Teacher
    {
        $isSelf = $currentUser->id === $teacher->user_id;
        $isAdmin = $currentUser->isAdmin();

        if (!$isAdmin && !$isSelf) {
            // Mask sensitive fields
            $teacher->nik = substr($teacher->nik, 0, 6) . '******' . substr($teacher->nik, -4);
            $teacher->npwp = null;
            $teacher->nama_bank = null;
            $teacher->no_rekening = null;
            $teacher->atas_nama = null;
        }

        return $teacher;
    }
}
