<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/peta', [PageController::class, 'peta'])->name('peta');
Route::get('/tabel', [PageController::class, 'tabel'])->name('tabel');

// Statistik komunitas yang dulu ada di /dashboard sekarang digabung ke
// beranda (lihat PageController::home()) agar navbar tidak terlalu panjang.
// Redirect ditaruh di sini supaya link/bookmark lama tidak 404.
Route::redirect('/dashboard', '/', 301);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/lapor', [PageController::class, 'lapor'])->name('lapor');
    Route::get('/kuis', [PageController::class, 'kuis'])->name('kuis');
    Route::get('/papan-skor', [PageController::class, 'papanSkor'])->name('papan-skor');
    Route::get('/papan-skor/{pengamat}', [PageController::class, 'riwayatPengamat'])->name('riwayat-pengamat');
});

require __DIR__ . '/auth.php';
