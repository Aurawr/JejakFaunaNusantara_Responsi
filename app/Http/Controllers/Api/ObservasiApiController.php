<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Observasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ObservasiApiController extends Controller
{
    public function geoJson(Request $request): JsonResponse
    {
        // CATATAN FASE AWAL: filter status_verifikasi sengaja dilonggarkan
        // (semua status ditampilkan, bukan hanya 'terverifikasi') karena
        // proses verifikasi komunitas belum diaktifkan untuk versi awal
        // projek ini. Kolom status_verifikasi tetap diisi 'pending' saat
        // observasi baru dibuat (lihat method store()), sehingga ketika
        // verifikasi diaktifkan kembali nanti, cukup kembalikan baris
        // ->where('observasi.status_verifikasi', 'terverifikasi') di bawah.
        $query = DB::table('observasi')
            ->join('users', 'observasi.user_id', '=', 'users.id')
            ->leftJoin('spesies', 'observasi.spesies_id', '=', 'spesies.id')
            ->select(
                'observasi.id', 'observasi.foto', 'observasi.tanggal_amatan',
                'observasi.status_verifikasi', 'observasi.nama_usulan', 'observasi.user_id',
                'users.name as nama_pengamat',
                'spesies.nama_lokal', 'spesies.nama_ilmiah',
                DB::raw('ST_AsGeoJSON(observasi.geom_point) as geojson')
            );

        if ($request->filled('provinsi_id')) {
            $query->where('observasi.provinsi_id', $request->input('provinsi_id'));
        }

        $rows = $query->get();

        $features = $rows->map(function ($row) {
            return [
                'type' => 'Feature',
                'geometry' => json_decode($row->geojson),
                'properties' => [
                    'id' => $row->id,
                    'user_id' => $row->user_id,
                    'nama' => $row->nama_lokal ?? $row->nama_usulan ?? 'Belum diketahui',
                    'nama_ilmiah' => $row->nama_ilmiah,
                    'foto' => $row->foto,
                    'tanggal_amatan' => $row->tanggal_amatan,
                    'nama_pengamat' => $row->nama_pengamat,
                    'status_verifikasi' => $row->status_verifikasi,
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ], 200, [], JSON_NUMERIC_CHECK);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'geometry_point' => 'required|string',
            'nama_usulan' => 'nullable|string|max:255',
            'spesies_id' => 'nullable|exists:spesies,id',
            'provinsi_id' => 'nullable|exists:provinsi,id',
            'tanggal_amatan' => 'required|date',
            'sumber_lokasi' => 'required|in:gps_realtime,klik_manual',
            'catatan' => 'nullable|string|max:1000',
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $namaFile = time() . '_observasi.' . $request->file('foto')->extension();
        $request->file('foto')->storeAs('observasi', $namaFile, 'public');

        $id = DB::table('observasi')->insertGetId([
            'user_id' => Auth::id(),
            'spesies_id' => $validated['spesies_id'] ?? null,
            'provinsi_id' => $validated['provinsi_id'] ?? null,
            'nama_usulan' => $validated['nama_usulan'] ?? null,
            'foto' => $namaFile,
            'tanggal_amatan' => $validated['tanggal_amatan'],
            'sumber_lokasi' => $validated['sumber_lokasi'],
            'status_verifikasi' => 'pending',
            'catatan' => $validated['catatan'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::statement(
            'UPDATE observasi SET geom_point = ST_GeomFromText(?, 4326) WHERE id = ?',
            [$validated['geometry_point'], $id]
        );

        Auth::user()->tambahPoin(5, 'observasi_baru', 'Melaporkan observasi baru');

        return response()->json([
            'message' => 'Observasi berhasil dikirim, menunggu verifikasi komunitas.',
            'id' => $id,
        ], 201);
    }

    public function verifikasi(Request $request, int $id): JsonResponse
    {
        if (! Auth::user()->isVerifikator()) {
            return response()->json(['message' => 'Anda tidak memiliki akses verifikasi.'], 403);
        }

        $validated = $request->validate([
            'status_verifikasi' => 'required|in:terverifikasi,ditolak',
            'spesies_id' => 'nullable|exists:spesies,id',
        ]);

        $observasi = Observasi::findOrFail($id);
        $observasi->update([
            'status_verifikasi' => $validated['status_verifikasi'],
            'spesies_id' => $validated['spesies_id'] ?? $observasi->spesies_id,
        ]);

        if ($validated['status_verifikasi'] === 'terverifikasi') {
            $observasi->user->tambahPoin(15, 'observasi_terverifikasi', 'Observasi telah diverifikasi komunitas');
        }

        return response()->json(['message' => 'Status observasi diperbarui.']);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $observasi = Observasi::findOrFail($id);

        if ($observasi->user_id !== Auth::id() && ! Auth::user()->isVerifikator()) {
            return response()->json(['message' => 'Anda tidak memiliki akses mengubah data ini.'], 403);
        }

        $validated = $request->validate([
            'geometry_point' => 'nullable|string',
            'nama_usulan' => 'nullable|string|max:255',
            'spesies_id' => 'nullable|exists:spesies,id',
            'provinsi_id' => 'nullable|exists:provinsi,id',
            'tanggal_amatan' => 'required|date',
            'catatan' => 'nullable|string|max:1000',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $dataUpdate = [
            'nama_usulan' => $validated['nama_usulan'] ?? $observasi->nama_usulan,
            'spesies_id' => $validated['spesies_id'] ?? $observasi->spesies_id,
            'provinsi_id' => $validated['provinsi_id'] ?? $observasi->provinsi_id,
            'tanggal_amatan' => $validated['tanggal_amatan'],
            'catatan' => $validated['catatan'] ?? $observasi->catatan,
        ];

        // Jika warga biasa (bukan verifikator) mengubah data inti laporannya,
        // status dikembalikan ke pending agar diperiksa ulang oleh komunitas.
        if (! Auth::user()->isVerifikator()) {
            $dataUpdate['status_verifikasi'] = 'pending';
        }

        if ($request->hasFile('foto')) {
            if ($observasi->foto && Storage::disk('public')->exists('observasi/' . $observasi->foto)) {
                Storage::disk('public')->delete('observasi/' . $observasi->foto);
            }

            $namaFile = time() . '_observasi.' . $request->file('foto')->extension();
            $request->file('foto')->storeAs('observasi', $namaFile, 'public');
            $dataUpdate['foto'] = $namaFile;
        }

        $observasi->update($dataUpdate);

        if (! empty($validated['geometry_point'])) {
            DB::statement(
                'UPDATE observasi SET geom_point = ST_GeomFromText(?, 4326) WHERE id = ?',
                [$validated['geometry_point'], $id]
            );
        }

        return response()->json(['message' => 'Observasi berhasil diperbarui.']);
    }

    public function show(int $id): JsonResponse
    {
        $observasi = Observasi::with(['spesies', 'provinsi'])->findOrFail($id);

        if ($observasi->user_id !== Auth::id() && ! Auth::user()->isVerifikator()) {
            return response()->json(['message' => 'Anda tidak memiliki akses melihat data ini.'], 403);
        }

        $titik = DB::table('observasi')
            ->select(DB::raw('ST_AsGeoJSON(geom_point) as geojson'))
            ->where('id', $id)
            ->first();

        return response()->json([
            'id' => $observasi->id,
            'nama_usulan' => $observasi->nama_usulan,
            'spesies_id' => $observasi->spesies_id,
            'provinsi_id' => $observasi->provinsi_id,
            'foto' => $observasi->foto,
            'tanggal_amatan' => $observasi->tanggal_amatan,
            'catatan' => $observasi->catatan,
            'status_verifikasi' => $observasi->status_verifikasi,
            'geometry' => json_decode($titik->geojson),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $observasi = Observasi::findOrFail($id);

        if ($observasi->user_id !== Auth::id() && ! Auth::user()->isVerifikator()) {
            return response()->json(['message' => 'Anda tidak memiliki akses menghapus data ini.'], 403);
        }

        if ($observasi->foto && Storage::disk('public')->exists('observasi/' . $observasi->foto)) {
            Storage::disk('public')->delete('observasi/' . $observasi->foto);
        }

        $observasi->delete();

        return response()->json(['message' => 'Observasi berhasil dihapus.']);
    }
}
