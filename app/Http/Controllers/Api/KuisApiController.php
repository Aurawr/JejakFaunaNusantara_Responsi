<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KuisSoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KuisApiController extends Controller
{
    public function soal(Request $request): JsonResponse
    {
        $tipe = $request->input('tipe', 'edukasi');

        if ($tipe === 'edukasi') {

        $soal = \App\Models\Spesies::query()
            ->whereNotNUll('foto_referensi')
            ->inRandomOrder()
            ->take(5)
            ->get()
            ->map(function ($s) {
                $provinsi = $s -> provinsi?->first();
                return [
                    'id' => $s->id,
                    'foto' => $s->foto_referensi,
                    'tipe_soal' => 'edukasi',
                    'provinsi_konteks' =>  $provinsi?->nama_provinsi,
                    'jawaban' => $s->nama_lokal,
                ];
            });

        return response()->json($soal);
        }
    }

    public function jawab(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'jawaban' => 'required|string'
        ]);

        $spesies = \App\Models\Spesies::findOrFail($id);
        $jawabanBenar = $spesies->nama_lokal;

        $benar = strtolower(trim($validated['jawaban']))
            === strtolower(trim($jawabanBenar));

        return response()->json([
            'benar' => $benar,
            'jawaban_benar' => $jawabanBenar,
        ]);
    }

    public function selesai(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jumlah_benar' => 'required|integer|min:0',
            'jumlah_soal' => 'required|integer|min:1',
        ]);

        $skor = (int) round(($validated['jumlah_benar'] / $validated['jumlah_soal']) * 100);
        $poin = $validated['jumlah_benar'] * 3;

        $user = Auth::user();

        $user->kuisHasil()->create([
            'skor' => $skor,
            'jumlah_benar' => $validated['jumlah_benar'],
            'jumlah_soal' => $validated['jumlah_soal'],
        ]);

        $user->tambahPoin($poin, 'kuis', 'Menyelesaikan sesi kuis');

        return response()->json([
            'skor' => $skor,
            'poin_didapat' => $poin,
            'total_poin' => $user->fresh()->total_poin,
        ]);
    }

    private function catatTebakanTantangan(KuisSoal $soal, string $jawaban): void
    {
        // Tebakan komunitas dicatat di tabel terpisah (tebakan_tantangan) sebagai
        // sinyal bantu verifikasi: jika mayoritas pemain menebak nama yang sama,
        // verifikator bisa memprioritaskan observasi tersebut untuk diverifikasi.
        \Illuminate\Support\Facades\DB::table('tebakan_tantangan')->insert([
            'kuis_soal_id' => $soal->id,
            'user_id' => Auth::id(),
            'jawaban' => $jawaban,
            'created_at' => now(),
        ]);
    }
}
