<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelTemplateGenerator
{
    public static function normalizeJenis(string $jenis): string
    {
        return match (strtolower(trim($jenis))) {
            'siswa', 'student', 'students' => 'students',
            'guru', 'tendik', 'teacher', 'teachers' => 'teachers',
            'kelas', 'rombel', 'class', 'classes' => 'classes',
            'mata_pelajaran', 'mapel', 'subject', 'subjects' => 'subjects',
            'wali', 'wali_murid', 'guardian', 'guardians' => 'guardians',
            default => strtolower(trim($jenis)),
        };
    }

    public static function generate(string $jenis): StreamedResponse
    {
        $canonical = static::normalizeJenis($jenis);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ucfirst($canonical));

        $headers = static::getHeaders($canonical);
        $sample = static::getSampleData($canonical);

        // Header row
        foreach ($headers as $colIdx => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $header);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Header style
        $headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '166534'], // Dark green
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Sample data row
        if (!empty($sample)) {
            foreach ($sample as $colIdx => $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                $sheet->setCellValueExplicit("{$colLetter}2", $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $filename = "template_import_{$canonical}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public static function getHeaders(string $jenis): array
    {
        $canonical = static::normalizeJenis($jenis);
        return match ($canonical) {
            'students' => [
                'nis', 'nisn', 'nik', 'no_kk', 'nama_lengkap', 'jenis_kelamin', 'tempat_lahir', 'tgl_lahir',
                'agama', 'anak_ke', 'alamat_jalan', 'rt', 'rw', 'desa_kelurahan', 'kecamatan', 'kab_kota',
                'provinsi', 'kode_pos', 'tinggal_bersama', 'transportasi', 'asal_sekolah_tk', 'tgl_masuk',
                'kode_kelas', 'status', 'nama_ayah', 'nik_ayah', 'pekerjaan_ayah', 'nama_ibu', 'nik_ibu',
                'pekerjaan_ibu', 'no_hp_wali', 'no_whatsapp_wali'
            ],
            'teachers' => [
                'nip', 'nuptk', 'nik', 'nama_lengkap', 'gelar_depan', 'gelar_belakang', 'jenis_kelamin',
                'tempat_lahir', 'tgl_lahir', 'agama', 'alamat_jalan', 'kab_kota', 'provinsi', 'no_hp',
                'email', 'jenis_ptk', 'status_kepegawaian', 'tgl_mulai_tugas', 'jabatan', 'tugas_tambahan',
                'pendidikan_terakhir', 'jurusan_prodi', 'institusi', 'tahun_lulus', 'status_sertifikasi'
            ],
            'classes' => [
                'kode', 'nama', 'tingkat', 'paralel', 'tahun_ajaran', 'nip_wali_kelas', 'kode_ruang', 'kapasitas'
            ],
            'subjects' => [
                'kode', 'nama', 'kelompok', 'tingkat_berlaku', 'jam_per_minggu', 'kkm'
            ],
            'guardians' => [
                'nis_siswa', 'hubungan', 'nama_lengkap', 'nik', 'pekerjaan', 'pendidikan_terakhir',
                'no_hp', 'no_whatsapp', 'email', 'is_kontak_utama'
            ],
            default => throw new \InvalidArgumentException("Jenis template {$jenis} tidak didukung."),
        };
    }

    public static function getSampleData(string $jenis): array
    {
        $canonical = static::normalizeJenis($jenis);
        return match ($canonical) {
            'students' => [
                '2026001', '0123456789', '3302011005180001', '3302010101150002', 'Ahmad Dani Saputra', 'L', 'Banyumas', '2018-05-10',
                'Islam', '1', 'Jl. Kenanga No. 5', '01', '02', 'Kranji', 'Purwokerto Timur', 'Banyumas',
                'Jawa Tengah', '53116', 'orang_tua', 'Motor', 'TK Pertiwi 1', '2026-07-15',
                '1A', 'aktif', 'Bambang Saputra', '3302011204850001', 'Karyawan Swasta', 'Dewi Lestari', '3302015008880002',
                'Ibu Rumah Tangga', '+6281234567890', '+6281234567890'
            ],
            'teachers' => [
                '199001012020011001', '9876543210987654', '3302010101900001', 'Rahmat Hidayat', 'Drs.', 'M.Pd.', 'L',
                'Purwokerto', '1990-01-01', 'Islam', 'Jl. Mawar No. 12', 'Banyumas', 'Jawa Tengah', '+6281398765432',
                'rahmat@simbim.sch.id', 'guru_kelas', 'gty', '2020-01-02', 'Guru Kelas 1', 'Pembina Pramuka',
                'S1', 'PGSD', 'Universitas Muhammadiyah Purwokerto', '2013', 'sudah'
            ],
            'classes' => [
                '1A', 'Kelas 1 A', '1', 'A', '2026/2027', '199001012020011001', 'RK-01', '28'
            ],
            'subjects' => [
                'PAI', 'Pendidikan Agama Islam', 'agama', '1-6', '3', '75'
            ],
            'guardians' => [
                '2026001', 'ayah', 'Bambang Saputra', '3302011204850001', 'Karyawan Swasta', 'S1',
                '+6281234567890', '+6281234567890', 'bambang@gmail.com', '1'
            ],
            default => [],
        };
    }
}
