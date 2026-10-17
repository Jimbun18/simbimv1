<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\InAppNotification;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Invoice::with([
            'student:id,nis,nisn,nama_lengkap,class_id,no_hp_siswa,telepon',
            'student.schoolClass:id,nama,tingkat',
            'student.guardians',
            'feeType:id,kode,nama,periode',
            'academicYear:id,nama,status',
            'payments' => fn($q) => $q->where('is_batal', false)->orderBy('id', 'desc'),
        ]);

        // FR-KEU-07: Wali hanya dapat melihat tagihan anak-anaknya sendiri
        if ($user->isGuardian()) {
            $guardian = $user->guardian;
            if (!$guardian) {
                return response()->json(['success' => true, 'data' => [], 'summary' => []]);
            }
            $childIds = $guardian->students()->pluck('students.id');
            $query->whereIn('student_id', $childIds);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->query('student_id'));
        }

        if ($request->filled('class_id')) {
            $query->whereHas('student', fn($q) => $q->where('class_id', $request->query('class_id')));
        }

        if ($request->filled('fee_type_id')) {
            $query->where('fee_type_id', $request->query('fee_type_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->query('academic_year_id'));
        }

        if ($request->filled('periode')) {
            $query->where('periode', $request->query('periode'));
        }

        // Summary stats (Ringan & Cepat tanpa hidrasi seluruh model)
        $summary = [
            'total_tagihan' => (int) (clone $query)->count(),
            'total_nominal' => (float) (clone $query)->sum('jumlah'),
            'total_terbayar' => (float) (clone $query)->sum('jumlah_dibayar'),
            'total_sisa' => (float) (clone $query)->sum('sisa'),
            'belum_bayar' => (int) (clone $query)->where('status', 'belum_bayar')->count(),
            'sebagian' => (int) (clone $query)->where('status', 'sebagian')->count(),
            'lunas' => (int) (clone $query)->where('status', 'lunas')->count(),
            'menunggak' => (int) (clone $query)->where('status', 'menunggak')->count(),
        ];

        if ($request->boolean('all') || $request->query('per_page') === 'all' || $request->query('all') == 1) {
            $invoices = $query->orderBy('jatuh_tempo', 'desc')->orderBy('id', 'desc')->get();
            return response()->json([
                'success' => true,
                'message' => 'Daftar tagihan berhasil diambil.',
                'data' => $invoices,
                'summary' => $summary,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $invoices->count(),
                    'total' => $invoices->count(),
                ],
            ]);
        }

        $perPage = min((int) $request->query('per_page', 25), 1000);
        $invoices = $query->orderBy('jatuh_tempo', 'desc')->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar tagihan berhasil diambil.',
            'data' => $invoices->items(),
            'summary' => $summary,
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'fee_type_id' => ['required', 'exists:fee_types,id'],
            'academic_year_id' => ['sometimes', 'required', 'exists:academic_years,id'],
            'periode' => ['required', 'string', 'max:30'],
            'jumlah' => ['nullable', 'numeric', 'min:0'],
            'jatuh_tempo' => ['required', 'date'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $feeType = FeeType::findOrFail($validated['fee_type_id']);
        $activeYear = AcademicYear::getActive() ?? AcademicYear::latest()->first();
        $academicYearId = $validated['academic_year_id'] ?? ($activeYear?->id ?? 1);
        $nominal = $validated['jumlah'] ?? $feeType->nominal;

        $invoice = Invoice::create([
            'no_tagihan' => Invoice::generateNumber(),
            'student_id' => $validated['student_id'],
            'fee_type_id' => $feeType->id,
            'academic_year_id' => $academicYearId,
            'periode' => $validated['periode'],
            'jumlah' => $nominal,
            'jumlah_dibayar' => 0,
            'sisa' => $nominal,
            'status' => 'belum_bayar',
            'jatuh_tempo' => $validated['jatuh_tempo'],
            'keterangan' => $validated['keterangan'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        ActivityLogService::log(
            aksi: 'create_invoice',
            modul: 'invoices',
            targetType: Invoice::class,
            targetId: $invoice->id,
            dataSesudah: $invoice->toArray(),
            keterangan: "Membuat tagihan {$feeType->nama} ({$invoice->no_tagihan}) untuk siswa ID #{$invoice->student_id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Tagihan berhasil dibuat.',
            'data' => $invoice->load(['student', 'feeType', 'academicYear']),
        ], 201);
    }

    public function generateBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fee_type_id' => ['required', 'exists:fee_types,id'],
            'class_id' => ['nullable', 'exists:classes,id'], // opsional: jika null, seluruh siswa aktif
            'academic_year_id' => ['sometimes', 'required', 'exists:academic_years,id'],
            'periode' => ['required', 'string', 'max:30'],
            'jatuh_tempo' => ['required_without:tgl_jatuh_tempo', 'nullable', 'date'],
            'tgl_jatuh_tempo' => ['required_without:jatuh_tempo', 'nullable', 'date'],
            'nominal_override' => ['nullable', 'numeric', 'min:0'],
        ]);

        $jatuhTempo = $validated['jatuh_tempo'] ?? $validated['tgl_jatuh_tempo'];
        $feeType = FeeType::findOrFail($validated['fee_type_id']);
        $activeYear = AcademicYear::getActive() ?? AcademicYear::latest()->first();
        $academicYearId = $validated['academic_year_id'] ?? ($activeYear?->id ?? 1);
        $nominal = $validated['nominal_override'] ?? $feeType->nominal;
        $user = $request->user();

        $studentsQuery = Student::where('status', 'aktif');
        if (!empty($validated['class_id'])) {
            $studentsQuery->where('class_id', $validated['class_id']);
        }

        $students = $studentsQuery->get();
        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada siswa aktif yang memenuhi kriteria.',
                'data' => null,
            ], 422);
        }

        $createdCount = 0;
        $skippedCount = 0;

        // Hitung sequence awal agar loop batch cepat dan bebas tabrakan
        $prefix = 'INV/' . now()->format('Y/m') . '/';
        $lastInvoice = Invoice::where('no_tagihan', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();
        $currentSeq = 1;
        if ($lastInvoice) {
            $parts = explode('/', $lastInvoice->no_tagihan);
            $currentSeq = (int) end($parts) + 1;
        }

        DB::transaction(function () use ($students, $feeType, $academicYearId, $validated, $nominal, $jatuhTempo, $user, &$createdCount, &$skippedCount, &$currentSeq) {
            foreach ($students as $student) {
                // Cek apakah sudah pernah dibuat tagihan untuk fee_type & periode yang sama
                $exists = Invoice::where('student_id', $student->id)
                    ->where('fee_type_id', $feeType->id)
                    ->where('periode', $validated['periode'])
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                $invoice = Invoice::create([
                    'no_tagihan' => Invoice::generateNumber($currentSeq++),
                    'student_id' => $student->id,
                    'fee_type_id' => $feeType->id,
                    'academic_year_id' => $academicYearId,
                    'periode' => $validated['periode'],
                    'jumlah' => $nominal,
                    'jumlah_dibayar' => 0,
                    'sisa' => $nominal,
                    'status' => 'belum_bayar',
                    'jatuh_tempo' => $jatuhTempo,
                    'created_by' => $user->id,
                ]);

                $createdCount++;

                // Notifikasi ke wali murid
                foreach ($student->guardians as $guardian) {
                    if ($guardian->user_id && $guardian->pivot->boleh_lihat_keuangan) {
                        InAppNotification::send(
                            userId: $guardian->user_id,
                            judul: "Tagihan Baru: {$feeType->nama}",
                            isi: "Tagihan {$feeType->nama} periode {$validated['periode']} untuk {$student->nama_lengkap} sebesar Rp " . number_format($nominal, 0, ',', '.') . " telah diterbitkan.",
                            tipe: 'tagihan',
                            targetUrl: "/parent/children/{$student->id}/invoices"
                        );
                    }
                }
            }
        });

        ActivityLogService::log(
            aksi: 'batch_generate_invoices',
            modul: 'invoices',
            targetType: Invoice::class,
            keterangan: "Generate tagihan massal {$feeType->nama} ({$validated['periode']}): {$createdCount} dibuat, {$skippedCount} dilewati."
        );

        return response()->json([
            'success' => true,
            'message' => "Tagihan massal berhasil diproses. Berhasil: {$createdCount}, Dilewati: {$skippedCount}.",
            'data' => [
                'created' => $createdCount,
                'skipped' => $skippedCount,
                'total_students' => $students->count(),
            ],
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $invoice = Invoice::with([
            'student.schoolClass',
            'student.guardians',
            'feeType',
            'academicYear',
            'payments.recorder:id,nama',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail tagihan berhasil diambil.',
            'data' => $invoice,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->payments()->where('is_batal', false)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan yang sudah memiliki riwayat pembayaran tidak dapat diubah jumlahnya secara langsung.',
                'data' => null,
            ], 422);
        }

        $validated = $request->validate([
            'jumlah' => ['sometimes', 'required', 'numeric', 'min:0'],
            'jatuh_tempo' => ['sometimes', 'required', 'date'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $oldData = $invoice->toArray();

        if (isset($validated['jumlah'])) {
            $validated['sisa'] = $validated['jumlah'] - $invoice->jumlah_dibayar;
        }

        $validated['updated_by'] = $request->user()->id;
        $invoice->update($validated);

        ActivityLogService::log(
            aksi: 'update_invoice',
            modul: 'invoices',
            targetType: Invoice::class,
            targetId: $invoice->id,
            dataSebelum: $oldData,
            dataSesudah: $invoice->fresh()->toArray(),
            keterangan: "Memperbarui tagihan {$invoice->no_tagihan}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Tagihan berhasil diperbarui.',
            'data' => $invoice->fresh()->load(['student', 'feeType']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->payments()->where('is_batal', false)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan yang sudah memiliki pembayaran tidak dapat dihapus. Batalkan pembayarannya terlebih dahulu jika perlu.',
                'data' => null,
            ], 422);
        }

        $oldData = $invoice->toArray();
        $invoice->delete();

        ActivityLogService::log(
            aksi: 'delete_invoice',
            modul: 'invoices',
            targetType: Invoice::class,
            targetId: $id,
            dataSebelum: $oldData,
            keterangan: "Menghapus tagihan {$invoice->no_tagihan}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Tagihan berhasil dihapus.',
            'data' => null,
        ]);
    }
}
