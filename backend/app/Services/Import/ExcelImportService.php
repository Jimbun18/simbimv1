<?php

namespace App\Services\Import;

use App\Models\AcademicYear;
use App\Models\Education;
use App\Models\Guardian;
use App\Models\ImportBatch;
use App\Models\ImportError;
use App\Models\Occupation;
use App\Models\Religion;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Transport;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelImportService
{
    /**
     * Parse and validate an uploaded Excel/CSV file.
     */
    public function validate(UploadedFile $file, string $jenis, ?array $columnMapping = null): array
    {
        $jenis = ExcelTemplateGenerator::normalizeJenis($jenis);
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, false, false);

        if (count($rows) < 2) {
            throw new \InvalidArgumentException('File Excel tidak memiliki baris data.');
        }

        $isDapodik = false;
        $startRow = 1;

        if ($jenis === 'students') {
            $firstCell = strtolower(trim((string) ($rows[0][0] ?? '')));
            $row4Cell1 = strtolower(trim((string) ($rows[4][1] ?? '')));
            $row4Cell4 = strtolower(trim((string) ($rows[4][4] ?? '')));

            if (str_contains($firstCell, 'daftar peserta didik') || ($row4Cell1 === 'nama' && $row4Cell4 === 'nisn')) {
                $isDapodik = true;
                $startRow = 6; // Data begins at index 6 (Row 7 in Excel)
            }
        }

        $mappedIndices = [];

        if ($isDapodik) {
            $mappedIndices = [
                'nama_lengkap' => 1,
                'nis' => 2,
                'jenis_kelamin' => 3,
                'nisn' => 4,
                'tempat_lahir' => 5,
                'tgl_lahir' => 6,
                'nik' => 7,
                'agama' => 8,
                'alamat_jalan' => 9,
                'rt' => 10,
                'rw' => 11,
                'desa_kelurahan' => 13,
                'kecamatan' => 14,
                'kode_pos' => 15,
                'tinggal_bersama' => 16,
                'transportasi' => 17,
                'no_hp_wali' => 19,
                'nama_ayah' => 24,
                'pekerjaan_ayah' => 27,
                'nik_ayah' => 29,
                'nama_ibu' => 30,
                'pekerjaan_ibu' => 33,
                'nik_ibu' => 35,
                'kode_kelas' => 42,
                'no_akta_lahir' => 49,
                'asal_sekolah_tk' => 56,
                'anak_ke' => 57,
                'no_kk' => 60,
                'berat_kg' => 61,
                'tinggi_cm' => 62,
                'jumlah_saudara' => 64,
                'jarak_ke_sekolah_km' => 65,
            ];
        } else {
            // Header mapping for standard template
            $fileHeaders = array_map(fn ($h) => trim(strtolower((string) $h)), $rows[0]);
            $expectedHeaders = ExcelTemplateGenerator::getHeaders($jenis);

            foreach ($expectedHeaders as $expected) {
                $matchedHeader = $columnMapping[$expected] ?? $expected;
                $idx = array_search(strtolower($matchedHeader), $fileHeaders, true);
                if ($idx !== false) {
                    $mappedIndices[$expected] = $idx;
                }
            }
        }

        // Create initial batch record
        $storedPath = $file->storeAs('imports', "batch_" . time() . "_{$file->getClientOriginalName()}");

        $batch = ImportBatch::create([
            'user_id' => auth()->id() ?? 1,
            'jenis_data' => $jenis,
            'nama_file' => $file->getClientOriginalName(),
            'total' => max(0, count($rows) - $startRow),
            'berhasil' => 0,
            'gagal' => 0,
            'status' => 'validating',
            'column_mapping' => [
                'stored_file_path' => $storedPath,
                'mapped_indices' => $mappedIndices,
                'is_dapodik' => $isDapodik,
                'created_ids' => [],
            ],
        ]);

        $validRows = [];
        $duplicates = [];
        $errorsCount = 0;
        $seenKeys = [];

        // Pre-load reference maps for fast in-memory lookup
        $religions = Religion::pluck('id', DB::raw('LOWER(nama)'))->toArray();
        $educations = Education::pluck('id', DB::raw('LOWER(nama)'))->toArray();
        $occupations = Occupation::pluck('id', DB::raw('LOWER(nama)'))->toArray();
        $transports = Transport::pluck('id', DB::raw('LOWER(nama)'))->toArray();

        for ($r = $startRow; $r < count($rows); $r++) {
            $rowNum = $r + 1; // 1-indexed row in Excel
            $rawRow = $rows[$r];

            // Extract mapped row data
            $rowData = [];
            foreach ($mappedIndices as $field => $colIdx) {
                $val = $rawRow[$colIdx] ?? null;
                $rowData[$field] = $this->cleanCellValue($field, $val);
            }

            // Skip completely empty rows
            if (empty(array_filter($rowData, fn ($v) => $v !== null && $v !== ''))) {
                continue;
            }

            if ($isDapodik) {
                if (empty($rowData['nis'])) {
                    $rowData['nis'] = sprintf("BIM%04d", 1000 + $r);
                }
                if (empty($rowData['agama'])) {
                    $rowData['agama'] = 'Islam';
                }
            }

            $rowErrors = $this->validateRow(
                $jenis,
                $rowData,
                $rowNum,
                $seenKeys,
                $religions,
                $educations,
                $occupations,
                $transports
            );

            if (!empty($rowErrors)) {
                $errorsCount++;
                foreach ($rowErrors as $err) {
                    ImportError::create([
                        'batch_id' => $batch->id,
                        'nomor_baris' => $rowNum,
                        'kolom' => $err['kolom'],
                        'pesan' => $err['pesan'],
                    ]);
                }
            } else {
                $isDuplicate = $this->checkDatabaseDuplicate($jenis, $rowData);
                if ($isDuplicate) {
                    $duplicates[] = ['row' => $rowNum, 'data' => $rowData];
                } else {
                    $validRows[] = ['row' => $rowNum, 'data' => $rowData];
                }
            }
        }

        $totalNonEmpty = count($validRows) + count($duplicates) + $errorsCount;

        $batch->update([
            'total' => $totalNonEmpty,
            'berhasil' => count($validRows) + count($duplicates),
            'gagal' => $errorsCount,
            'status' => 'preview',
        ]);

        return [
            'batch_id' => $batch->id,
            'file_name' => $batch->nama_file,
            'total_rows' => $totalNonEmpty,
            'total_valid' => count($validRows) + count($duplicates),
            'total_error' => $errorsCount,
            'valid_count' => count($validRows),
            'duplicate_count' => count($duplicates),
            'error_count' => $errorsCount,
            'preview_samples' => array_slice(array_merge($validRows, $duplicates), 0, 50),
            'errors_preview' => $batch->errors()->take(50)->get()->map(fn ($e) => [
                'nomor_baris' => $e->nomor_baris,
                'kolom' => $e->kolom,
                'pesan' => $e->pesan,
            ]),
        ];
    }

    /**
     * Commit validated batch into permanent database tables.
     */
    public function commit(ImportBatch $batch, string $duplicateStrategy = 'skip'): array
    {
        if ($batch->status !== 'preview') {
            throw new \InvalidArgumentException("Batch berstatus '{$batch->status}' tidak dapat di-commit.");
        }

        $mapping = $batch->column_mapping;
        $filePath = $mapping['stored_file_path'] ?? null;

        if (!$filePath || !Storage::exists($filePath)) {
            throw new \RuntimeException('File sumber import tidak ditemukan di penyimpanan server.');
        }

        $fullPath = Storage::path($filePath);
        $spreadsheet = IOFactory::load($fullPath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, false, false);

        $mappedIndices = $mapping['mapped_indices'];
        $errorRowNumbers = $batch->errors()->pluck('nomor_baris')->toArray();

        $religions = Religion::pluck('id', DB::raw('LOWER(nama)'))->toArray();
        $educations = Education::pluck('id', DB::raw('LOWER(nama)'))->toArray();
        $occupations = Occupation::pluck('id', DB::raw('LOWER(nama)'))->toArray();
        $transports = Transport::pluck('id', DB::raw('LOWER(nama)'))->toArray();

        $isDapodik = $mapping['is_dapodik'] ?? false;
        $startRow = $isDapodik ? 6 : 1;

        $createdIds = [];
        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use (
            $batch,
            $rows,
            $mappedIndices,
            $errorRowNumbers,
            $duplicateStrategy,
            $religions,
            $educations,
            $occupations,
            $transports,
            $isDapodik,
            $startRow,
            &$createdIds,
            &$insertedCount,
            &$updatedCount,
            &$skippedCount
        ) {
            for ($r = $startRow; $r < count($rows); $r++) {
                $rowNum = $r + 1;
                if (in_array($rowNum, $errorRowNumbers, true)) {
                    continue; // Skip invalid rows
                }

                $rawRow = $rows[$r];
                $data = [];
                foreach ($mappedIndices as $field => $colIdx) {
                    $val = $rawRow[$colIdx] ?? null;
                    $data[$field] = $this->cleanCellValue($field, $val);
                }

                if (empty(array_filter($data, fn ($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                if ($isDapodik) {
                    if (empty($data['nis'])) {
                        $data['nis'] = sprintf("BIM%04d", 1000 + $r);
                    }
                    if (empty($data['agama'])) {
                        $data['agama'] = 'Islam';
                    }
                }

                $res = $this->importRow(
                    $batch->jenis_data,
                    $data,
                    $duplicateStrategy,
                    $religions,
                    $educations,
                    $occupations,
                    $transports
                );

                if ($res['action'] === 'created') {
                    $insertedCount++;
                    $createdIds[] = $res['id'];
                } elseif ($res['action'] === 'updated') {
                    $updatedCount++;
                } else {
                    $skippedCount++;
                }
            }

            // Save created IDs in column_mapping for rollback capability
            $currentMapping = $batch->column_mapping;
            $currentMapping['created_ids'] = $createdIds;

            $batch->update([
                'status' => 'committed',
                'berhasil' => $insertedCount + $updatedCount,
                'column_mapping' => $currentMapping,
            ]);

            ActivityLogService::log(
                aksi: 'commit_import',
                modul: 'import',
                targetType: ImportBatch::class,
                targetId: $batch->id,
                keterangan: "Commit import {$batch->jenis_data} batch #{$batch->id}: {$insertedCount} baru, {$updatedCount} diperbarui, {$skippedCount} dilewati."
            );
        });

        return [
            'batch_id' => $batch->id,
            'inserted' => $insertedCount,
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
        ];
    }

    /**
     * Rollback a previously committed batch.
     */
    public function rollback(ImportBatch $batch): array
    {
        if ($batch->status !== 'committed') {
            throw new \InvalidArgumentException("Batch dengan status '{$batch->status}' tidak dapat di-rollback.");
        }

        $createdIds = $batch->column_mapping['created_ids'] ?? [];

        if (empty($createdIds)) {
            $batch->update(['status' => 'rolled_back']);
            return ['rolled_back_count' => 0];
        }

        $deletedCount = 0;

        DB::transaction(function () use ($batch, $createdIds, &$deletedCount) {
            $modelClass = match ($batch->jenis_data) {
                'students' => Student::class,
                'teachers' => Teacher::class,
                'classes' => SchoolClass::class,
                'subjects' => Subject::class,
                'guardians' => Guardian::class,
                default => null,
            };

            if ($modelClass) {
                $deletedCount = $modelClass::whereIn('id', $createdIds)->forceDelete();
            }

            $batch->update(['status' => 'rolled_back']);

            ActivityLogService::log(
                aksi: 'rollback_import',
                modul: 'import',
                targetType: ImportBatch::class,
                targetId: $batch->id,
                keterangan: "Rollback import {$batch->jenis_data} batch #{$batch->id}: {$deletedCount} data dihapus permanen."
            );
        });

        return [
            'batch_id' => $batch->id,
            'rolled_back_count' => $deletedCount,
        ];
    }

    /**
     * Export row validation errors into a downloadable Excel report.
     */
    public function exportErrors(ImportBatch $batch): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Error');

        $sheet->setCellValue('A1', 'Nomor Baris');
        $sheet->setCellValue('B1', 'Kolom');
        $sheet->setCellValue('C1', 'Pesan Kesalahan');

        $errors = $batch->errors()->orderBy('nomor_baris')->get();
        $row = 2;
        foreach ($errors as $err) {
            $sheet->setCellValue("A{$row}", $err->nomor_baris);
            $sheet->setCellValue("B{$row}", $err->kolom ?? '-');
            $sheet->setCellValue("C{$row}", $err->pesan);
            $row++;
        }

        $filename = "error_import_batch_{$batch->id}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function validateRow(
        string $jenis,
        array $data,
        int $rowNum,
        array &$seenKeys,
        array $religions,
        array $educations,
        array $occupations,
        array $transports
    ): array {
        $errors = [];

        switch ($jenis) {
            case 'students':
                if (empty($data['nis'])) {
                    $errors[] = ['kolom' => 'nis', 'pesan' => 'NIS wajib diisi.'];
                } elseif (isset($seenKeys['nis'][$data['nis']])) {
                    $errors[] = ['kolom' => 'nis', 'pesan' => "Duplikat NIS '{$data['nis']}' ditemukan pada baris {$seenKeys['nis'][$data['nis']]}. "];
                } else {
                    $seenKeys['nis'][$data['nis']] = $rowNum;
                }

                if (!empty($data['nik']) && (!preg_match('/^\d{16}$/', $data['nik']))) {
                    $errors[] = ['kolom' => 'nik', 'pesan' => 'NIK harus 16 digit angka.'];
                }

                if (!empty($data['nisn']) && (!preg_match('/^\d{10}$/', $data['nisn']))) {
                    $errors[] = ['kolom' => 'nisn', 'pesan' => 'NISN harus 10 digit angka.'];
                }

                if (empty($data['nama_lengkap'])) {
                    $errors[] = ['kolom' => 'nama_lengkap', 'pesan' => 'Nama lengkap siswa wajib diisi.'];
                }

                if (empty($data['jenis_kelamin']) || !in_array(strtoupper($data['jenis_kelamin']), ['L', 'P'])) {
                    $errors[] = ['kolom' => 'jenis_kelamin', 'pesan' => 'Jenis kelamin harus L atau P.'];
                }

                if (empty($data['agama']) || !isset($religions[strtolower($data['agama'])])) {
                    $errors[] = ['kolom' => 'agama', 'pesan' => "Agama '{$data['agama']}' tidak dikenali di sistem."];
                }

                if (!empty($data['tgl_lahir']) && !$this->parseDate($data['tgl_lahir'])) {
                    $errors[] = ['kolom' => 'tgl_lahir', 'pesan' => 'Format tanggal lahir tidak valid (gunakan YYYY-MM-DD).'];
                }
                break;

            case 'teachers':
                if (empty($data['nama_lengkap'])) {
                    $errors[] = ['kolom' => 'nama_lengkap', 'pesan' => 'Nama lengkap guru wajib diisi.'];
                }

                if (empty($data['nik']) || !preg_match('/^\d{16}$/', $data['nik'])) {
                    $errors[] = ['kolom' => 'nik', 'pesan' => 'NIK wajib diisi dan harus 16 digit angka.'];
                } elseif (isset($seenKeys['nik'][$data['nik']])) {
                    $errors[] = ['kolom' => 'nik', 'pesan' => "Duplikat NIK '{$data['nik']}' ditemukan pada baris {$seenKeys['nik'][$data['nik']]}. "];
                } else {
                    $seenKeys['nik'][$data['nik']] = $rowNum;
                }

                if (!empty($data['agama']) && !isset($religions[strtolower($data['agama'])])) {
                    $errors[] = ['kolom' => 'agama', 'pesan' => "Agama '{$data['agama']}' tidak valid."];
                }
                break;

            case 'classes':
                if (empty($data['kode'])) {
                    $errors[] = ['kolom' => 'kode', 'pesan' => 'Kode kelas wajib diisi.'];
                }
                if (empty($data['nama'])) {
                    $errors[] = ['kolom' => 'nama', 'pesan' => 'Nama kelas wajib diisi.'];
                }
                if (empty($data['tingkat']) || !in_array((int)$data['tingkat'], [1, 2, 3, 4, 5, 6])) {
                    $errors[] = ['kolom' => 'tingkat', 'pesan' => 'Tingkat harus angka 1 sampai 6.'];
                }
                break;

            case 'subjects':
                if (empty($data['kode'])) {
                    $errors[] = ['kolom' => 'kode', 'pesan' => 'Kode mapel wajib diisi.'];
                }
                if (empty($data['nama'])) {
                    $errors[] = ['kolom' => 'nama', 'pesan' => 'Nama mapel wajib diisi.'];
                }
                if (empty($data['kelompok']) || !in_array(strtolower($data['kelompok']), ['umum', 'agama', 'muatan_lokal', 'ekstrakurikuler'])) {
                    $errors[] = ['kolom' => 'kelompok', 'pesan' => 'Kelompok mapel harus umum, agama, muatan_lokal, atau ekstrakurikuler.'];
                }
                break;
        }

        return $errors;
    }

    private function checkDatabaseDuplicate(string $jenis, array $data): bool
    {
        return match ($jenis) {
            'students' => (!empty($data['nis']) && Student::where('nis', $data['nis'])->exists())
                || (!empty($data['nisn']) && Student::where('nisn', $data['nisn'])->exists())
                || (!empty($data['nik']) && Student::where('nik', $data['nik'])->exists()),
            'teachers' => Teacher::where('nik', $data['nik'])->when(!empty($data['nip']), fn ($q) => $q->orWhere('nip', $data['nip']))->exists(),
            'classes' => SchoolClass::where('kode', $data['kode'])->exists(),
            'subjects' => Subject::where('kode', $data['kode'])->exists(),
            'guardians' => !empty($data['nik']) && Guardian::where('nik', $data['nik'])->exists(),
            default => false,
        };
    }

    private function importRow(
        string $jenis,
        array $data,
        string $strategy,
        array $religions,
        array $educations,
        array $occupations,
        array $transports
    ): array {
        switch ($jenis) {
            case 'students':
                $existing = null;
                if (!empty($data['nis'])) {
                    $existing = Student::where('nis', $data['nis'])->first();
                }
                if (!$existing && !empty($data['nisn'])) {
                    $existing = Student::where('nisn', $data['nisn'])->first();
                }
                if (!$existing && !empty($data['nik'])) {
                    $existing = Student::where('nik', $data['nik'])->first();
                }

                if ($existing) {
                    if ($strategy === 'reject') {
                        throw new \InvalidArgumentException("Duplikat data siswa NIS {$data['nis']}. Import dibatalkan.");
                    }
                    if ($strategy === 'skip') {
                        return ['action' => 'skipped', 'id' => $existing->id];
                    }
                    // Update
                    $existing->update([
                        'nama_lengkap' => $data['nama_lengkap'] ?? $existing->nama_lengkap,
                        'nisn' => $data['nisn'] ?? $existing->nisn,
                        'nik' => $data['nik'] ?? $existing->nik,
                        'jenis_kelamin' => !empty($data['jenis_kelamin']) ? strtoupper($data['jenis_kelamin']) : $existing->jenis_kelamin,
                        'no_kk' => $data['no_kk'] ?? $existing->no_kk,
                        'no_akta_lahir' => $data['no_akta_lahir'] ?? $existing->no_akta_lahir,
                        'desa_kelurahan' => $data['desa_kelurahan'] ?? $existing->desa_kelurahan,
                        'kecamatan' => $data['kecamatan'] ?? $existing->kecamatan,
                    ]);
                    return ['action' => 'updated', 'id' => $existing->id];
                }

                // Resolve religion and transport
                $agamaId = $religions[strtolower($data['agama'] ?? '')] ?? 1;
                $transportId = $transports[strtolower($data['transportasi'] ?? '')] ?? null;
                if (!$transportId && !empty($data['transportasi'])) {
                    foreach ($transports as $tName => $tId) {
                        if (str_contains(strtolower($data['transportasi']), $tName) || str_contains($tName, strtolower($data['transportasi']))) {
                            $transportId = $tId;
                            break;
                        }
                    }
                }

                // Resolve class if provided
                $classId = null;
                if (!empty($data['kode_kelas'])) {
                    $class = SchoolClass::where('kode', $data['kode_kelas'])
                        ->orWhere('nama', 'LIKE', '%' . $data['kode_kelas'] . '%')
                        ->first();
                    $classId = $class?->id;
                }

                $student = Student::create([
                    'nis' => $data['nis'],
                    'nisn' => $data['nisn'] ?? null,
                    'nik' => $data['nik'] ?? null,
                    'no_kk' => $data['no_kk'] ?? null,
                    'no_akta_lahir' => $data['no_akta_lahir'] ?? null,
                    'nama_lengkap' => $data['nama_lengkap'],
                    'jenis_kelamin' => strtoupper($data['jenis_kelamin'] ?? 'L'),
                    'tempat_lahir' => $data['tempat_lahir'] ?? 'Banyumas',
                    'tgl_lahir' => $this->parseDate($data['tgl_lahir'] ?? '2018-01-01'),
                    'agama' => $agamaId,
                    'anak_ke' => !empty($data['anak_ke']) ? (int)$data['anak_ke'] : 1,
                    'jumlah_saudara' => !empty($data['jumlah_saudara']) ? (int)$data['jumlah_saudara'] : 0,
                    'berat_kg' => !empty($data['berat_kg']) ? (float)$data['berat_kg'] : null,
                    'tinggi_cm' => !empty($data['tinggi_cm']) ? (float)$data['tinggi_cm'] : null,
                    'jarak_ke_sekolah_km' => !empty($data['jarak_ke_sekolah_km']) ? (float)$data['jarak_ke_sekolah_km'] : null,
                    'alamat_jalan' => $data['alamat_jalan'] ?? '-',
                    'rt' => $data['rt'] ?? null,
                    'rw' => $data['rw'] ?? null,
                    'desa_kelurahan' => $data['desa_kelurahan'] ?? null,
                    'kecamatan' => $data['kecamatan'] ?? null,
                    'kab_kota' => $data['kab_kota'] ?? 'Banyumas',
                    'provinsi' => $data['provinsi'] ?? 'Jawa Tengah',
                    'kode_pos' => $data['kode_pos'] ?? null,
                    'tinggal_bersama' => $data['tinggal_bersama'] ?? 'orang_tua',
                    'transportasi' => $transportId,
                    'asal_sekolah_tk' => $data['asal_sekolah_tk'] ?? null,
                    'tgl_masuk' => $this->parseDate($data['tgl_masuk'] ?? '2026-07-15'),
                    'tahun_masuk' => 2026,
                    'class_id' => $classId,
                    'status' => 'aktif',
                ]);

                // Create enrollment in active academic year
                if ($classId) {
                    $activeYear = AcademicYear::getActive();
                    if ($activeYear) {
                        StudentEnrollment::firstOrCreate([
                            'student_id' => $student->id,
                            'academic_year_id' => $activeYear->id,
                        ], [
                            'class_id' => $classId,
                            'tgl_masuk_kelas' => $student->tgl_masuk,
                            'status_kelas' => 'aktif',
                        ]);
                    }
                }

                // Create primary guardian if parent info provided
                if (!empty($data['nama_ayah']) || !empty($data['nama_ibu'])) {
                    $guardianName = !empty($data['nama_ayah']) ? $data['nama_ayah'] : $data['nama_ibu'];
                    $guardianHubungan = !empty($data['nama_ayah']) ? 'ayah' : 'ibu';
                    $guardianNik = !empty($data['nama_ayah']) ? ($data['nik_ayah'] ?? null) : ($data['nik_ibu'] ?? null);

                    $guardian = Guardian::create([
                        'hubungan' => $guardianHubungan,
                        'nama_lengkap' => $guardianName,
                        'nik' => $guardianNik,
                        'no_hp' => $data['no_hp_wali'] ?? '+6281200000000',
                        'no_whatsapp' => $data['no_whatsapp_wali'] ?? ($data['no_hp_wali'] ?? null),
                        'created_by' => auth()->id(),
                    ]);

                    $student->guardians()->attach($guardian->id, [
                        'hubungan' => $guardianHubungan,
                        'is_kontak_utama' => true,
                        'urutan' => 1,
                    ]);
                }

                return ['action' => 'created', 'id' => $student->id];

            case 'teachers':
                $existing = Teacher::where('nik', $data['nik'])->first();
                if ($existing) {
                    if ($strategy === 'reject') {
                        throw new \InvalidArgumentException("Duplikat data guru NIK {$data['nik']}.");
                    }
                    if ($strategy === 'skip') {
                        return ['action' => 'skipped', 'id' => $existing->id];
                    }
                    $existing->update([
                        'nama_lengkap' => $data['nama_lengkap'] ?? $existing->nama_lengkap,
                        'nip' => !empty($data['nip']) && $data['nip'] !== '-' ? $data['nip'] : $existing->nip,
                        'nuptk' => !empty($data['nuptk']) && $data['nuptk'] !== '-' ? $data['nuptk'] : $existing->nuptk,
                        'email' => !empty($data['email']) ? $data['email'] : $existing->email,
                        'no_hp' => !empty($data['no_hp']) ? substr($data['no_hp'], 0, 16) : $existing->no_hp,
                    ]);
                    return ['action' => 'updated', 'id' => $existing->id];
                }

                $agamaId = $religions[strtolower($data['agama'] ?? '')] ?? 1;
                $pendidikanId = $educations[strtolower($data['pendidikan_terakhir'] ?? '')] ?? null;

                $jkRaw = strtoupper(trim((string)($data['jenis_kelamin'] ?? 'L')));
                $jk = str_starts_with($jkRaw, 'P') ? 'P' : 'L';

                $jenisPtk = !empty($data['jenis_ptk']) && $data['jenis_ptk'] !== '-' ? strtolower($data['jenis_ptk']) : 'guru_kelas';
                $statusKepegawaian = !empty($data['status_kepegawaian']) && $data['status_kepegawaian'] !== '-' ? strtolower($data['status_kepegawaian']) : 'gty';

                $teacher = Teacher::create([
                    'nip' => !empty($data['nip']) && $data['nip'] !== '-' ? $data['nip'] : null,
                    'nuptk' => !empty($data['nuptk']) && $data['nuptk'] !== '-' ? $data['nuptk'] : null,
                    'nik' => $data['nik'],
                    'nama_lengkap' => $data['nama_lengkap'],
                    'gelar_depan' => !empty($data['gelar_depan']) && $data['gelar_depan'] !== '-' ? $data['gelar_depan'] : null,
                    'gelar_belakang' => !empty($data['gelar_belakang']) && $data['gelar_belakang'] !== '-' ? $data['gelar_belakang'] : null,
                    'jenis_kelamin' => $jk,
                    'tempat_lahir' => !empty($data['tempat_lahir']) ? $data['tempat_lahir'] : 'Banyumas',
                    'tgl_lahir' => $this->parseDate($data['tgl_lahir'] ?? '1990-01-01') ?? '1990-01-01',
                    'agama' => $agamaId,
                    'alamat_jalan' => !empty($data['alamat_jalan']) ? $data['alamat_jalan'] : '-',
                    'kab_kota' => !empty($data['kab_kota']) ? $data['kab_kota'] : 'Banyumas',
                    'provinsi' => !empty($data['provinsi']) ? $data['provinsi'] : 'Jawa Tengah',
                    'no_hp' => !empty($data['no_hp']) ? substr($data['no_hp'], 0, 16) : '+6281200000000',
                    'email' => !empty($data['email']) ? $data['email'] : null,
                    'jenis_ptk' => $jenisPtk,
                    'status_kepegawaian' => $statusKepegawaian,
                    'tgl_mulai_tugas' => $this->parseDate($data['tgl_mulai_tugas'] ?? '2020-01-01') ?? '2020-01-01',
                    'jabatan' => !empty($data['jabatan']) && $data['jabatan'] !== '-' ? $data['jabatan'] : null,
                    'tugas_tambahan' => !empty($data['tugas_tambahan']) && $data['tugas_tambahan'] !== '-' ? $data['tugas_tambahan'] : null,
                    'pendidikan_terakhir' => $pendidikanId,
                    'jurusan_prodi' => !empty($data['jurusan_prodi']) ? $data['jurusan_prodi'] : null,
                    'institusi' => !empty($data['institusi']) ? $data['institusi'] : null,
                    'tahun_lulus' => !empty($data['tahun_lulus']) && is_numeric($data['tahun_lulus']) ? (int)$data['tahun_lulus'] : null,
                    'status_sertifikasi' => !empty($data['status_sertifikasi']) ? strtolower($data['status_sertifikasi']) : 'belum',
                    'status' => 'aktif',
                    'created_by' => auth()->id(),
                ]);

                return ['action' => 'created', 'id' => $teacher->id];

            case 'classes':
                $existing = SchoolClass::where('kode', $data['kode'])->first();
                if ($existing) {
                    if ($strategy === 'reject') {
                        throw new \InvalidArgumentException("Duplikat data kelas {$data['kode']}.");
                    }
                    if ($strategy === 'skip') {
                        return ['action' => 'skipped', 'id' => $existing->id];
                    }
                    $existing->update(['nama' => $data['nama']]);
                    return ['action' => 'updated', 'id' => $existing->id];
                }

                $activeYear = AcademicYear::getActive() ?? AcademicYear::first();
                $class = SchoolClass::create([
                    'academic_year_id' => $activeYear?->id ?? 1,
                    'kode' => $data['kode'],
                    'nama' => $data['nama'],
                    'tingkat' => (int) $data['tingkat'],
                    'paralel' => $data['paralel'] ?? null,
                    'kapasitas' => (int) ($data['kapasitas'] ?? 28),
                    'status' => 'aktif',
                    'created_by' => auth()->id(),
                ]);

                return ['action' => 'created', 'id' => $class->id];

            case 'subjects':
                $existing = Subject::where('kode', $data['kode'])->first();
                if ($existing) {
                    if ($strategy === 'reject') {
                        throw new \InvalidArgumentException("Duplikat data mapel {$data['kode']}.");
                    }
                    if ($strategy === 'skip') {
                        return ['action' => 'skipped', 'id' => $existing->id];
                    }
                    $existing->update(['nama' => $data['nama']]);
                    return ['action' => 'updated', 'id' => $existing->id];
                }

                $subject = Subject::create([
                    'kode' => $data['kode'],
                    'nama' => $data['nama'],
                    'kelompok' => strtolower($data['kelompok']),
                    'tingkat_berlaku' => $data['tingkat_berlaku'] ?? null,
                    'jam_per_minggu' => (int) ($data['jam_per_minggu'] ?? 2),
                    'kkm' => (int) ($data['kkm'] ?? 75),
                    'status' => 'aktif',
                    'created_by' => auth()->id(),
                ]);

                return ['action' => 'created', 'id' => $subject->id];

            default:
                throw new \InvalidArgumentException("Jenis import {$jenis} belum diimplementasikan.");
        }
    }

    private function parseDate(mixed $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        if (is_numeric($val)) {
            // Excel serial date number
            try {
                return Carbon::instance(Date::excelToDateTimeObject((float) $val))->format('Y-m-d');
            } catch (\Throwable) {
                // fall through
            }
        }

        try {
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function cleanCellValue(string $field, mixed $val): mixed
    {
        if ($val === null) {
            return null;
        }

        if (is_string($val)) {
            $val = trim($val);
            if ($val === '' || $val === '-') {
                return null;
            }
        }

        // Convert any scientific notation or large float numbers (e.g. 5.4277467523032E+14 or 542774675230323.0)
        if (is_numeric($val) || (is_string($val) && preg_match('/^[+-]?\d+(\.\d+)?[eE][+-]?\d+$/', trim($val)))) {
            if (in_array($field, ['nuptk', 'nik', 'nip', 'nis', 'nisn', 'no_kk', 'nik_ayah', 'nik_ibu', 'no_hp', 'no_hp_wali', 'no_whatsapp_wali', 'kode_pos', 'tahun_lulus', 'rt', 'rw', 'anak_ke', 'jumlah_saudara'])) {
                $val = number_format((float) $val, 0, '', '');
            }
        }

        // Length sanitization according to database schema
        if (is_string($val)) {
            if ($field === 'nuptk') {
                $val = substr(preg_replace('/[^0-9]/', '', $val), 0, 16);
                return $val !== '' ? $val : null;
            }
            if (in_array($field, ['nik', 'no_kk', 'nik_ayah', 'nik_ibu'])) {
                $val = substr(preg_replace('/[^0-9]/', '', $val), 0, 16);
                return $val !== '' ? $val : null;
            }
            if ($field === 'nisn') {
                $val = substr(preg_replace('/[^0-9]/', '', $val), 0, 10);
                return $val !== '' ? $val : null;
            }
            if ($field === 'nip') {
                $val = substr(preg_replace('/[^0-9]/', '', $val), 0, 18);
                return $val !== '' ? $val : null;
            }
            if (in_array($field, ['no_hp', 'no_hp_wali', 'no_whatsapp_wali'])) {
                $val = substr($val, 0, 16);
                return $val !== '' ? $val : null;
            }
        }

        return $val;
    }
}
