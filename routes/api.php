<?php

use App\Http\Controllers\Api\KuisApiController;
use App\Http\Controllers\Api\ObservasiApiController;
use App\Http\Controllers\Api\ProvinsiApiController;
use App\Http\Controllers\Api\SpesiesApiController;
use App\Http\Controllers\Api\TabelApiController;
use Illuminate\Support\Facades\Route;

// Layer provinsi (polygon dan polyline memakai endpoint geojson yang sama)
Route::get('/provinsi/wfs-proxy', [ProvinsiApiController::class, 'wfsProxy']);
Route::get('/provinsi/geojson', [ProvinsiApiController::class, 'geoJson']);
Route::get('/provinsi/cari', [ProvinsiApiController::class, 'cariByNama']);
Route::get('/provinsi/{id}/rekap', [ProvinsiApiController::class, 'rekap']);

// Layer fauna khas (database lama)
Route::get('/spesies', [SpesiesApiController::class, 'index']);
Route::get('/spesies/titik-geojson', [ProvinsiApiController::class, 'titikFaunaKhasGeoJson']);
Route::get('/spesies/{id}', [SpesiesApiController::class, 'show']);

// Layer observasi warga
Route::get('/observasi/geojson', [ObservasiApiController::class, 'geoJson']);

// Halaman /tabel (DataTables server-side processing, format draw/start/length)
Route::get('/tabel/fauna-khas', [TabelApiController::class, 'faunaKhas']);
Route::get('/tabel/observasi', [TabelApiController::class, 'observasi']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/observasi/{id}', [ObservasiApiController::class, 'show']);
    Route::post('/observasi', [ObservasiApiController::class, 'store']);
    Route::patch('/observasi/{id}', [ObservasiApiController::class, 'update']);
    Route::delete('/observasi/{id}', [ObservasiApiController::class, 'destroy']);
    Route::patch('/observasi/{id}/verifikasi', [ObservasiApiController::class, 'verifikasi']);

    Route::get('/kuis/soal', [KuisApiController::class, 'soal']);
    Route::post('/kuis/soal/{id}/jawab', [KuisApiController::class, 'jawab']);
    Route::post('/kuis/selesai', [KuisApiController::class, 'selesai']);
});
