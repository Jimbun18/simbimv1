<?php

namespace App\Services\Export;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterDataExportService
{
    /**
     * Export Master Data Guru & Tendik ke Excel
     */
    public function exportTeachers(Request $request): StreamedResponse
    {
        $query = Teacher::with(['religion', 'education', 'user']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('nuptk', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('jenis_ptk')) {
            $query->where('jenis_ptk', $request->query('jenis_ptk'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $teachers = $query->orderBy('nama_lengkap')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Guru & Tendik');
        $sheet->setShowGridlines(true);

        $headers = [
            'No',
            'Nama Lengkap & Gelar',
            'NIP',
            'NUPTK',
            'NIK',
            'L/P',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Usia',
            'Agama',
            'Jenis PTK',
            'Status Kepegawaian',
            'Status Keaktifan',
            'Tgl Mulai Tugas',
            'Masa Kerja / Mengabdi',
            'No. WhatsApp / HP',
            'Email',
            'Alamat Domisili',
            'Kab / Kota',
            'Pendidikan Terakhir',
            'Sertifikasi Pendidik',
        ];

        $colCount = count($headers);
        $totalData = $teachers->count();
        $currentFilter = $request->filled('jenis_ptk') ? 'Jenis PTK: ' . $request->query('jenis_ptk') : 'Semua Jenis PTK';
        $metaText = 'Dicetak pada: ' . Carbon::now()->translatedFormat('d F Y H:i') . ' WIB | Filter: ' . $currentFilter . ' | Total: ' . $totalData . ' Orang';

        $this->applyHeaderTitle($sheet, 'SDI BINA INSAN MANDIRI PURWOKERTO', 'DATA MASTER GURU & TENAGA KEPENDIDIKAN', $metaText, $colCount);
        $this->applyTableHeaders($sheet, 5, $headers);

        $row = 6;
        $no = 1;
        foreach ($teachers as $t) {
            $tglLahir = $t->tgl_lahir ? Carbon::parse($t->tgl_lahir) : null;
            $usia = $tglLahir ? $tglLahir->age . ' thn' : '-';
            $tglMulai = $t->tgl_mulai_tugas ? Carbon::parse($t->tgl_mulai_tugas) : null;
            $tenure = $this->formatTenure($tglMulai);

            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $t->nama_gelar ?: $t->nama_lengkap);
            
            // Format angka pengenal sebagai string eksplisit
            $sheet->setCellValueExplicit("C{$row}", (string) ($t->nip ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", (string) ($t->nuptk ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$row}", (string) ($t->nik ?? '-'), DataType::TYPE_STRING);
            
            $sheet->setCellValue("F{$row}", $t->jenis_kelamin === 'L' ? 'L' : 'P');
            $sheet->setCellValue("G{$row}", $t->tempat_lahir ?? '-');
            $sheet->setCellValue("H{$row}", $tglLahir ? $tglLahir->translatedFormat('d-m-Y') : '-');
            $sheet->setCellValue("I{$row}", $usia);
            $sheet->setCellValue("J{$row}", $t->religion?->nama ?? '-');
            $sheet->setCellValue("K{$row}", $this->formatJenisPtk($t->jenis_ptk));
            $sheet->setCellValue("L{$row}", strtoupper((string) ($t->status_kepegawaian ?? '-')));
            $sheet->setCellValue("M{$row}", ucfirst((string) ($t->status ?? 'aktif')));
            $sheet->setCellValue("N{$row}", $tglMulai ? $tglMulai->translatedFormat('d-m-Y') : '-');
            $sheet->setCellValue("O{$row}", $tenure);
            $sheet->setCellValueExplicit("P{$row}", (string) ($t->no_hp ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValue("Q{$row}", $t->email ?? '-');
            $sheet->setCellValue("R{$row}", $t->alamat_jalan ?? '-');
            $sheet->setCellValue("S{$row}", $t->kab_kota ?? '-');
            $sheet->setCellValue("T{$row}", $t->education?->nama ?? '-');
            $sheet->setCellValue("U{$row}", $t->status_sertifikasi === 'sudah' ? 'Sudah Tersertifikasi' : 'Belum');

            $row++;
            $no++;
        }

        $endRow = max($row - 1, 6);
        $this->applyDataStyling($sheet, 6, $endRow, $colCount, [
            'center_cols' => ['A', 'C', 'D', 'E', 'F', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'U'],
        ]);

        // Summary footer
        $sheet->setCellValue("A{$row}", "TOTAL DATA: {$totalData} ORANG");
        $sheet->mergeCells("A{$row}:" . Coordinate::stringFromColumnIndex($colCount) . "{$row}");
        $this->applyFooterStyling($sheet, $row, $colCount);

        $this->autoFitColumns($sheet, $colCount);
        $sheet->freezePane('A6');

        $filename = 'Master_Data_Guru_Tendik_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Master Data Siswa ke Excel
     */
    public function exportStudents(Request $request): StreamedResponse
    {
        $query = Student::with(['currentClass', 'religion', 'guardians']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        }

        $students = $query->orderBy('nama_lengkap')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');
        $sheet->setShowGridlines(true);

        $headers = [
            'No',
            'NIS',
            'NISN',
            'NIK',
            'Nama Lengkap Siswa',
            'Nama Panggilan',
            'Kelas Saat Ini',
            'L/P',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Usia',
            'Agama',
            'Status Siswa',
            'Tanggal Bergabung',
            'Masa Bergabung',
            'Alamat Domisili',
            'Kab / Kota',
            'Nama Orang Tua / Wali',
            'No. HP Wali / Siswa',
        ];

        $colCount = count($headers);
        $totalData = $students->count();
        $statusLabel = $request->filled('status') ? 'Status: ' . ucfirst($request->query('status')) : 'Semua Status';
        $metaText = 'Dicetak pada: ' . Carbon::now()->translatedFormat('d F Y H:i') . ' WIB | Filter: ' . $statusLabel . ' | Total: ' . $totalData . ' Siswa';

        $this->applyHeaderTitle($sheet, 'SDI BINA INSAN MANDIRI PURWOKERTO', 'DATA MASTER PESERTA DIDIK (SISWA)', $metaText, $colCount);
        $this->applyTableHeaders($sheet, 5, $headers);

        $row = 6;
        $no = 1;
        foreach ($students as $s) {
            $tglLahir = $s->tgl_lahir ? Carbon::parse($s->tgl_lahir) : null;
            $usia = $tglLahir ? $tglLahir->age . ' thn' : '-';
            $tglMasuk = $s->tgl_masuk ? Carbon::parse($s->tgl_masuk) : null;
            $tenure = $this->formatTenure($tglMasuk);

            $primaryGuardian = $s->primaryGuardian() ?? $s->guardians->first();
            $guardianName = $primaryGuardian ? ($primaryGuardian->nama_lengkap . ' (' . ucfirst($primaryGuardian->pivot->hubungan ?? 'Wali') . ')') : '-';
            $guardianPhone = $primaryGuardian?->no_hp ?: ($s->no_hp_siswa ?: '-');

            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValueExplicit("B{$row}", (string) ($s->nis ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$row}", (string) ($s->nisn ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", (string) ($s->nik ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValue("E{$row}", $s->nama_lengkap);
            $sheet->setCellValue("F{$row}", $s->nama_panggilan ?? '-');
            $sheet->setCellValue("G{$row}", $s->currentClass?->nama ?? '-');
            $sheet->setCellValue("H{$row}", $s->jenis_kelamin === 'L' ? 'L' : 'P');
            $sheet->setCellValue("I{$row}", $s->tempat_lahir ?? '-');
            $sheet->setCellValue("J{$row}", $tglLahir ? $tglLahir->translatedFormat('d-m-Y') : '-');
            $sheet->setCellValue("K{$row}", $usia);
            $sheet->setCellValue("L{$row}", $s->religion?->nama ?? '-');
            $sheet->setCellValue("M{$row}", ucfirst((string) ($s->status ?? 'aktif')));
            $sheet->setCellValue("N{$row}", $tglMasuk ? $tglMasuk->translatedFormat('d-m-Y') : '-');
            $sheet->setCellValue("O{$row}", $tenure);
            $sheet->setCellValue("P{$row}", $s->alamat_jalan ?? '-');
            $sheet->setCellValue("Q{$row}", $s->kab_kota ?? '-');
            $sheet->setCellValue("R{$row}", $guardianName);
            $sheet->setCellValueExplicit("S{$row}", (string) $guardianPhone, DataType::TYPE_STRING);

            $row++;
            $no++;
        }

        $endRow = max($row - 1, 6);
        $this->applyDataStyling($sheet, 6, $endRow, $colCount, [
            'center_cols' => ['A', 'B', 'C', 'D', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'O', 'S'],
        ]);

        // Summary footer
        $sheet->setCellValue("A{$row}", "TOTAL DATA: {$totalData} SISWA");
        $sheet->mergeCells("A{$row}:" . Coordinate::stringFromColumnIndex($colCount) . "{$row}");
        $this->applyFooterStyling($sheet, $row, $colCount);

        $this->autoFitColumns($sheet, $colCount);
        $sheet->freezePane('A6');

        $filename = 'Master_Data_Siswa_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Master Data Kelas & Rombel ke Excel
     */
    public function exportClasses(Request $request): StreamedResponse
    {
        $query = SchoolClass::with(['academicYear', 'homeroomTeacher', 'room'])
            ->withCount(['enrollments as total_siswa_aktif' => function ($q) {
                $q->where('status', 'aktif');
            }]);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->query('academic_year_id'));
        }

        $classes = $query->orderBy('tingkat')->orderBy('nama')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Kelas & Rombel');
        $sheet->setShowGridlines(true);

        $headers = [
            'No',
            'Nama Rombel / Kelas',
            'Kode',
            'Tingkat',
            'Tahun Ajaran',
            'Wali Kelas',
            'Ruangan',
            'Kapasitas Maksimal',
            'Jumlah Siswa Terdaftar',
            'Status Rombel',
        ];

        $colCount = count($headers);
        $totalData = $classes->count();
        $metaText = 'Dicetak pada: ' . Carbon::now()->translatedFormat('d F Y H:i') . ' WIB | Total: ' . $totalData . ' Rombel';

        $this->applyHeaderTitle($sheet, 'SDI BINA INSAN MANDIRI PURWOKERTO', 'DATA MASTER KELAS & ROMBONGAN BELAJAR', $metaText, $colCount);
        $this->applyTableHeaders($sheet, 5, $headers);

        $row = 6;
        $no = 1;
        foreach ($classes as $c) {
            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $c->nama);
            $sheet->setCellValue("C{$row}", $c->kode ?? '-');
            $sheet->setCellValue("D{$row}", 'Kelas ' . $c->tingkat);
            $sheet->setCellValue("E{$row}", $c->academicYear?->nama ?? '-');
            $sheet->setCellValue("F{$row}", $c->homeroomTeacher?->nama_gelar ?: ($c->homeroomTeacher?->nama_lengkap ?? 'Belum Ditentukan'));
            $sheet->setCellValue("G{$row}", $c->room?->nama ?? '-');
            $sheet->setCellValue("H{$row}", ($c->kapasitas ?? 30) . ' Siswa');
            $sheet->setCellValue("I{$row}", ($c->total_siswa_aktif ?? 0) . ' Siswa');
            $sheet->setCellValue("J{$row}", ucfirst((string) ($c->status ?? 'aktif')));

            $row++;
            $no++;
        }

        $endRow = max($row - 1, 6);
        $this->applyDataStyling($sheet, 6, $endRow, $colCount, [
            'center_cols' => ['A', 'C', 'D', 'E', 'G', 'H', 'I', 'J'],
        ]);

        // Summary footer
        $sheet->setCellValue("A{$row}", "TOTAL DATA: {$totalData} KELAS/ROMBEL");
        $sheet->mergeCells("A{$row}:" . Coordinate::stringFromColumnIndex($colCount) . "{$row}");
        $this->applyFooterStyling($sheet, $row, $colCount);

        $this->autoFitColumns($sheet, $colCount);
        $sheet->freezePane('A6');

        $filename = 'Master_Data_Kelas_Rombel_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Format masa kerja/bergabung (Tahun, Bulan, Hari)
     */
    private function formatTenure(?CarbonInterface $startDate, ?CarbonInterface $endDate = null): string
    {
        if (!$startDate) return '-';
        $end = $endDate ?? Carbon::now();
        if ($end->lt($startDate)) return '0 hr';

        $diff = $startDate->diff($end);
        $parts = [];
        if ($diff->y > 0) $parts[] = "{$diff->y} thn";
        if ($diff->m > 0) $parts[] = "{$diff->m} bln";
        if ($diff->d > 0 || empty($parts)) $parts[] = "{$diff->d} hr";

        return implode(' ', $parts);
    }

    /**
     * Format jenis PTK menjadi teks resmi
     */
    private function formatJenisPtk(?string $val): string
    {
        return match ($val) {
            'guru_kelas' => 'Guru Kelas',
            'guru_mapel' => 'Guru Mata Pelajaran',
            'guru_agama' => 'Guru PAI / Agama',
            'guru_olahraga' => 'Guru PJOK / Olahraga',
            'kepala_sekolah' => 'Kepala Sekolah',
            'tenaga_kependidikan' => 'Tenaga Kependidikan',
            default => $val ? ucwords(str_replace('_', ' ', $val)) : '-',
        };
    }

    /**
     * Format Banner Judul Lembaga di Atas Tabel
     */
    private function applyHeaderTitle(Worksheet $sheet, string $schoolName, string $reportTitle, string $meta, int $colCount): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($colCount);

        // Row 1: Nama Sekolah
        $sheet->setCellValue('A1', $schoolName);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true)->getColor()->setRGB('064E3B'); // Emerald 900
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Row 2: Judul Dokumen
        $sheet->setCellValue('A2', $reportTitle);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setSize(11)->setBold(true)->getColor()->setRGB('1E293B'); // Slate 800
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // Row 3: Meta & Timestamp
        $sheet->setCellValue('A3', $meta);
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('64748B'); // Slate 500
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(16);

        // Row 4: Spacer
        $sheet->getRowDimension(4)->setRowHeight(8);
    }

    /**
     * Format Header Kolom Tabel (Emerald 600)
     */
    private function applyTableHeaders(Worksheet $sheet, int $headerRow, array $headers): void
    {
        $colCount = count($headers);
        $lastCol = Coordinate::stringFromColumnIndex($colCount);

        $col = 1;
        foreach ($headers as $h) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue("{$colLetter}{$headerRow}", $h);
            $col++;
        }

        $headerRange = "A{$headerRow}:{$lastCol}{$headerRow}";
        $headerStyle = $sheet->getStyle($headerRange);

        $headerStyle->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('059669'); // Emerald 600
        $headerStyle->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('047857');
        $sheet->getRowDimension($headerRow)->setRowHeight(28);
    }

    /**
     * Format Data Baris Tabel (Zebra striping & Borders)
     */
    private function applyDataStyling(Worksheet $sheet, int $startRow, int $endRow, int $colCount, array $options = []): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        $centerCols = $options['center_cols'] ?? ['A'];

        for ($r = $startRow; $r <= $endRow; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(20);
            $range = "A{$r}:{$lastCol}{$r}";
            $style = $sheet->getStyle($range);

            $style->getFont()->setSize(9.5)->getColor()->setRGB('1E293B');
            $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            // Zebra striping
            if ($r % 2 === 0) {
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC'); // Slate 50
            } else {
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
            }

            $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
        }

        // Apply center alignment to specified columns
        foreach ($centerCols as $colLetter) {
            $sheet->getStyle("{$colLetter}{$startRow}:{$colLetter}{$endRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    /**
     * Format Footer Summary Baris Total
     */
    private function applyFooterStyling(Worksheet $sheet, int $footerRow, int $colCount): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        $range = "A{$footerRow}:{$lastCol}{$footerRow}";
        $style = $sheet->getStyle($range);

        $style->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('064E3B');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5'); // Emerald 50
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('059669');
        $sheet->getRowDimension($footerRow)->setRowHeight(24);
    }

    /**
     * Atur Lebar Kolom Otomatis + Padding
     */
    private function autoFitColumns(Worksheet $sheet, int $colCount): void
    {
        for ($col = 1; $col <= $colCount; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }
}
