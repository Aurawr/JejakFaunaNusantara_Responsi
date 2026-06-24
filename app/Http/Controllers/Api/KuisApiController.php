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

        $soal = KuisSoal::with(['spesies', 'observasi'])
            ->where('tipe_soal', $tipe)
            ->inRandomOrder()
            ->take(5)
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'foto' => $s->fotoSoal(),
                    'tipe_soal' => $s->tipe_soal,
                    'provinsi_konteks' => $s->observasi?->provinsi?->nama_provinsi
                        ?? $s->spesies?->provinsi?->first()?->nama_provinsi,
                ];
            });

        return response()->json($soal);
    }

    public function jawab(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate(['jawaban' => 'required|string']);

        $soal = KuisSoal::with('spesies')->findOrFail($id);
        $jawabanBenar = $soal->spesies?->nama_lokal ?? '';

        $benar = strtolower(trim($validated['jawaban'])) === strtolower(trim($jawabanBenar));

        if ($soal->tipe_soal === 'tantangan_identifikasi' && $soal->observasi_id) {
            $this->catatTebakanTantangan($soal, $validated['jawaban']);
        }

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
