<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CmsAchievement;
use App\Models\CmsArticle;
use App\Models\CmsGallery;
use App\Services\ActivityLogService;
use App\Services\ImageCompressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CmsController extends Controller
{
    // ==========================================
    // PUBLIC APIS (Tampil di Landing Page)
    // ==========================================

    public function getPublicGalleries(): JsonResponse
    {
        $galleries = CmsGallery::orderByDesc('created_at')->get();
        return response()->json([
            'success' => true,
            'message' => 'Daftar galeri kegiatan berhasil diambil.',
            'data' => $galleries,
        ]);
    }

    public function getPublicAchievements(): JsonResponse
    {
        $achievements = CmsAchievement::orderByDesc('created_at')->get();
        return response()->json([
            'success' => true,
            'message' => 'Daftar prestasi siswa berhasil diambil.',
            'data' => $achievements,
        ]);
    }

    public function getPublicArticles(): JsonResponse
    {
        $articles = CmsArticle::orderByDesc('created_at')->get();
        return response()->json([
            'success' => true,
            'message' => 'Daftar warta & artikel berhasil diambil.',
            'data' => $articles,
        ]);
    }

    // ==========================================
    // ADMIN CMS: 1. GALERI KEGIATAN SISWA
    // (Foto + Jenis Kegiatan + Waktu Posting)
    // ==========================================

    public function indexGalleries(): JsonResponse
    {
        $galleries = CmsGallery::with('creator:id,name')->orderByDesc('created_at')->paginate(20);
        return response()->json([
            'success' => true,
            'message' => 'Data galeri berhasil diambil.',
            'data' => $galleries,
        ]);
    }

    public function storeGallery(Request $request): JsonResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'], // Mendukung s/d 10MB karena akan dikompresi otomatis
            'activity_type' => ['required', 'string', 'max:100'],
        ]);

        $path = ImageCompressionService::compressAndStore($request->file('foto'), 'cms/galleries', 1400, 82);

        $gallery = CmsGallery::create([
            'image_path' => $path,
            'activity_type' => $request->input('activity_type'),
            'created_by' => auth()->id(),
        ]);

        ActivityLogService::log(
            aksi: 'tambah_galeri_cms',
            modul: 'cms',
            targetType: CmsGallery::class,
            targetId: $gallery->id,
            dataSebelum: null,
            dataSesudah: $gallery->toArray(),
            keterangan: 'Admin menambahkan foto baru ke galeri kegiatan siswa: ' . $gallery->activity_type
        );

        return response()->json([
            'success' => true,
            'message' => 'Foto kegiatan berhasil dikompresi dan ditambahkan ke galeri.',
            'data' => $gallery,
        ], 201);
    }

    public function destroyGallery(int $id): JsonResponse
    {
        $gallery = CmsGallery::findOrFail($id);

        if ($gallery->image_path && Storage::disk('public')->exists($gallery->image_path)) {
            Storage::disk('public')->delete($gallery->image_path);
        }

        $old = $gallery->toArray();
        $gallery->delete();

        ActivityLogService::log(
            aksi: 'hapus_galeri_cms',
            modul: 'cms',
            targetType: CmsGallery::class,
            targetId: $id,
            dataSebelum: $old,
            dataSesudah: null,
            keterangan: 'Admin menghapus foto dari galeri kegiatan.'
        );

        return response()->json([
            'success' => true,
            'message' => 'Foto kegiatan berhasil dihapus.',
        ]);
    }

    // ==========================================
    // ADMIN CMS: 2. PRESTASI SISWA
    // (Foto + Judul + Waktu Posting)
    // ==========================================

    public function indexAchievements(): JsonResponse
    {
        $achievements = CmsAchievement::with('creator:id,name')->orderByDesc('created_at')->paginate(20);
        return response()->json([
            'success' => true,
            'message' => 'Data prestasi berhasil diambil.',
            'data' => $achievements,
        ]);
    }

    public function storeAchievement(Request $request): JsonResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'title' => ['required', 'string', 'max:255'],
        ]);

        $path = ImageCompressionService::compressAndStore($request->file('foto'), 'cms/achievements', 1400, 82);

        $achievement = CmsAchievement::create([
            'title' => $request->input('title'),
            'image_path' => $path,
            'created_by' => auth()->id(),
        ]);

        ActivityLogService::log(
            aksi: 'tambah_prestasi_cms',
            modul: 'cms',
            targetType: CmsAchievement::class,
            targetId: $achievement->id,
            dataSebelum: null,
            dataSesudah: $achievement->toArray(),
            keterangan: 'Admin menambahkan prestasi siswa baru: ' . $achievement->title
        );

        return response()->json([
            'success' => true,
            'message' => 'Prestasi siswa berhasil dikompresi dan ditambahkan.',
            'data' => $achievement,
        ], 201);
    }

    public function destroyAchievement(int $id): JsonResponse
    {
        $achievement = CmsAchievement::findOrFail($id);

        if ($achievement->image_path && Storage::disk('public')->exists($achievement->image_path)) {
            Storage::disk('public')->delete($achievement->image_path);
        }

        $old = $achievement->toArray();
        $achievement->delete();

        ActivityLogService::log(
            aksi: 'hapus_prestasi_cms',
            modul: 'cms',
            targetType: CmsAchievement::class,
            targetId: $id,
            dataSebelum: $old,
            dataSesudah: null,
            keterangan: 'Admin menghapus prestasi siswa.'
        );

        return response()->json([
            'success' => true,
            'message' => 'Prestasi siswa berhasil dihapus.',
        ]);
    }

    // ==========================================
    // ADMIN CMS: 3. WARTA & ARTIKEL SEKOLAH
    // (Foto + Judul + Keterangan + Waktu Posting)
    // ==========================================

    public function indexArticles(): JsonResponse
    {
        $articles = CmsArticle::with('creator:id,name')->orderByDesc('created_at')->paginate(20);
        return response()->json([
            'success' => true,
            'message' => 'Data artikel berhasil diambil.',
            'data' => $articles,
        ]);
    }

    public function storeArticle(Request $request): JsonResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $path = ImageCompressionService::compressAndStore($request->file('foto'), 'cms/articles', 1400, 82);

        $article = CmsArticle::create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'image_path' => $path,
            'created_by' => auth()->id(),
        ]);

        ActivityLogService::log(
            aksi: 'tambah_artikel_cms',
            modul: 'cms',
            targetType: CmsArticle::class,
            targetId: $article->id,
            dataSebelum: null,
            dataSesudah: $article->toArray(),
            keterangan: 'Admin menambahkan warta/artikel baru: ' . $article->title
        );

        return response()->json([
            'success' => true,
            'message' => 'Warta & artikel berhasil dipublikasikan.',
            'data' => $article,
        ], 201);
    }

    public function destroyArticle(int $id): JsonResponse
    {
        $article = CmsArticle::findOrFail($id);

        if ($article->image_path && Storage::disk('public')->exists($article->image_path)) {
            Storage::disk('public')->delete($article->image_path);
        }

        $old = $article->toArray();
        $article->delete();

        ActivityLogService::log(
            aksi: 'hapus_artikel_cms',
            modul: 'cms',
            targetType: CmsArticle::class,
            targetId: $id,
            dataSebelum: $old,
            dataSesudah: null,
            keterangan: 'Admin menghapus warta/artikel sekolah.'
        );

        return response()->json([
            'success' => true,
            'message' => 'Warta & artikel berhasil dihapus.',
        ]);
    }
}
