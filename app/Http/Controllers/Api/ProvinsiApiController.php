<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provinsi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProvinsiApiController extends Controller
{
    public function wfsProxy(): \Illuminate\Http\Response
    {
        // Pendekatan pass-through: response dari GeoServer disalurkan
        // langsung ke browser tanpa pernah di-decode jadi array PHP atau
        // di-encode ulang jadi JSON. Ini menghindari PHP harus menanggung
        // beban memori untuk memahami struktur datanya — sama seperti pola
        // `echo file_get_contents($url)` pada kode project lama, hanya saja
        // di sini memakai Http client Laravel dan streaming body secara
        // langsung, bukan memuat seluruh isi ke memori sekaligus.
        $url = config('services.geoserver.url')
            . '?service=WFS&version=1.0.0&request=GetFeature'
            . '&typeName=' . config('services.geoserver.workspace') . ':' . config('services.geoserver.layer_provinsi')
            . '&outputFormat=application/json';

        $response = Http::timeout(60)->get($url);

        if ($response->failed()) {
            return response('{"error":"GeoServer tidak dapat dihubungi"}', 502)
                ->header('Content-Type', 'application/json');
        }

        // ->body() mengambil string mentah tanpa proses decode JSON sama
        // sekali, lalu dikirim langsung sebagai response Laravel.
        return response($response->body(), 200)
            ->header('Content-Type', 'application/json');
    }

    public function geoJson(): JsonResponse
    {
        $rows = DB::table('provinsi')
            ->select('id', 'nama_provinsi', 'zona_biogeografi', DB::raw('ST_AsGeoJSON(geom_polygon) as geojson'))
            ->get();

        $features = $rows->map(function ($row) {
            return [
                'type' => 'Feature',
                'geometry' => json_decode($row->geojson),
                'properties' => [
                    'id' => $row->id,
                    'nama_provinsi' => $row->nama_provinsi,
                    'zona_biogeografi' => $row->zona_biogeografi,
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ], 200, [], JSON_NUMERIC_CHECK);
    }

    public function rekap(int $id): JsonResponse
    {
        $provinsi = Provinsi::with('spesies')->findOrFail($id);

        $jumlahObservasi = $provinsi->observasi()->where('status_verifikasi', 'terverifikasi')->count();

        return response()->json([
            'nama_provinsi' => $provinsi->nama_provinsi,
            'jumlah_spesies_khas' => $provinsi->spesies->count(),
            'jumlah_observasi_warga' => $jumlahObservasi,
            'spesies' => $provinsi->spesies,
        ]);
    }

    public function cariByNama(Request $request): JsonResponse
    {
        // Dipakai map.blade.php saat memakai wfs-proxy: GeoServer hanya
        // mengirim properti nama provinsi (PROVINSI/NAMOBJ), bukan id
        // provinsi lokal. Endpoint ini mencocokkan dari nama, sebagai
        // pengganti rekap($id) yang membutuhkan id langsung.
        $nama = trim(mb_strtoupper($request->query('nama', '')));

        $provinsi = Provinsi::where('nama_provinsi', $nama)->with('spesies')->first();

        if (! $provinsi) {
            return response()->json([
                'id' => null,
                'nama_provinsi' => $nama,
                'jumlah_spesies_khas' => 0,
                'jumlah_observasi_warga' => 0,
                'spesies' => [],
            ]);
        }

        $jumlahObservasi = $provinsi->observasi()->where('status_verifikasi', 'terverifikasi')->count();

        return response()->json([
            'id' => $provinsi->id,
            'nama_provinsi' => $provinsi->nama_provinsi,
            'jumlah_spesies_khas' => $provinsi->spesies->count(),
            'jumlah_observasi_warga' => $jumlahObservasi,
            'spesies' => $provinsi->spesies,
        ]);
    }

    public function titikFaunaKhasGeoJson(): JsonResponse
    {
        // Titik representatif fauna khas (dari migrasi data lama), berbeda
        // dari layer observasi warga. Ini menampilkan satu titik contoh
        // habitat per fauna khas, bukan hasil pengamatan langsung warga.
        $rows = DB::table('spesies_provinsi')
            ->join('spesies', 'spesies_provinsi.spesies_id', '=', 'spesies.id')
            ->join('provinsi', 'spesies_provinsi.provinsi_id', '=', 'provinsi.id')
            ->select(
                'spesies.id as spesies_id', 'spesies.nama_lokal', 'spesies.nama_ilmiah',
                'spesies.foto_referensi', 'spesies.status_konservasi',
                'provinsi.nama_provinsi',
                'spesies_provinsi.deskripsi',
                DB::raw('ST_AsGeoJSON(spesies_provinsi.geom_titik_referensi) as geojson')
            )
            ->whereNotNull('spesies_provinsi.geom_titik_referensi')
            ->get();

        // deskripsi disertakan di sini (bukan cuma lewat /provinsi/cari) supaya
        // sidebar titik fauna khas di peta tidak perlu request tambahan saat
        // marker-nya diklik langsung.
        $features = $rows->map(function ($row) {
            return [
                'type' => 'Feature',
                'geometry' => json_decode($row->geojson),
                'properties' => [
                    'spesies_id' => $row->spesies_id,
                    'nama_lokal' => $row->nama_lokal,
                    'nama_ilmiah' => $row->nama_ilmiah,
                    'foto_referensi' => $row->foto_referensi,
                    'status_konservasi' => $row->status_konservasi,
                    'nama_provinsi' => $row->nama_provinsi,
                    'deskripsi' => $row->deskripsi,
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ], 200, [], JSON_NUMERIC_CHECK);
    }
}
