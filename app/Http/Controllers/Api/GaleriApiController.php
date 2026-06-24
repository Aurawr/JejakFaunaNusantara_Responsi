<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Endpoint untuk halaman /galeri. Menggabungkan fauna khas (database lama)
 * dan observasi warga jadi satu daftar tunggal lewat UNION di level SQL,
 * mengikuti pola "Observations" iNaturalist yang tidak memisah sumber data
 * — pengunjung melihat satu kumpulan foto fauna, bukan dua kategori.
 *
 * Dipisah dari TabelApiController karena formatnya beda (pagination grid
 * sederhana, bukan format draw/start/length DataTables) dan tujuannya beda
 * (jelajah visual lewat foto, bukan tabel data tabular).
 */
class GaleriApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $halaman = max((int) $request->input('halaman', 1), 1);
        $perHalaman = 12;
        $cari = trim((string) $request->input('cari', ''));

        $faunaKhas = DB::table('spesies')
            ->leftJoin('spesies_provinsi', 'spesies_provinsi.spesies_id', '=', 'spesies.id')
            ->leftJoin('provinsi', 'provinsi.id', '=', 'spesies_provinsi.provinsi_id')
            ->selectRaw("
                'fauna_khas' as tipe,
                spesies.id as id,
                spesies.nama_lokal as nama,
                spesies.nama_ilmiah as nama_ilmiah,
                spesies.status_konservasi as status_konservasi,
                spesies.foto_referensi as foto,
                provinsi.nama_provinsi as nama_provinsi,
                NULL as nama_pengamat,
                NULL as tanggal_amatan,
                spesies.created_at as urutan_waktu
            ")
            ->whereNotNull('spesies.foto_referensi')
            ->groupBy(
                'spesies.id', 'spesies.nama_lokal', 'spesies.nama_ilmiah',
                'spesies.status_konservasi', 'spesies.foto_referensi',
                'provinsi.nama_provinsi', 'spesies.created_at'
            );

        // Catatan sama seperti ObservasiApiController::geoJson() dan
        // TabelApiController::observasi(): semua status_verifikasi sengaja
        // ditampilkan dulu di fase awal ini (verifikasi komunitas belum
        // diaktifkan). Tambahkan ->where('status_verifikasi', 'terverifikasi')
        // di sini juga saat fitur itu diaktifkan kembali.
        $observasiWarga = DB::table('observasi')
            ->join('users', 'observasi.user_id', '=', 'users.id')
            ->leftJoin('spesies', 'observasi.spesies_id', '=', 'spesies.id')
            ->leftJoin('provinsi', 'observasi.provinsi_id', '=', 'provinsi.id')
            ->selectRaw("
                'observasi' as tipe,
                observasi.id as id,
                COALESCE(spesies.nama_lokal, observasi.nama_usulan, 'Belum diketahui') as nama,
                spesies.nama_ilmiah as nama_ilmiah,
                observasi.status_verifikasi as status_konservasi,
                observasi.foto as foto,
                provinsi.nama_provinsi as nama_provinsi,
                users.name as nama_pengamat,
                observasi.tanggal_amatan as tanggal_amatan,
                observasi.created_at as urutan_waktu
            ")
            ->whereNotNull('observasi.foto');

        if ($cari !== '') {
            $faunaKhas->where(function ($q) use ($cari) {
                $q->where('spesies.nama_lokal', 'ilike', "%{$cari}%")
                    ->orWhere('spesies.nama_ilmiah', 'ilike', "%{$cari}%");
            });

            $observasiWarga->where(function ($q) use ($cari) {
                $q->where('spesies.nama_lokal', 'ilike', "%{$cari}%")
                    ->orWhere('observasi.nama_usulan', 'ilike', "%{$cari}%");
            });
        }

        $gabungan = $faunaKhas->unionAll($observasiWarga);

        $totalData = DB::query()->fromSub($gabungan, 'g')->count();

        $baris = DB::query()->fromSub($gabungan, 'g')
            ->orderByDesc('urutan_waktu')
            ->offset(($halaman - 1) * $perHalaman)
            ->limit($perHalaman)
            ->get();

        $data = $baris->map(function ($row) {
            $folderFoto = $row->tipe === 'fauna_khas' ? 'referensi' : 'observasi';

            return [
                'tipe' => $row->tipe,
                'id' => $row->id,
                'nama' => $row->nama,
                'nama_ilmiah' => $row->nama_ilmiah,
                'status' => $row->status_konservasi,
                'nama_provinsi' => $row->nama_provinsi,
                'nama_pengamat' => $row->nama_pengamat,
                'tanggal_amatan' => $row->tanggal_amatan,
                'foto_url' => $row->foto
                    ? Storage::disk('public')->url(
                        str_starts_with($row->foto, 'referensi/') ||
                        str_starts_with($row->foto, 'observasi/')
                            ? $row->foto
                            : $folderFoto . '/' . $row->foto
                )
                : null,
            ];
        });

        return response()->json([
            'data' => $data,
            'halaman_saat_ini' => $halaman,
            'total_halaman' => (int) ceil($totalData / $perHalaman),
            'total_data' => $totalData,
        ]);
    }

    public function show(string $tipe, int $id): JsonResponse
    {
        if ($tipe === 'fauna_khas') {
            return $this->detailFaunaKhas($id);
        }

        if ($tipe === 'observasi') {
            return $this->detailObservasi($id);
        }

        return response()->json(['message' => 'Tipe data tidak dikenali.'], 404);
    }

    private function detailFaunaKhas(int $id): JsonResponse
    {
        $spesies = DB::table('spesies')->where('id', $id)->first();

        if (! $spesies) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        $provinsiTerkait = DB::table('spesies_provinsi')
            ->join('provinsi', 'provinsi.id', '=', 'spesies_provinsi.provinsi_id')
            ->where('spesies_provinsi.spesies_id', $id)
            ->select('provinsi.nama_provinsi', 'spesies_provinsi.deskripsi')
            ->get();

        return response()->json([
            'tipe' => 'fauna_khas',
            'id' => $spesies->id,
            'nama' => $spesies->nama_lokal,
            'nama_ilmiah' => $spesies->nama_ilmiah,
            'kelas_taksonomi' => $spesies->kelas_taksonomi,
            'status' => $spesies->status_konservasi,
            'foto_url' => $spesies->foto_referensi
                ? Storage::disk('public')->url($spesies->foto_referensi)
                : null,
            'provinsi_terkait' => $provinsiTerkait,
        ]);
    }

    private function detailObservasi(int $id): JsonResponse
    {
        $row = DB::table('observasi')
            ->join('users', 'observasi.user_id', '=', 'users.id')
            ->leftJoin('spesies', 'observasi.spesies_id', '=', 'spesies.id')
            ->leftJoin('provinsi', 'observasi.provinsi_id', '=', 'provinsi.id')
            ->where('observasi.id', $id)
            ->selectRaw("
                observasi.id, observasi.foto, observasi.tanggal_amatan, observasi.catatan,
                observasi.status_verifikasi, observasi.nama_usulan,
                users.name as nama_pengamat, users.id as user_id,
                provinsi.nama_provinsi,
                spesies.nama_lokal, spesies.nama_ilmiah
            ")
            ->first();

        if (! $row) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        $titik = DB::table('observasi')
            ->selectRaw('ST_AsGeoJSON(geom_point) as geojson')
            ->where('id', $id)
            ->first();

        return response()->json([
            'tipe' => 'observasi',
            'id' => $row->id,
            'nama' => $row->nama_lokal ?? $row->nama_usulan ?? 'Belum diketahui',
            'nama_ilmiah' => $row->nama_ilmiah,
            'status' => $row->status_verifikasi,
            'foto_url' => $row->foto ? Storage::disk('public')->url('observasi/' . $row->foto) : null,
            'nama_pengamat' => $row->nama_pengamat,
            'tanggal_amatan' => $row->tanggal_amatan,
            'nama_provinsi' => $row->nama_provinsi,
            'catatan' => $row->catatan,
            'geometry' => $titik && $titik->geojson ? json_decode($titik->geojson) : null,
        ]);
    }
}
