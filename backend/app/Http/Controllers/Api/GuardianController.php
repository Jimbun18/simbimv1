<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class GuardianController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Guardian::with([
            'religion',
            'education',
            'occupation',
            'incomeRange',
            'user:id,username,email,status',
            'students:id,nama_lengkap,nis,class_id',
            'students.schoolClass:id,nama'
        ]);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%")
                  ->orWhere('no_whatsapp', 'like', "%{$search}%")
                  ->orWhereHas('students', function ($sq) use ($search) {
                      $sq->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('hubungan')) {
            $query->where('hubungan', $request->query('hubungan'));
        }

        $perPage = (int) $request->query('per_page', 20);
        if ($perPage <= 0 || $request->query('all') === 'true') {
            $guardiansList = $query->orderBy('nama_lengkap')->get();
            $items = $guardiansList;
            $meta = [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $guardiansList->count(),
                'total' => $guardiansList->count(),
            ];
        } else {
            $guardians = $query->orderBy('nama_lengkap')->paginate(min($perPage, 250));
            $items = $guardians->items();
            $meta = [
                'current_page' => $guardians->currentPage(),
                'last_page' => $guardians->lastPage(),
                'per_page' => $guardians->perPage(),
                'total' => $guardians->total(),
            ];
        }

        $totalGuardians = Guardian::count();
        $totalActiveAccounts = Guardian::whereNotNull('user_id')->count();
        $totalConnectedStudents = DB::table('student_guardian')->distinct('student_id')->count('student_id');

        return response()->json([
            'success' => true,
            'message' => 'Daftar wali murid berhasil diambil.',
            'data' => $items,
            'meta' => $meta,
            'stats' => [
                'total_guardians' => $totalGuardians,
                'active_accounts' => $totalActiveAccounts,
                'connected_students' => $totalConnectedStudents,
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hubungan' => ['required', 'in:ayah,ibu,wali'],
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nik' => ['nullable', 'string', 'size:16', 'regex:/^\d{16}$/'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tgl_lahir' => ['nullable', 'date', 'before:today'],
            'status_hidup' => ['nullable', 'in:hidup,meninggal'],
            'agama' => ['nullable', 'exists:ref_religions,id'],
            'pendidikan_terakhir' => ['nullable', 'exists:ref_education,id'],
            'pekerjaan' => ['nullable', 'exists:ref_occupations,id'],
            'penghasilan_bulanan' => ['nullable', 'exists:ref_income_ranges,id'],
            'no_hp' => ['required', 'string', 'max:16'],
            'no_whatsapp' => ['nullable', 'string', 'max:16'],
            'email' => ['nullable', 'email', 'max:150'],
            'alamat_jalan' => ['nullable', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'desa_kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kab_kota' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'size:5'],
            'create_user_account' => ['sometimes', 'boolean'],
        ]);

        $guardian = DB::transaction(function () use ($validated) {
            $validated['created_by'] = auth()->id();

            // Create portal user account if requested (FR-AUTH-07)
            if (!empty($validated['create_user_account'])) {
                $roleWali = Role::where('nama', Role::WALI)->firstOrFail();
                $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $validated['no_whatsapp'] ?? $validated['no_hp']));
                $username = 'wali_' . $baseUsername;

                $user = User::firstOrCreate(
                    ['username' => $username],
                    [
                        'nama' => $validated['nama_lengkap'],
                        'email' => $validated['email'] ?? null,
                        'no_hp' => $validated['no_hp'],
                        'password' => Hash::make('Password123!'),
                        'role_id' => $roleWali->id,
                        'status' => 'aktif',
                        'must_change_password' => true,
                    ]
                );

                $validated['user_id'] = $user->id;
            }

            unset($validated['create_user_account']);

            return Guardian::create($validated);
        });

        ActivityLogService::log(
            aksi: 'create',
            modul: 'guardians',
            targetType: Guardian::class,
            targetId: $guardian->id,
            dataSesudah: $guardian->toArray(),
            keterangan: "Menambahkan data wali murid: {$guardian->nama_lengkap} ({$guardian->hubungan})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data wali murid berhasil ditambahkan.',
            'data' => $guardian->load(['religion', 'education', 'occupation', 'incomeRange', 'user']),
        ], 201);
    }

    public function show(Guardian $guardian): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail wali murid berhasil diambil.',
            'data' => $guardian->load(['religion', 'education', 'occupation', 'incomeRange', 'students', 'user']),
        ]);
    }

    public function update(Request $request, Guardian $guardian): JsonResponse
    {
        $validated = $request->validate([
            'hubungan' => ['sometimes', 'required', 'in:ayah,ibu,wali'],
            'nama_lengkap' => ['sometimes', 'required', 'string', 'max:150'],
            'nik' => ['nullable', 'string', 'size:16', 'regex:/^\d{16}$/'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tgl_lahir' => ['nullable', 'date', 'before:today'],
            'status_hidup' => ['nullable', 'in:hidup,meninggal'],
            'agama' => ['nullable', 'exists:ref_religions,id'],
            'pendidikan_terakhir' => ['nullable', 'exists:ref_education,id'],
            'pekerjaan' => ['nullable', 'exists:ref_occupations,id'],
            'penghasilan_bulanan' => ['nullable', 'exists:ref_income_ranges,id'],
            'no_hp' => ['sometimes', 'required', 'string', 'max:16'],
            'no_whatsapp' => ['nullable', 'string', 'max:16'],
            'email' => ['nullable', 'email', 'max:150'],
            'alamat_jalan' => ['nullable', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'desa_kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kab_kota' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'size:5'],
        ]);

        $oldData = $guardian->toArray();
        $validated['updated_by'] = auth()->id();
        $guardian->update($validated);

        // Sinkronisasi otomatis ke Akun Pengguna (User)
        if ($guardian->user) {
            $userSync = [];
            if (isset($validated['nama_lengkap'])) {
                $userSync['nama'] = $validated['nama_lengkap'];
            }
            if (array_key_exists('email', $validated)) {
                $userSync['email'] = $validated['email'] ?: null;
            }
            $phone = $validated['no_whatsapp'] ?? ($validated['no_hp'] ?? null);
            if ($phone !== null) {
                $userSync['no_hp'] = $phone ?: null;
            }
            if (!empty($userSync)) {
                $guardian->user->update($userSync);
            }
        }

        ActivityLogService::log(
            aksi: 'update',
            modul: 'guardians',
            targetType: Guardian::class,
            targetId: $guardian->id,
            dataSebelum: $oldData,
            dataSesudah: $guardian->fresh()->toArray(),
            keterangan: "Memperbarui data wali murid: {$guardian->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data wali murid berhasil diperbarui.',
            'data' => $guardian->fresh()->load(['religion', 'education', 'occupation', 'incomeRange']),
        ]);
    }

    public function destroy(Guardian $guardian): JsonResponse
    {
        if ($guardian->students()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Wali murid tidak dapat dihapus karena masih terhubung dengan data siswa.',
                'data' => null,
            ], 422);
        }

        $oldData = $guardian->toArray();
        $guardian->delete();

        ActivityLogService::log(
            aksi: 'delete',
            modul: 'guardians',
            targetType: Guardian::class,
            targetId: $guardian->id,
            dataSebelum: $oldData,
            keterangan: "Menghapus data wali murid: {$guardian->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data wali murid berhasil dihapus.',
            'data' => null,
        ]);
    }

    public function syncStudents(Request $request, Guardian $guardian): JsonResponse
    {
        $validated = $request->validate([
            'student_ids' => ['present', 'array'],
            'student_ids.*' => ['exists:students,id'],
        ]);

        $syncData = [];
        foreach ($validated['student_ids'] as $studentId) {
            $syncData[$studentId] = [
                'hubungan' => $guardian->hubungan,
                'is_kontak_utama' => true,
                'boleh_lihat_keuangan' => true,
                'boleh_jemput' => true,
            ];
        }

        $guardian->students()->sync($syncData);

        ActivityLogService::log(
            aksi: 'sync_students',
            modul: 'guardians',
            targetType: Guardian::class,
            targetId: $guardian->id,
            keterangan: "Memperbarui daftar anak asuh untuk wali murid: {$guardian->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar anak tanggungan berhasil diperbarui.',
            'data' => $guardian->fresh()->load(['students:id,nama_lengkap,nis,class_id', 'students.schoolClass:id,nama']),
        ]);
    }

    public function createAccount(Guardian $guardian): JsonResponse
    {
        if ($guardian->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Wali murid ini sudah memiliki akun login.',
            ], 422);
        }

        $roleWali = Role::where('nama', Role::WALI)->firstOrFail();
        $cleanPhone = preg_replace('/[^0-9]/', '', $guardian->no_whatsapp ?: $guardian->no_hp);
        $username = 'wali_' . substr($cleanPhone, -8);

        // Ensure username unique
        $candidateUsername = $username;
        $counter = 1;
        while (User::where('username', $candidateUsername)->exists()) {
            $candidateUsername = $username . '_' . $counter++;
        }

        $user = User::create([
            'nama' => $guardian->nama_lengkap,
            'username' => $candidateUsername,
            'email' => $guardian->email ?: null,
            'no_hp' => $guardian->no_hp,
            'password' => Hash::make('Password123!'),
            'role_id' => $roleWali->id,
            'status' => 'aktif',
            'must_change_password' => true,
        ]);

        $guardian->update(['user_id' => $user->id]);

        ActivityLogService::log(
            aksi: 'create_account',
            modul: 'guardians',
            targetType: Guardian::class,
            targetId: $guardian->id,
            keterangan: "Membuatkan akun login portal wali murid untuk {$guardian->nama_lengkap} (@{$candidateUsername})."
        );

        return response()->json([
            'success' => true,
            'message' => "Akun login berhasil dibuat. Username: @{$candidateUsername}, Password default: Password123!",
            'data' => $guardian->fresh()->load('user'),
        ]);
    }
}
