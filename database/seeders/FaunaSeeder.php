<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaunaSeeder extends Seeder
{
    public function run(): void
    {
        $provinsiId = DB::table('provinsi')->insertGetId([
            'nama_provinsi' => 'Sumatera Utara',
            'zona_biogeografi' => 'asiatis',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $spesiesId = DB::table('spesies')->insertGetId([
            'nama_ilmiah' => 'Panthera tigris sumatrae',
            'nama_lokal' => 'Harimau Sumatra',
            'kelas_taksonomi' => 'mamalia',
            'status_konservasi' => 'dilindungi',
            'foto_referensi' => 'referensi/harimau-sumatra.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('spesies_provinsi')->insert([
            'spesies_id' => $spesiesId,
            'provinsi_id' => $provinsiId,
            'deskripsi' => 'Subspesies harimau endemik Sumatra, populasi liar terus menurun akibat fragmentasi habitat.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('kuis_soal')->insert([
            'spesies_id' => $spesiesId,
            'observasi_id' => null,
            'tipe_soal' => 'edukasi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
