<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Services\Import\ExcelImportService;
use App\Services\Import\ExcelTemplateGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function __construct(
        protected ExcelImportService $importService
    ) {}

    public function downloadTemplate(string $jenis): StreamedResponse
    {
        return ExcelTemplateGenerator::generate($jenis);
    }

    public function validateFile(Request $request, string $jenis): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'], // max 10MB
            'column_mapping' => ['nullable', 'array'],
        ], [
            'file.required' => 'File Excel/CSV wajib diunggah.',
            'file.mimes' => 'Format file harus berupa .xlsx, .xls, atau .csv.',
        ]);

        $result = $this->importService->validate(
            $request->file('file'),
            $jenis,
            $request->input('column_mapping')
        );

        return response()->json([
            'success' => true,
            'message' => 'File import berhasil divalidasi.',
            'data' => $result,
        ]);
    }

    public function commit(Request $request, ImportBatch $batch): JsonResponse
    {
        $validated = $request->validate([
            'duplicate_strategy' => ['sometimes', 'in:skip,update,reject'],
        ]);

        $strategy = $validated['duplicate_strategy'] ?? 'skip';
        $result = $this->importService->commit($batch, $strategy);

        return response()->json([
            'success' => true,
            'message' => 'Data import berhasil disimpan ke basis data.',
            'data' => $result,
        ]);
    }

    public function rollback(ImportBatch $batch): JsonResponse
    {
        $result = $this->importService->rollback($batch);

        return response()->json([
            'success' => true,
            'message' => 'Batch import berhasil di-rollback.',
            'data' => $result,
        ]);
    }

    public function exportErrors(ImportBatch $batch): StreamedResponse
    {
        return $this->importService->exportErrors($batch);
    }

    public function batches(Request $request): JsonResponse
    {
        $batches = ImportBatch::with('user:id,nama,username')
            ->when($request->filled('jenis_data'), fn ($q) => $q->where('jenis_data', $request->query('jenis_data')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Daftar batch import berhasil diambil.',
            'data' => $batches->items(),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
            ],
        ]);
    }
}
