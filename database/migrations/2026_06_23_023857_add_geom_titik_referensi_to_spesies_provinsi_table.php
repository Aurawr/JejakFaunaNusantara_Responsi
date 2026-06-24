<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom ini menyimpan titik representatif fauna khas per provinsi
        // (berasal dari data lat/lng pada tabel fauna lama), berbeda secara
        // konsep dari geom_point di tabel observasi yang merupakan laporan
        // warga. Titik di sini adalah satu lokasi referensi/contoh habitat,
        // bukan hasil pengamatan langsung seperti observasi.
        DB::statement('ALTER TABLE spesies_provinsi ADD COLUMN geom_titik_referensi geometry(Point, 4326) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE spesies_provinsi DROP COLUMN IF EXISTS geom_titik_referensi');
    }
};
