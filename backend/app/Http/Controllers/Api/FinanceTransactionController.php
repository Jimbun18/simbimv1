<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransaction;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FinanceTransaction::with(['recorder:id,nama', 'payment.invoice.student:id,nama_lengkap']);

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->query('tipe'));
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->query('kategori'));
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->query('start_date'), $request->query('end_date')]);
        } elseif ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->query('tanggal'));
        }

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereYear('tanggal', $request->query('tahun'))
                ->whereMonth('tanggal', $request->query('bulan'));
        }

        $all = (clone $query)->get();
        $totalMasuk = (float) $all->where('tipe', 'masuk')->sum('jumlah');
        $totalKeluar = (float) $all->where('tipe', 'keluar')->sum('jumlah');
        $saldo = $totalMasuk - $totalKeluar;

        $summary = [
            'total_transaksi' => $all->count(),
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'saldo' => $saldo,
        ];

        $perPage = min((int) $request->query('per_page', 25), 100);
        $transactions = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar transaksi kas berhasil diambil.',
            'data' => $transactions->items(),
            'summary' => $summary,
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipe' => ['required', 'in:masuk,keluar'],
            'kategori' => ['required', 'string', 'max:50'],
            'jumlah' => ['required', 'numeric', 'min:1000'],
            'tanggal' => ['sometimes', 'required', 'date'],
            'keterangan' => ['required', 'string', 'max:500'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $lampiranPath = $request->file('lampiran')->store('finance/attachments', 'public');
        }

        $user = $request->user();
        $trans = FinanceTransaction::create([
            'no_transaksi' => FinanceTransaction::generateTransactionNumber($validated['tipe']),
            'tipe' => $validated['tipe'],
            'kategori' => $validated['kategori'],
            'jumlah' => $validated['jumlah'],
            'tanggal' => $validated['tanggal'] ?? now()->toDateString(),
            'keterangan' => $validated['keterangan'],
            'lampiran_path' => $lampiranPath,
            'dicatat_oleh' => $user->id,
        ]);

        ActivityLogService::log(
            aksi: 'record_finance_transaction',
            modul: 'finance_transactions',
            targetType: FinanceTransaction::class,
            targetId: $trans->id,
            dataSesudah: $trans->toArray(),
            keterangan: "Mencatat kas {$trans->tipe}: Rp " . number_format($trans->jumlah, 0, ',', '.') . " - {$trans->keterangan}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Transaksi kas berhasil dicatat.',
            'data' => $trans->load('recorder:id,nama'),
        ], 201);
    }

    public function report(Request $request): JsonResponse
    {
        $query = FinanceTransaction::query();

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->query('start_date'), $request->query('end_date')]);
        } elseif ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereYear('tanggal', $request->query('tahun'))
                ->whereMonth('tanggal', $request->query('bulan'));
        }

        $records = $query->get();

        $masukRecords = $records->where('tipe', 'masuk');
        $keluarRecords = $records->where('tipe', 'keluar');

        $totalMasuk = (float) $masukRecords->sum('jumlah');
        $totalKeluar = (float) $keluarRecords->sum('jumlah');

        $breakdownMasuk = $masukRecords->groupBy('kategori')->map(fn($group) => (float) $group->sum('jumlah'));
        $breakdownKeluar = $keluarRecords->groupBy('kategori')->map(fn($group) => (float) $group->sum('jumlah'));

        return response()->json([
            'success' => true,
            'message' => 'Laporan keuangan berhasil dihitung.',
            'data' => [
                'periode' => [
                    'start_date' => $request->query('start_date'),
                    'end_date' => $request->query('end_date'),
                    'bulan' => $request->query('bulan'),
                    'tahun' => $request->query('tahun'),
                ],
                'ringkasan' => [
                    'total_pemasukan' => $totalMasuk,
                    'total_pengeluaran' => $totalKeluar,
                    'surplus_defisit' => $totalMasuk - $totalKeluar,
                ],
                'breakdown_pemasukan' => $breakdownMasuk,
                'breakdown_pengeluaran' => $breakdownKeluar,
            ],
        ]);
    }
}
