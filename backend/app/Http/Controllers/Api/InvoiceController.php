<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Models\InAppNotification;
use App\Models\Invoice;
use App\Models\Payment;
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

    public function studentRecap(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Student::with([
            'schoolClass:id,nama,tingkat',
            'guardians' => function ($q) {
                $q->select('guardians.id', 'guardians.nama_lengkap', 'guardians.no_hp', 'guardians.pekerjaan')
                  ->withPivot(['hubungan', 'is_kontak_utama']);
            },
        ])
        ->withCount('invoices')
        ->withSum('invoices as total_tagihan', 'jumlah')
        ->withSum('invoices as total_dibayar', 'jumlah_dibayar')
        ->withSum('invoices as total_sisa', 'sisa')
        ->withCount(['invoices as tagihan_lunas_count' => function ($q) {
            $q->where('status', 'lunas');
        }])
        ->withCount(['invoices as tagihan_menunggak_count' => function ($q) {
            $q->where('sisa', '>', 0);
        }]);

        // Jika Wali Murid, batasi hanya anak-anaknya
        if ($user->isGuardian()) {
            $guardian = $user->guardian;
            if (!$guardian) {
                return response()->json(['success' => true, 'data' => [], 'summary' => []]);
            }
            $childIds = $guardian->students()->pluck('students.id');
            $query->whereIn('id', $childIds);
        }

        // Filter Kelas
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        }

        // Filter Pencarian (Nama, NIS, NISN)
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        // Filter Status Pembayaran
        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'lunas') {
                $query->whereHas('invoices')
                      ->whereDoesntHave('invoices', fn($q) => $q->where('sisa', '>', 0));
            } elseif ($status === 'menunggak') {
                $query->whereHas('invoices', fn($q) => $q->where('sisa', '>', 0));
            } elseif ($status === 'belum_ada_tagihan') {
                $query->doesntHave('invoices');
            }
        }

        $allStudentsQuery = clone $query;
        $totalSiswa = (clone $allStudentsQuery)->count();

        $perPage = min((int) $request->query('per_page', 25), 500);
        $paginated = $query->orderBy('nama_lengkap', 'asc')->paginate($perPage);

        $transformed = collect($paginated->items())->map(function ($s) {
            $totalTagihan = (float) ($s->total_tagihan ?? 0);
            $totalDibayar = (float) ($s->total_dibayar ?? 0);
            $totalSisa = (float) ($s->total_sisa ?? 0);

            $statusPembayaran = 'tidak_ada_tagihan';
            if ($totalTagihan > 0) {
                if ($totalSisa <= 0) {
                    $statusPembayaran = 'lunas';
                } elseif ($totalDibayar > 0) {
                    $statusPembayaran = 'menunggak';
                } else {
                    $statusPembayaran = 'belum_bayar';
                }
            }

            $primaryGuardian = $s->guardians->firstWhere('pivot.is_kontak_utama', true) ?? $s->guardians->first();

            return [
                'id' => $s->id,
                'nis' => $s->nis,
                'nisn' => $s->nisn,
                'nama_lengkap' => $s->nama_lengkap,
                'class_id' => $s->class_id,
                'kelas' => $s->schoolClass ? $s->schoolClass->nama : '-',
                'wali' => $primaryGuardian ? [
                    'nama' => $primaryGuardian->nama_lengkap,
                    'no_hp' => $primaryGuardian->no_hp,
                    'hubungan' => $primaryGuardian->pivot->hubungan ?? 'Wali',
                ] : null,
                'no_hp_siswa' => $s->no_hp_siswa,
                'total_tagihan' => $totalTagihan,
                'total_dibayar' => $totalDibayar,
                'total_sisa' => $totalSisa,
                'count_invoices' => (int) $s->invoices_count,
                'count_lunas' => (int) $s->tagihan_lunas_count,
                'count_menunggak' => (int) $s->tagihan_menunggak_count,
                'status_pembayaran' => $statusPembayaran,
            ];
        });

        $globalSummary = [
            'total_siswa' => $totalSiswa,
            'total_tagihan' => (float) Invoice::sum('jumlah'),
            'total_dibayar' => (float) Invoice::sum('jumlah_dibayar'),
            'total_piutang' => (float) Invoice::sum('sisa'),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Rekap keuangan siswa berhasil diambil.',
            'data' => $transformed,
            'summary' => $globalSummary,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function studentRecapDetail(Request $request, int $studentId): JsonResponse
    {
        $user = $request->user();

        if ($user->isGuardian()) {
            $guardian = $user->guardian;
            if (!$guardian || !$guardian->students()->where('students.id', $studentId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berhak melihat data keuangan anak ini.',
                ], 403);
            }
        }

        $student = Student::with([
            'schoolClass:id,nama,tingkat',
            'guardians' => function ($q) {
                $q->select('guardians.id', 'guardians.nama_lengkap', 'guardians.no_hp', 'guardians.email', 'guardians.pekerjaan')
                  ->withPivot(['hubungan', 'is_kontak_utama']);
            },
        ])->findOrFail($studentId);

        // Ambil tahun ajaran aktif atau yang dipilih
        $activeYear = AcademicYear::where('status', 'aktif')->first() ?? AcademicYear::latest('id')->first();
        $academicYearId = $request->query('academic_year_id', $activeYear ? $activeYear->id : null);
        $academicYear = $academicYearId ? AcademicYear::find($academicYearId) : $activeYear;

        // Ambil semua master tarif dan jenis biaya (aktif)
        $allFeeTypes = FeeType::where('is_aktif', true)->orderBy('id', 'asc')->get();
        // Cari fee type yang berkode SPP atau bertipe bulanan
        $sppFeeType = $allFeeTypes->firstWhere('kode', 'SPP') 
            ?? $allFeeTypes->firstWhere('periode', 'bulanan') 
            ?? $allFeeTypes->first();

        // Ambil semua tagihan siswa
        $invoicesQuery = Invoice::with([
            'feeType:id,kode,nama,periode,nominal',
            'academicYear:id,nama',
            'payments' => fn($q) => $q->where('is_batal', false)->orderBy('tanggal', 'desc'),
        ])
        ->where('student_id', $studentId);

        if ($academicYearId) {
            $invoicesQuery->where(function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId)
                  ->orWhereNull('academic_year_id');
            });
        }

        $invoices = $invoicesQuery->orderBy('jatuh_tempo', 'asc')->get();

        // 1. Matriks 12 Bulan SPP (Juli s/d Juni Tahun Ajaran)
        $yearStart = 2026;
        if ($academicYear && preg_match('/^(\d{4})/', $academicYear->nama, $matches)) {
            $yearStart = (int) $matches[1];
        } else {
            $yearStart = (int) date('Y');
        }

        $bulanNames = [
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
        ];

        $matriks12Bulan = [];
        $seq = 1;
        foreach ([7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6] as $m) {
            $calYear = ($m >= 7) ? $yearStart : ($yearStart + 1);
            $periodKey = sprintf('%04d-%02d', $calYear, $m);
            $labelBulan = $bulanNames[$m] . ' ' . $calYear;

            // Cari apakah sudah ada tagihan SPP untuk periode ini
            $inv = $invoices->first(function ($item) use ($periodKey, $sppFeeType) {
                return $item->periode === $periodKey && ($sppFeeType ? $item->fee_type_id === $sppFeeType->id : true);
            });

            // Jika tidak ada di SPP fee type, cari tagihan bulanan apapun pada periode ini
            if (!$inv) {
                $inv = $invoices->first(function ($item) use ($periodKey) {
                    return $item->periode === $periodKey;
                });
            }

            $matriks12Bulan[] = [
                'urutan' => $seq++,
                'bulan_angka' => $m,
                'bulan_nama' => $bulanNames[$m],
                'tahun' => $calYear,
                'periode_key' => $periodKey,
                'label' => $labelBulan,
                'has_invoice' => !is_null($inv),
                'invoice' => $inv,
                'nominal_standar' => $sppFeeType ? (float) $sppFeeType->nominal : 0,
                'status' => $inv ? $inv->status : 'belum_terbit',
                'jumlah' => $inv ? (float) $inv->jumlah : ($sppFeeType ? (float) $sppFeeType->nominal : 0),
                'jumlah_dibayar' => $inv ? (float) $inv->jumlah_dibayar : 0,
                'sisa' => $inv ? (float) $inv->sisa : ($sppFeeType ? (float) $sppFeeType->nominal : 0),
            ];
        }

        // 2. Rekap per Tarif & Jenis Biaya (Terhubung ke menu Tarif & Jenis Biaya)
        $rekapJenisBiaya = $allFeeTypes->map(function ($ft) use ($invoices) {
            $relatedInvoices = $invoices->where('fee_type_id', $ft->id)->values();
            $totalTagihan = (float) $relatedInvoices->sum('jumlah');
            $totalDibayar = (float) $relatedInvoices->sum('jumlah_dibayar');
            $totalSisa = (float) $relatedInvoices->sum('sisa');

            $status = 'belum_ada_tagihan';
            if ($relatedInvoices->count() > 0) {
                if ($totalSisa <= 0) {
                    $status = 'lunas';
                } elseif ($totalDibayar > 0) {
                    $status = 'sebagian';
                } else {
                    $status = 'belum_bayar';
                }
            }

            return [
                'fee_type_id' => $ft->id,
                'kode' => $ft->kode,
                'nama' => $ft->nama,
                'periode' => $ft->periode,
                'nominal_standar' => (float) $ft->nominal,
                'keterangan' => $ft->keterangan,
                'count_invoices' => $relatedInvoices->count(),
                'total_tagihan' => $totalTagihan,
                'total_dibayar' => $totalDibayar,
                'total_sisa' => $totalSisa,
                'status' => $status,
                'invoices' => $relatedInvoices,
            ];
        });

        // 3. Tagihan Belum Lunas & Tagihan Lunas
        $tagihanBelumLunas = $invoices->filter(fn($inv) => (float) $inv->sisa > 0)->values();
        $tagihanLunas = $invoices->filter(fn($inv) => (float) $inv->sisa <= 0)->values();

        // 4. Riwayat Kuitansi Pembayaran
        $invoiceIds = $invoices->pluck('id');
        $riwayatPembayaran = Payment::with([
            'invoice:id,no_tagihan,fee_type_id,periode',
            'invoice.feeType:id,nama',
            'recorder:id,nama',
        ])
        ->whereIn('invoice_id', $invoiceIds)
        ->where('is_batal', false)
        ->orderBy('tanggal', 'desc')
        ->orderBy('id', 'desc')
        ->get();

        $primaryGuardian = $student->guardians->firstWhere('pivot.is_kontak_utama', true) ?? $student->guardians->first();

        return response()->json([
            'success' => true,
            'message' => 'Detail rekap keuangan siswa berhasil dimuat.',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                    'nama_lengkap' => $student->nama_lengkap,
                    'kelas' => $student->schoolClass ? $student->schoolClass->nama : '-',
                    'wali' => $primaryGuardian ? [
                        'nama' => $primaryGuardian->nama_lengkap,
                        'no_hp' => $primaryGuardian->no_hp,
                        'email' => $primaryGuardian->email,
                        'hubungan' => $primaryGuardian->pivot->hubungan ?? 'Wali',
                    ] : null,
                ],
                'academic_year' => $academicYear ? [
                    'id' => $academicYear->id,
                    'nama' => $academicYear->nama,
                ] : null,
                'spp_fee_type' => $sppFeeType,
                'summary' => [
                    'total_tagihan' => (float) $invoices->sum('jumlah'),
                    'total_dibayar' => (float) $invoices->sum('jumlah_dibayar'),
                    'total_sisa' => (float) $invoices->sum('sisa'),
                    'count_total' => $invoices->count(),
                    'count_lunas' => $tagihanLunas->count(),
                    'count_belum_lunas' => $tagihanBelumLunas->count(),
                ],
                'matriks_12_bulan' => $matriks12Bulan,
                'rekap_jenis_biaya' => $rekapJenisBiaya,
                'tagihan_belum_lunas' => $tagihanBelumLunas,
                'tagihan_lunas' => $tagihanLunas,
                'riwayat_pembayaran' => $riwayatPembayaran,
            ],
        ]);
    }
}
