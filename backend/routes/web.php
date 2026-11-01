<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $indexPath = public_path('index.html');
    if (file_exists($indexPath)) {
        return response()->file($indexPath);
    }
    $cpanelIndexPath = base_path('../public_html/index.html');
    if (file_exists($cpanelIndexPath)) {
        return response()->file($cpanelIndexPath);
    }
    return redirect('/index.html');
});

/**
 * Route penyaji file storage otomatis (Solusi Hosting tanpa Symlink).
 * Membaca langsung dari storage/app/public tanpa memerlukan symlink di cPanel.
 */
Route::get('storage/{path}', function ($path) {
    $path = ltrim($path, '/');
    $basePath = realpath(storage_path('app/public'));
    $filePath = realpath(storage_path('app/public/' . $path));

    if (!$filePath || !str_starts_with($filePath, $basePath) || !is_file($filePath)) {
        abort(404);
    }

    return response()->file($filePath);
})->where('path', '.*');

Route::fallback(function () {
    $indexPath = public_path('index.html');
    if (file_exists($indexPath)) {
        return response()->file($indexPath);
    }
    $cpanelIndexPath = base_path('../public_html/index.html');
    if (file_exists($cpanelIndexPath)) {
        return response()->file($cpanelIndexPath);
    }
    return redirect('/index.html');
});
