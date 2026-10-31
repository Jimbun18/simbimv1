<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Route penyaji file storage otomatis (Solusi Hosting tanpa Symlink).
 * Membaca langsung dari storage/app/public tanpa memerlukan symlink di cPanel.
 */
Route::get('storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    if (!file_exists($filePath)) {
        abort(404);
    }
    return response()->file($filePath);
})->where('path', '.*');

