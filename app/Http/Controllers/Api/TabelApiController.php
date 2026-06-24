<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Endpoint khusus untuk halaman /tabel (DataTables server-side processing).
 *
 * Dipisah dari SpesiesApiController & ObservasiApiController karena format
 * request/response DataTables (draw, start, length, search, order) berbeda
 * dari endpoint GeoJSON yang dipakai peta. Menyatukannya di controller yang
 * sama akan membuat method-method itu penuh percabangan "kalau dari
 * DataTables vs kalau dari peta".
 */
class TabelApiController extends Controller
{
    public function faunaKhas(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $cari = (string) $request->input('search.value', '');

        // Kolom diwhitelist manual (bukan dari input request) agar tidak
        // bisa dipakai untuk ORDER BY kolom sembarang lewat parameter.
        $kolomBisaDiurutkan = ['nama_lokal', 'nama_ilmiah', 'kelas_taksonomi', 'status_konservasi', 'jumlah_provinsi'];
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderKolom = $kolomBisaDiurutkan[$orderIndex] ?? 'nama_lokal';
        $orderArah = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

        $query = DB::table('spesies')
            ->leftJoin('spesies_provinsi', 'spesies_provinsi.spesies_id', '=', 'spesies.id')
            ->select(
                'spesies.id',
                'spesies.nama_lokal',
                'spesies.nama_ilmiah',
                'spesies.kelas_taksonomi',
                'spesies.status_konservasi',
                'spesies.foto_referensi',
                DB::raw('COUNT(DISTINCT spesies_provinsi.provinsi_id) as jumlah_provinsi')
            )
            ->groupBy(
                'spesies.id', 'spesies.nama_lokal', 'spesies.nama_ilmiah',
                'spesies.kelas_taksonomi', 'spesies.status_konservasi', 'spesies.foto_referensi'
            );

        $totalKeseluruhan = DB::table('spesies')->count();

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('spesies.nama_lokal', 'ilike', "%{$cari}%")
                    ->orWhere('spesies.nama_ilmiah', 'ilike', "%{$cari}%")
                    ->orWhere('spesies.kelas_taksonomi', 'ilike', "%{$cari}%")
                    ->orWhere('spesies.status_konservasi', 'ilike', "%{$cari}%");
            });
        }

        $totalTersaring = $query->clone()->get()->count();

        $baris = $query->orderBy($orderKolom, $orderArah)
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $baris->map(function ($row) {
            return [
                'id' => $row->id,
                'nama_lokal' => $row->nama_lokal,
                'nama_ilmiah' => $row->nama_ilmiah,
                'kelas_taksonomi' => $row->kelas_taksonomi,
                'status_konservasi' => $row->status_konservasi,
                'jumlah_provinsi' => $row->jumlah_provinsi,
                'foto_url' => $row->foto_referensi
                    ? Storage::disk('public')->url($row->foto_referensi)
                    : null,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalKeseluruhan,
            'recordsFiltered' => $totalTersaring,
            'data' => $data,
        ]);
    }

    public function observasi(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $cari = (string) $request->input('search.value', '');

        $kolomBisaDiurutkan = ['nama', 'nama_pengamat', 'tanggal_amatan', 'nama_provinsi', 'status_verifikasi'];
        $orderIndex = (int) $request->input('order.0.column', 2);
        $orderKolom = $kolomBisaDiurutkan[$orderIndex] ?? 'tanggal_amatan';
        $orderArah = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

        // CATATAN: sama seperti ObservasiApiController::geoJson(), semua
        // status_verifikasi sengaja ditampilkan dulu di fase awal ini
        // (lihat catatan di sana). Saat verifikasi komunitas diaktifkan,
        // tambahkan ->where('observasi.status_verifikasi', 'terverifikasi')
        // di kedua tempat agar peta & tabel konsisten.
        $base = DB::table('observasi')
            ->join('users', 'observasi.user_id', '=', 'users.id')
            ->leftJoin('spesies', 'observasi.spesies_id', '=', 'spesies.id')
            ->leftJoin('provinsi', 'observasi.provinsi_id', '=', 'provinsi.id')
            ->selectRaw("observasi.id, observasi.foto, observasi.tanggal_amatan, observasi.status_verifikasi,
                users.name as nama_pengamat,
                provinsi.nama_provinsi,
                COALESCE(spesies.nama_lokal, observasi.nama_usulan, 'Belum diketahui') as nama,
                spesies.nama_ilmiah");

        $totalKeseluruhan = DB::table('observasi')->count();

        if ($cari !== '') {
            $base->where(function ($q) use ($cari) {
                $q->where('users.name', 'ilike', "%{$cari}%")
                    ->orWhere('provinsi.nama_provinsi', 'ilike', "%{$cari}%")
                    ->orWhere('spesies.nama_lokal', 'ilike', "%{$cari}%")
                    ->orWhere('observasi.nama_usulan', 'ilike', "%{$cari}%")
                    ->orWhere('observasi.status_verifikasi', 'ilike', "%{$cari}%");
            });
        }

        $totalTersaring = $base->clone()->get()->count();

        $kolomUntukOrder = $orderKolom === 'nama_provinsi' ? 'provinsi.nama_provinsi' : $orderKolom;

        $baris = $base->orderBy($kolomUntukOrder, $orderArah)
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $baris->map(function ($row) {
            return [
                'id' => $row->id,
                'nama' => $row->nama,
                'nama_ilmiah' => $row->nama_ilmiah,
                'nama_pengamat' => $row->nama_pengamat,
                'tanggal_amatan' => $row->tanggal_amatan,
                'nama_provinsi' => $row->nama_provinsi ?? '—',
                'status_verifikasi' => $row->status_verifikasi,
                'foto_url' => $row->foto
                    ? Storage::disk('public')->url('observasi/' . $row->foto)
                    : null,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalKeseluruhan,
            'recordsFiltered' => $totalTersaring,
            'data' => $data,
        ]);
    }
}
