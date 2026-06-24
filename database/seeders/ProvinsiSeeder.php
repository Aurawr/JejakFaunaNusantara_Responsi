<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinsiSeeder extends Seeder
{
    public function run(): void
    {
        $asiatis = [
            'ACEH', 'SUMATERA UTARA', 'SUMATERA BARAT', 'RIAU', 'KEPULAUAN RIAU',
            'JAMBI', 'SUMATERA SELATAN', 'BENGKULU', 'LAMPUNG',
            'KEPULAUAN BANGKA BELITUNG', 'BANTEN', 'DKI JAKARTA', 'JAWA BARAT',
            'JAWA TENGAH', 'DAERAH ISTIMEWA YOGYAKARTA', 'JAWA TIMUR',
            'KALIMANTAN BARAT', 'KALIMANTAN TENGAH', 'KALIMANTAN SELATAN',
            'KALIMANTAN TIMUR', 'KALIMANTAN UTARA', 'BALI',
        ];

        $peralihan = [
            'SULAWESI UTARA', 'SULAWESI TENGAH', 'SULAWESI SELATAN',
            'SULAWESI TENGGARA', 'GORONTALO', 'SULAWESI BARAT',
            'NUSA TENGGARA BARAT', 'NUSA TENGGARA TIMUR', 'MALUKU', 'MALUKU UTARA',
        ];

        $australis = [
            'PAPUA', 'PAPUA BARAT', 'PAPUA SELATAN', 'PAPUA TENGAH',
            'PAPUA PEGUNUNGAN', 'PAPUA BARAT DAYA',
        ];

        $semua = [];

        foreach ($asiatis as $nama) {
            $semua[] = ['nama' => $nama, 'zona' => 'asiatis'];
        }
        foreach ($peralihan as $nama) {
            $semua[] = ['nama' => $nama, 'zona' => 'peralihan'];
        }
        foreach ($australis as $nama) {
            $semua[] = ['nama' => $nama, 'zona' => 'australis'];
        }

        foreach ($semua as $p) {
            DB::table('provinsi')->insert([
                'nama_provinsi' => $p['nama'],
                'zona_biogeografi' => $p['zona'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
