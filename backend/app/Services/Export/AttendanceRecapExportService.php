<?php

namespace App\Services\Export;

use App\Models\SchoolClass;
use App\Models\Subject;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceRecapExportService
{
    /**
     * Export Rekapitulasi Presensi Per Kelas & Mapel ke Excel (.xlsx)
     */
    public function exportClassRecap(
        SchoolClass $schoolClass,
        array $recapData,
        string $startDate,
        string $endDate,
        ?Subject $subject = null
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Presensi');
        $sheet->setShowGridlines(true);

        $className = $schoolClass->nama ?? 'Kelas';
        $homeroomTeacher = $schoolClass->homeroomTeacher?->nama_lengkap ?? '-';
        $subjectName = $subject?->nama ?? 'Semua Mata Pelajaran';
        $periodStr = Carbon::parse($startDate)->isoFormat('D MMMM Y') . ' s/d ' . Carbon::parse($endDate)->isoFormat('D MMMM Y');
        $totalMeetings = $recapData['total_meetings'] ?? 0;
        $students = $recapData['students'] ?? [];
        $summary = $recapData['summary'] ?? [];

        // 1. Title Block
        $headers = [
            'No',
            'NIS',
            'NISN',
            'Nama Lengkap Siswa',
            'L/P',
            'Hadir (H)',
            'Sakit (S)',
            'Izin (I)',
            'Alpa (A)',
            'Total Hadir/Absen',
            'Pertemuan Kelas',
            '% Kehadiran',
            'Predikat Disiplin',
        ];
        $colCount = count($headers);
        $lastCol = Coordinate::stringFromColumnIndex($colCount);

        // Header Instansi
        $sheet->setCellValue('A1', 'SIM-BIM — SISTEM INFORMASI MANAJEMEN SEKOLAH & BIMBEL');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true)->getColor()->setRGB('059669');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // Judul Dokumen
        $sheet->setCellValue('A2', "REKAPITULASI PRESENSI SISWA KELAS {$className}");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setSize(12)->setBold(true)->getColor()->setRGB('1E293B');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Info Metadata Baris 3 & 4
        $metaRow1 = "Mata Pelajaran: {$subjectName}   |   Periode: {$periodStr}   |   Total Pertemuan KBM Terlaksana: {$totalMeetings} Sesi";
        $sheet->setCellValue('A3', $metaRow1);
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->getStyle('A3')->getFont()->setSize(9.5)->setBold(true)->getColor()->setRGB('475569');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(18);

        $metaRow2 = "Wali Kelas: {$homeroomTeacher}   |   Tahun Ajaran: " . ($schoolClass->academicYear?->nama ?? '-') . '   |   Dicetak: ' . now()->isoFormat('D MMMM Y HH:mm') . ' WIB';
        $sheet->setCellValue('A4', $metaRow2);
        $sheet->mergeCells("A4:{$lastCol}4");
        $sheet->getStyle('A4')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('64748B');
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(18);

        $sheet->getRowDimension(5)->setRowHeight(10); // Spacer

        // 2. Table Headers (Row 6)
        $headerRow = 6;
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

        // 3. Table Rows
        $row = $headerRow + 1;
        $no = 1;

        foreach ($students as $stu) {
            $col = 1;
            $persen = (float) ($stu['persentase_kehadiran'] ?? 0);

            $predikat = 'Sangat Baik';
            if ($persen < 70) {
                $predikat = 'Perlu Pembinaan';
            } elseif ($persen < 80) {
                $predikat = 'Cukup';
            } elseif ($persen < 90) {
                $predikat = 'Baik';
            }

            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $no);
            $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($col++) . $row, $stu['nis'] ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($col++) . $row, $stu['nisn'] ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $stu['nama_lengkap'] ?? '-');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, strtoupper($stu['jenis_kelamin'] ?? '-'));
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $stu['hadir'] ?? 0);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $stu['sakit'] ?? 0);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $stu['izin'] ?? 0);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $stu['alpa'] ?? 0);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $stu['total_recorded'] ?? 0);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $totalMeetings);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, number_format($persen, 1) . '%');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, $predikat);

            $row++;
            $no++;
        }

        $endRow = max($row - 1, $headerRow);

        // Apply Data Rows Styling
        for ($r = $headerRow + 1; $r <= $endRow; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(20);
            $range = "A{$r}:{$lastCol}{$r}";
            $style = $sheet->getStyle($range);
            $style->getFont()->setSize(9.5)->getColor()->setRGB('1E293B');
            $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            // Zebra striping
            if ($r % 2 === 0) {
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            } else {
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
            }
            $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        }

        // Center alignment on numbers/codes
        $centerCols = ['A', 'B', 'C', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
        foreach ($centerCols as $c) {
            $sheet->getStyle("{$c}7:{$c}{$endRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // 4. Summary Footer Row
        $sheet->setCellValue("A{$row}", 'TOTAL AKUMULASI KELAS');
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->setCellValue("F{$row}", $summary['total_hadir'] ?? 0);
        $sheet->setCellValue("G{$row}", $summary['total_sakit'] ?? 0);
        $sheet->setCellValue("H{$row}", $summary['total_izin'] ?? 0);
        $sheet->setCellValue("I{$row}", $summary['total_alpa'] ?? 0);
        $sheet->setCellValue("J{$row}", $summary['total_recorded'] ?? 0);
        $sheet->setCellValue("K{$row}", $totalMeetings * count($students));
        $avgPersen = $summary['persentase_kehadiran'] ?? 0;
        $sheet->setCellValue("L{$row}", number_format($avgPersen, 1) . '%');
        $sheet->setCellValue("M{$row}", $avgPersen >= 85 ? 'Tercapai Target' : 'Perlu Perhatian');

        $footerRange = "A{$row}:{$lastCol}{$row}";
        $footerStyle = $sheet->getStyle($footerRange);
        $footerStyle->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('064E3B');
        $footerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D1FAE5'); // Emerald 100
        $footerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $footerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('059669');
        $sheet->getRowDimension($row)->setRowHeight(24);

        // Autofit columns
        for ($c = 1; $c <= $colCount; $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        $sheet->freezePane('A7');

        $safeClass = preg_replace('/[^A-Za-z0-9_\-]/', '_', $className);
        $filename = "Rekap_Presensi_{$safeClass}_" . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Rekapitulasi Presensi Pendidik & Tenaga Kependidikan ke Excel (.xlsx)
     */
    public function exportTeacherRecap(
        array $recapData,
        string $startDate,
        string $endDate
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Presensi PTK');
        $sheet->setShowGridlines(true);

        $periodStr = Carbon::parse($startDate)->isoFormat('D MMMM Y') . ' s/d ' . Carbon::parse($endDate)->isoFormat('D MMMM Y');
        $ptkList = $recapData['ptk_list'] ?? [];
        $summary = $recapData['summary'] ?? [];
        $workDays = $recapData['period']['total_hari_kerja'] ?? ($summary['total_hari_kerja'] ?? 0);

        // 1. Headers Definition
        $headers = [
            'No',
            'NIP / NIK',
            'Nama Lengkap Guru / Tendik',
            'Jabatan / Peran',
            'Hadir Tepat Waktu',
            'Terlambat (Sesi)',
            'Total Menit Terlambat',
            'Izin',
            'Sakit',
            'Dinas Luar',
            'Alpa / Tanpa Keterangan',
            'Total Hadir Fisik',
            '% Kehadiran',
            'Predikat Disiplin',
        ];
        $colCount = count($headers);
        $lastCol = Coordinate::stringFromColumnIndex($colCount);

        // 2. Title Block
        $sheet->setCellValue('A1', 'SIM-BIM — SISTEM INFORMASI MANAJEMEN SEKOLAH & BIMBEL');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true)->getColor()->setRGB('059669');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $sheet->setCellValue('A2', 'REKAPITULASI PRESENSI PENDIDIK & TENAGA KEPENDIDIKAN (PTK)');
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setSize(12)->setBold(true)->getColor()->setRGB('1E293B');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(20);

        $metaRow1 = "Periode: {$periodStr}   |   Hari Kerja Efektif: {$workDays} Hari   |   Total PTK: " . count($ptkList) . " Orang";
        $sheet->setCellValue('A3', $metaRow1);
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->getStyle('A3')->getFont()->setSize(9.5)->setBold(true)->getColor()->setRGB('475569');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(18);

        $metaRow2 = 'Dicetak pada: ' . now()->isoFormat('D MMMM Y HH:mm') . ' WIB oleh Administrator SIM-BIM';
        $sheet->setCellValue('A4', $metaRow2);
        $sheet->mergeCells("A4:{$lastCol}4");
        $sheet->getStyle('A4')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('64748B');
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(18);

        $sheet->getRowDimension(5)->setRowHeight(10); // Spacer

        // 3. Table Headers
        $headerRow = 6;
        $sheet->getRowDimension($headerRow)->setRowHeight(26);
        foreach ($headers as $idx => $headerText) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$colLetter}{$headerRow}", $headerText);
        }

        $headerRange = "A{$headerRow}:{$lastCol}{$headerRow}";
        $headerStyle = $sheet->getStyle($headerRange);
        $headerStyle->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E'); // Teal 700
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('0D9488');

        // 4. Data Rows
        $row = 7;
        foreach ($ptkList as $i => $ptk) {
            $sheet->getRowDimension($row)->setRowHeight(22);

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $ptk['nip'] ?? $ptk['nik'] ?? '-');
            $sheet->setCellValue("C{$row}", $ptk['nama_lengkap'] ?? '-');
            $sheet->setCellValue("D{$row}", $ptk['peran'] ?? '-');
            $sheet->setCellValue("E{$row}", $ptk['hadir_tepat_waktu'] ?? 0);
            $sheet->setCellValue("F{$row}", $ptk['terlambat'] ?? 0);
            $sheet->setCellValue("G{$row}", $ptk['menit_terlambat'] ?? 0);
            $sheet->setCellValue("H{$row}", $ptk['izin'] ?? 0);
            $sheet->setCellValue("I{$row}", $ptk['sakit'] ?? 0);
            $sheet->setCellValue("J{$row}", $ptk['dinas'] ?? 0);
            $sheet->setCellValue("K{$row}", $ptk['alpa'] ?? 0);
            $sheet->setCellValue("L{$row}", $ptk['total_hadir_fisik'] ?? 0);

            $persen = (float) ($ptk['persentase_kehadiran'] ?? 0);
            $sheet->setCellValue("M{$row}", number_format($persen, 1) . '%');
            $sheet->setCellValue("N{$row}", $ptk['predikat'] ?? '-');

            $rowRange = "A{$row}:{$lastCol}{$row}";
            $sheet->getStyle($rowRange)->getFont()->setSize(9.5);
            $sheet->getStyle($rowRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');

            // Alignments
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("E{$row}:L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("M{$row}:N{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Alternating fill
            if ($i % 2 === 1) {
                $sheet->getStyle($rowRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            // Bold on name and percent
            $sheet->getStyle("C{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $sheet->getStyle("M{$row}")->getFont()->setBold(true)->getColor()->setRGB($persen >= 85 ? '059669' : 'D97706');

            $row++;
        }

        // 5. Summary Footer Row
        $sheet->setCellValue("A{$row}", 'TOTAL & RATA-RATA AKUMULASI');
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("E{$row}", $summary['total_hadir_tepat_waktu'] ?? 0);
        $sheet->setCellValue("F{$row}", $summary['total_terlambat'] ?? 0);
        $sheet->setCellValue("G{$row}", $summary['total_menit_terlambat'] ?? 0);
        $sheet->setCellValue("H{$row}", $summary['total_izin'] ?? 0);
        $sheet->setCellValue("I{$row}", $summary['total_sakit'] ?? 0);
        $sheet->setCellValue("J{$row}", $summary['total_dinas'] ?? 0);
        $sheet->setCellValue("K{$row}", $summary['total_alpa'] ?? 0);
        $sheet->setCellValue("L{$row}", $summary['total_hadir_fisik'] ?? 0);

        $avgPersen = (float) ($summary['rata_rata_kehadiran'] ?? 0);
        $sheet->setCellValue("M{$row}", number_format($avgPersen, 1) . '%');
        $sheet->setCellValue("N{$row}", $avgPersen >= 85 ? 'Kepatuhan Baik' : 'Perlu Monitoring');

        $footerRange = "A{$row}:{$lastCol}{$row}";
        $footerStyle = $sheet->getStyle($footerRange);
        $footerStyle->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('064E3B');
        $footerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCFBF1'); // Teal 100
        $footerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $footerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('0D9488');
        $sheet->getRowDimension($row)->setRowHeight(24);

        // Autofit columns
        for ($c = 1; $c <= $colCount; $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        $sheet->freezePane('A7');

        $filename = "Rekap_Presensi_Guru_Tendik_{$startDate}_{$endDate}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
