<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Education;
use App\Models\IncomeRange;
use App\Models\Occupation;
use App\Models\Religion;
use App\Models\Role;
use App\Models\Transport;
use Illuminate\Http\JsonResponse;

class ReferenceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Data referensi berhasil diambil.',
            'data' => [
                'religions' => Religion::orderBy('urutan')->get(['id', 'nama']),
                'education' => Education::orderBy('urutan')->get(['id', 'nama']),
                'occupations' => Occupation::orderBy('urutan')->get(['id', 'nama']),
                'income_ranges' => IncomeRange::orderBy('urutan')->get(['id', 'nama']),
                'transports' => Transport::orderBy('urutan')->get(['id', 'nama']),
                'roles' => Role::all(['id', 'nama']),
            ],
        ]);
    }
}
