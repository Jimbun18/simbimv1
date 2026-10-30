<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PpdbRegistration;
use App\Services\ActivityLogService;
use App\Services\ImageCompressionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

            // Berkas
            'pas_foto' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'akta_kelahiran' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:10240'],
            'kartu_keluarga' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:10240'],
        ]);

        $tahun = date('Y');
        $jenjangCode = match ($validated['jenjang']) {
            'KB' => 'KB',
            'TK A' => 'TKA',
            'TK B' => 'TKB',
            'SD' => 'SD',
            default => 'GEN',
        };

        // Buat nomor pendaftaran unik: PPDB-2026-SD-0001
        $countThisYear = PpdbRegistration::whereYear('created_at', $tahun)->count() + 1;
        $nomorPendaftaran = sprintf('PPDB-%s-%s-%04d', $tahun, $jenjangCode, $countThisYear);

        // Pastikan tidak tabrakan
        while (PpdbRegistration::where('nomor_pendaftaran', $nomorPendaftaran)->exists()) {
            $countThisYear++;
            $nomorPendaftaran = sprintf('PPDB-%s-%s-%04d', $tahun, $jenjangCode, $countThisYear);
        }

        // Upload Berkas
        $pasFotoPath = null;
        if ($request->hasFile('pas_foto')) {
            $pasFotoPath = ImageCompressionService::compressAndStore(
                $request->file('pas_foto'),
                'ppdb/photos',
                800,
                85
            );
        }

        $aktaKelahiranPath = null;
        if ($request->hasFile('akta_kelahiran')) {
            $file = $request->file('akta_kelahiran');
            if (str_starts_with($file->getMimeType(), 'image/')) {
                $aktaKelahiranPath = ImageCompressionService::compressAndStore($file, 'ppdb/documents', 1400, 85);
            } else {
                $aktaKelahiranPath = $file->store('ppdb/documents', 'public');
            }
        }

        $kartuKeluargaPath = null;
        if ($request->hasFile('kartu_keluarga')) {
            $file = $request->file('kartu_keluarga');
            if (str_starts_with($file->getMimeType(), 'image/')) {
                $kartuKeluargaPath = ImageCompressionService::compressAndStore($file, 'ppdb/documents', 1400, 85);
            } else {
                $kartuKeluargaPath = $file->store('ppdb/documents', 'public');
            }
        }

        // Simpan pendaftaran
        $registration = PpdbRegistration::create([
            'nomor_pendaftaran' => $nomorPendaftaran,
            'tahun_ajaran' => $validated['tahun_ajaran'] ?? '2026/2027',
            'gelombang' => $validated['gelombang'] ?? 'Gelombang 1',
            'jenjang' => $validated['jenjang'],

            'nama_lengkap' => $validated['nama_lengkap'],
            'nama_panggilan' => $validated['nama_panggilan'],
            'jenis_kelamin' => $validated['jenis_kelamin'],
            'tempat_lahir' => $validated['tempat_lahir'],
            'tanggal_lahir' => $validated['tanggal_lahir'],
            'usia_daftar' => $validated['usia_daftar'] ?? null,
            'jumlah_saudara' => $validated['jumlah_saudara'] ?? 0,
            'anak_ke' => $validated['anak_ke'] ?? 1,
            'alamat_lengkap' => $validated['alamat_lengkap'],
            'tinggal_dengan' => $validated['tinggal_dengan'],
            'asal_sekolah' => $validated['asal_sekolah'] ?? null,
            'alamat_asal_sekolah' => $validated['alamat_asal_sekolah'] ?? null,
            'pindah_ke_kelas' => $validated['pindah_ke_kelas'] ?? null,
            'alasan_pindah' => $validated['alasan_pindah'] ?? null,

            'nama_ayah' => $validated['nama_ayah'],
            'ttl_ayah' => $validated['ttl_ayah'] ?? null,
            'pendidikan_ayah' => $validated['pendidikan_ayah'] ?? null,
            'pekerjaan_ayah' => $validated['pekerjaan_ayah'] ?? null,
            'penghasilan_ayah' => $validated['penghasilan_ayah'] ?? null,
            'alamat_ayah' => $validated['alamat_ayah'] ?? null,
            'no_hp_ayah' => $validated['no_hp_ayah'],

            'nama_ibu' => $validated['nama_ibu'],
            'ttl_ibu' => $validated['ttl_ibu'] ?? null,
            'pendidikan_ibu' => $validated['pendidikan_ibu'] ?? null,
            'pekerjaan_ibu' => $validated['pekerjaan_ibu'] ?? null,
            'penghasilan_ibu' => $validated['penghasilan_ibu'] ?? null,
            'alamat_ibu' => $validated['alamat_ibu'] ?? null,
            'no_hp_ibu' => $validated['no_hp_ibu'],

            'pas_foto_path' => $pasFotoPath,
            'akta_kelahiran_path' => $aktaKelahiranPath,
            'kartu_keluarga_path' => $kartuKeluargaPath,
            'status' => 'menunggu',
        ]);

        ActivityLogService::log(
            aksi: 'ppdb_pendaftaran_baru',
            modul: 'ppdb',
            targetType: PpdbRegistration::class,
            targetId: $registration->id,
            dataSebelum: null,
            dataSesudah: $registration->toArray(),
            keterangan: "Pendaftar PPDB Online baru ({$registration->jenjang}): {$registration->nama_lengkap} ({$registration->nomor_pendaftaran})"
        );

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran PPDB Online berhasil dikirim. Simpan nomor pendaftaran Anda untuk konfirmasi.',
            'data' => $registration,
        ], 201);
    }

    /**
     * Cek status pendaftaran calon murid secara publik
     */
    public function checkStatus(string $nomor): JsonResponse
    {
        $reg = PpdbRegistration::where('nomor_pendaftaran', $nomor)
            ->orWhere('no_hp_ayah', $nomor)
            ->orWhere('no_hp_ibu', $nomor)
            ->first();

        if (!$reg) {
            return response()->json([
                'success' => false,
                'message' => 'Data pendaftaran dengan nomor atau kontak tersebut tidak ditemukan.',
            ], 440);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nomor_pendaftaran' => $reg->nomor_pendaftaran,
                'nama_lengkap' => $reg->nama_lengkap,
                'jenjang' => $reg->jenjang,
                'status' => $reg->status,
                'status_label' => $reg->status_label,
                'catatan_admin' => $reg->catatan_admin,
                'tanggal_daftar' => $reg->created_at->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * ADMIN: List Pendaftar PPDB (Filter, Pencarian, & Pagination)
     */
    public function index(Request $request): JsonResponse
    {
        $query = PpdbRegistration::with('verifiedBy:id,nama')->orderByDesc('created_at');

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
        $registration = PpdbRegistration::with('verifiedBy:id,nama')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $registration,
        ]);
    }

    /**
     * ADMIN: Update Status & Verifikasi Pendaftaran
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:menunggu,diterima,cadangan,ditolak'],
            'catatan_admin' => ['nullable', 'string'],
        ]);

        $registration = PpdbRegistration::findOrFail($id);
        $oldData = $registration->toArray();

        $registration->update([
            'status' => $request->input('status'),
            'catatan_admin' => $request->input('catatan_admin'),
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        ActivityLogService::log(
            aksi: 'ppdb_update_status',
            modul: 'ppdb',
            targetType: PpdbRegistration::class,
            targetId: $registration->id,
            dataSebelum: $oldData,
            dataSesudah: $registration->fresh()->toArray(),
            keterangan: "Admin memperbarui status PPDB {$registration->nama_lengkap} ({$registration->nomor_pendaftaran}) menjadi {$registration->status_label}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Status pendaftaran PPDB berhasil diperbarui.',
            'data' => $registration->fresh()->load('verifiedBy:id,nama'),
        ]);
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

        $registration->delete();

        ActivityLogService::log(
            aksi: 'ppdb_hapus',
            modul: 'ppdb',
            targetType: PpdbRegistration::class,
            targetId: $id,
            dataSebelum: $oldData,
            dataSesudah: null,
            keterangan: "Admin menghapus data pendaftaran PPDB: {$oldData['nama_lengkap']} ({$oldData['nomor_pendaftaran']})"
        );

        return response()->json([
            'success' => true,
            'message' => 'Data pendaftar PPDB berhasil dihapus.',
        ]);
    }

    /**
     * ADMIN: Export Data Pendaftar PPDB ke File Excel (.xlsx)
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
                  ->orWhere('nama_ibu', 'like', "%{$search}%")
                  ->orWhere('no_hp_ayah', 'like', "%{$search}%");
            });
        }

        $records = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data PPDB Online');
        $sheet->setShowGridlines(true);

        // Header Dokumen
        $sheet->setCellValue('A1', 'REKAPITULASI PENERIMAAN PESERTA DIDIK BARU (PPDB) ONLINE');
        $sheet->setCellValue('A2', 'PAUD TERPADU & SD ISLAM TERPADU BINA INSAN MANDIRI PURWOKERTO');
        $sheet->setCellValue('A3', 'Diekspor pada: ' . date('d F Y H:i:s') . ' WIB | Total: ' . $records->count() . ' Siswa');

        $sheet->mergeCells('A1:AA1');
        $sheet->mergeCells('A2:AA2');
        $sheet->mergeCells('A3:AA3');

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('003366'));
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('666666'));

        // Headers Tabel
        $headers = [
            'A5' => 'No',
            'B5' => 'No Pendaftaran',
            'C5' => 'Tgl Daftar',
            'D5' => 'Jenjang',
            'E5' => 'Nama Lengkap',
            'F5' => 'Nama Panggilan',
            'G5' => 'L/P',
            'H5' => 'Tempat Lahir',
            'I5' => 'Tanggal Lahir',
            'J5' => 'Usia Saat Daftar',
            'K5' => 'Anak Ke',
            'L5' => 'Jml Saudara',
            'M5' => 'Alamat Calon Siswa',
            'N5' => 'Tinggal Bersama',
            'O5' => 'Asal Sekolah',
            'P5' => 'Alamat Asal Sekolah',
            'Q5' => 'Siswa Pindahan (Kelas/Alasan)',
            'R5' => 'Nama Ayah',
            'S5' => 'Pendidikan Ayah',
            'T5' => 'Pekerjaan Ayah',
            'U5' => 'Penghasilan Ayah',
            'V5' => 'No WA Ayah',
            'W5' => 'Nama Ibu',
            'X5' => 'Pendidikan Ibu',
            'Y5' => 'Pekerjaan Ibu',
            'Z5' => 'No WA Ibu',
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

