<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Payment::with([
            'invoice.student:id,nis,nama_lengkap,class_id',
            'invoice.student.schoolClass:id,nama',
            'invoice.feeType:id,nama,kode',
            'recorder:id,nama',
        ]);

        if ($user->isGuardian()) {
            $guardian = $user->guardian;
            if (!$guardian) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $childIds = $guardian->students()->pluck('students.id');
            $query->whereHas('invoice', fn($q) => $q->whereIn('student_id', $childIds));
        }

        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->query('invoice_id'));
        }

        if ($request->filled('metode')) {
            $query->where('metode', $request->query('metode'));
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->query('tanggal'));
        }

        if ($request->has('is_batal')) {
            $query->where('is_batal', filter_var($request->query('is_batal'), FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = min((int) $request->query('per_page', 25), 100);
        $payments = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pembayaran berhasil diambil.',
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'exists:invoices,id'],
            'jumlah' => ['required', 'numeric', 'min:1000'],
            'metode' => ['required', 'in:tunai,transfer,qris,beasiswa'],
            'tanggal' => ['sometimes', 'required', 'date'],
            'bukti' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $invoice = Invoice::with(['student', 'feeType'])->findOrFail($validated['invoice_id']);

        if ($invoice->status === 'lunas') {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan ini sudah lunas.',
                'data' => null,
            ], 422);
        }

        if ($validated['jumlah'] > $invoice->sisa) {
            return response()->json([
                'success' => false,
                'message' => "Jumlah pembayaran (Rp " . number_format($validated['jumlah'], 0, ',', '.') . ") melebihi sisa tagihan (Rp " . number_format($invoice->sisa, 0, ',', '.') . ").",
                'data' => null,
            ], 422);
        }

        $user = $request->user();
        $buktiPath = null;
        if ($request->hasFile('bukti')) {
            $buktiPath = $request->file('bukti')->store('payments/receipts', 'public');
        }

        $tanggal = $validated['tanggal'] ?? now()->toDateString();
        $jumlahBayar = (float) $validated['jumlah'];

        $payment = null;
        $transaction = null;

        DB::transaction(function () use ($validated, $invoice, $jumlahBayar, $tanggal, $buktiPath, $user, &$payment, &$transaction) {
            // 1. Simpan Payment
            $payment = Payment::create([
                'no_kuitansi' => Payment::generateReceiptNumber(),
                'invoice_id' => $invoice->id,
                'tanggal' => $tanggal,
                'jumlah' => $jumlahBayar,
                'metode' => $validated['metode'],
                'bukti_path' => $buktiPath,
                'catatan' => $validated['catatan'] ?? null,
                'dicatat_oleh' => $user->id,
            ]);

            // 2. Simpan Transaksi Kas Masuk
            $transaction = FinanceTransaction::create([
                'no_transaksi' => FinanceTransaction::generateTransactionNumber('masuk'),
                'tipe' => 'masuk',
                'kategori' => 'pembayaran_siswa',
                'jumlah' => $jumlahBayar,
                'tanggal' => $tanggal,
                'keterangan' => "Pembayaran {$invoice->feeType->nama} - {$invoice->student->nama_lengkap} ({$payment->no_kuitansi})",
                'payment_id' => $payment->id,
                'lampiran_path' => $buktiPath,
                'dicatat_oleh' => $user->id,
            ]);

            // 3. Update Invoice Balance & Status
            $newDibayar = $invoice->jumlah_dibayar + $jumlahBayar;
            $newSisa = $invoice->jumlah - $newDibayar;
            $newStatus = $newSisa <= 0 ? 'lunas' : 'sebagian';

            $invoice->update([
                'jumlah_dibayar' => $newDibayar,
                'sisa' => $newSisa,
                'status' => $newStatus,
                'updated_by' => $user->id,
            ]);
        });

        ActivityLogService::log(
            aksi: 'record_payment',
            modul: 'payments',
            targetType: Payment::class,
            targetId: $payment->id,
            dataSesudah: $payment->toArray(),
            keterangan: "Mencatat pembayaran Rp " . number_format($payment->jumlah, 0, ',', '.') . " ({$payment->no_kuitansi}) untuk tagihan {$invoice->no_tagihan}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil dicatat.',
            'data' => $payment->load(['invoice.student', 'invoice.feeType', 'recorder:id,nama']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $payment = Payment::with([
            'invoice.student.schoolClass',
            'invoice.feeType',
            'recorder:id,nama',
            'transaction',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail pembayaran berhasil diambil.',
            'data' => $payment,
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $payment = Payment::with(['invoice', 'transaction'])->findOrFail($id);

        if ($payment->is_batal) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran ini sudah dibatalkan sebelumnya.',
                'data' => null,
            ], 422);
        }

        $validated = $request->validate([
            'alasan_batal' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $user = $request->user();
        $invoice = $payment->invoice;

        DB::transaction(function () use ($payment, $invoice, $validated, $user) {
            // 1. Mark Payment Batal
            $payment->update([
                'is_batal' => true,
                'alasan_batal' => $validated['alasan_batal'],
            ]);

            // 2. FR-KEU-08: Buat Jurnal Pembalik (Kas Keluar)
            $reversalTrans = FinanceTransaction::create([
                'no_transaksi' => FinanceTransaction::generateTransactionNumber('keluar'),
                'tipe' => 'keluar',
                'kategori' => 'pembatalan_pembayaran',
                'jumlah' => $payment->jumlah,
                'tanggal' => now()->toDateString(),
                'keterangan' => "[JURNAL PEMBALIK] Pembatalan {$payment->no_kuitansi}. Alasan: {$validated['alasan_batal']}",
                'payment_id' => $payment->id,
                'is_pembalik' => true,
                'referensi_transaksi_id' => $payment->transaction?->id,
                'dicatat_oleh' => $user->id,
            ]);

            // 3. Kembalikan saldo tagihan
            $newDibayar = max(0, $invoice->jumlah_dibayar - $payment->jumlah);
            $newSisa = $invoice->jumlah - $newDibayar;
            $newStatus = $newDibayar <= 0 ? 'belum_bayar' : 'sebagian';

            $invoice->update([
                'jumlah_dibayar' => $newDibayar,
                'sisa' => $newSisa,
                'status' => $newStatus,
                'updated_by' => $user->id,
            ]);
        });

        ActivityLogService::log(
            aksi: 'cancel_payment',
            modul: 'payments',
            targetType: Payment::class,
            targetId: $payment->id,
            keterangan: "Membatalkan pembayaran {$payment->no_kuitansi} dengan jurnal pembalik. Alasan: {$validated['alasan_batal']}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil dibatalkan dan jurnal pembalik telah dicatat.',
            'data' => $payment->fresh()->load('invoice'),
        ]);
    }

    public function receipt(int $id): JsonResponse
    {
        $payment = Payment::with([
            'invoice.student.schoolClass',
            'invoice.feeType',
            'invoice.academicYear',
            'recorder:id,nama',
        ])->findOrFail($id);

        $student = $payment->invoice->student;
        $feeType = $payment->invoice->feeType;

        $receiptData = [
            'id' => $payment->id,
            'no_kuitansi' => $payment->no_kuitansi,
            'no_tagihan' => $payment->invoice->no_tagihan,
            'tanggal' => $payment->tanggal->format('d/m/Y'),
            'student' => [
                'id' => $student->id,
                'nis' => $student->nis,
                'nama' => $student->nama_lengkap,
                'kelas' => $student->schoolClass?->nama ?? '-',
            ],
            'student_name' => $student->nama_lengkap,
            'class_name' => $student->schoolClass?->nama ?? '-',
            'fee_type' => $feeType->nama,
            'fee_name' => $feeType->nama,
            'periode' => $payment->invoice->periode,
            'period' => $payment->invoice->periode,
            'jumlah_bayar' => (float) $payment->jumlah,
            'jumlah' => (float) $payment->jumlah,
            'metode' => strtoupper($payment->metode),
            'sisa_tagihan' => (float) $payment->invoice->sisa,
            'status_tagihan' => strtoupper($payment->invoice->status),
            'catatan' => $payment->catatan,
            'kasir' => $payment->recorder?->nama ?? 'Petugas Tata Usaha',
            'recorded_by' => $payment->recorder?->nama ?? 'Petugas Tata Usaha',
            'terbilang' => ((int) $payment->jumlah > 0 ? $this->terbilang((int) $payment->jumlah) : 'Nol') . ' Rupiah',
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data kuitansi berhasil diambil.',
            'data' => $receiptData,
        ]);
    }

    private function terbilang(int $angka): string
    {
        $angka = abs($angka);
        $baca = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        $hasil = '';

        if ($angka < 12) {
            $hasil = $baca[$angka];
        } elseif ($angka < 20) {
            $hasil = $this->terbilang($angka - 10) . ' Belas';
        } elseif ($angka < 100) {
            $hasil = $this->terbilang(intdiv($angka, 10)) . ' Puluh ' . $this->terbilang($angka % 10);
        } elseif ($angka < 200) {
            $hasil = 'Seratus ' . $this->terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $hasil = $this->terbilang(intdiv($angka, 100)) . ' Ratus ' . $this->terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $hasil = 'Seribu ' . $this->terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $hasil = $this->terbilang(intdiv($angka, 1000)) . ' Ribu ' . $this->terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $hasil = $this->terbilang(intdiv($angka, 1000000)) . ' Juta ' . $this->terbilang($angka % 1000000);
        } else {
            $hasil = $this->terbilang(intdiv($angka, 1000000000)) . ' Miliar ' . $this->terbilang($angka % 1000000000);
        }

        return trim(preg_replace('/\s+/', ' ', $hasil));
    }
}
