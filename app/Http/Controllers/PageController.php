<?php

namespace App\Http\Controllers;

use App\Models\Observasi;
use App\Models\Spesies;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{
    /**
     * Jumlah spesies endemik Indonesia adalah fakta nasional yang sudah
     * ditetapkan lembaga riset (BRIN/LIPI), bukan dihitung dari data warga
     * yang masuk lewat aplikasi ini. Disimpan sebagai konstanta agar jelas
     * sumbernya berbeda dari statistik partisipasi di bawahnya.
     */
    private const JUMLAH_SPESIES_ENDEMIK_INDONESIA = 1463;

    public function home()
    {
        // Statistik komunitas (sebelumnya halaman /dashboard tersendiri)
        // digabung ke beranda agar navbar tidak terlalu banyak halaman.
        // Datanya publik (bukan data pribadi pengguna), jadi aman ditaruh
        // di beranda yang bisa dilihat siapa saja.
        $totalSpesiesDilaporkan = Spesies::has('observasi')->count();
        $totalObservasiTerverifikasi = Observasi::where('status_verifikasi', 'terverifikasi')->count();
        $totalAnggota = User::count();

        return view('pages.home', [
            'totalSpesiesDilaporkan' => $totalSpesiesDilaporkan,
            'totalAnggota' => $totalAnggota,
            'totalSpesiesEndemik' => self::JUMLAH_SPESIES_ENDEMIK_INDONESIA,
            'totalObservasiTerverifikasi' => $totalObservasiTerverifikasi,
        ]);
    }

    public function peta()
    {
        // Sengaja publik, tanpa middleware auth: peta sebaran boleh dilihat
        // siapa saja. Tombol lapor dan kuis di dalam halaman ini akan
        // mengarahkan ke halaman login jika diklik oleh pengunjung yang
        // belum masuk (dicek lewat window.isLoggedIn di JS).
        return view('pages.map');
    }

    public function tabel()
    {
        // Halaman tabel data publik (fauna khas & observasi warga), pakai
        // DataTables server-side processing lewat TabelApiController.
        // Dipisah dari halaman peta agar tidak numpuk jadi satu halaman
        // dengan peta, form lapor, dan kuis.
        return view('pages.tabel');
    }

    public function galeri()
    {
        // Halaman publik, grid foto gabungan fauna khas + observasi warga,
        // datanya diambil lewat GaleriApiController::index() (AJAX), bukan
        // di-render langsung di sini, supaya pagination & search bisa
        // jalan tanpa reload halaman penuh.
        return view('pages.galeri');
    }

    public function galeriDetail(string $tipe, int $id)
    {
        // Halaman detail punya URL sendiri (bukan modal JS murni) agar bisa
        // dibagikan/dibookmark, mengikuti pola halaman detail observasi
        // iNaturalist yang juga punya URL tersendiri per observasi.
        if (! in_array($tipe, ['fauna_khas', 'observasi'], true)) {
            abort(404);
        }

        return view('pages.galeri-detail', ['tipe' => $tipe, 'id' => $id]);
    }

    public function lapor()
    {
        // Halaman ini dipisah dari peta utama dan dilindungi middleware
        // auth+verified di routes/web.php. Karena itu, tidak perlu lagi
        // ada pengecekan login manual lewat JS (periksaLoginSebelumAksi)
        // seperti sebelumnya saat fitur ini masih berupa FAB di atas peta:
        // pengunjung yang belum login otomatis diarahkan ke halaman login
        // oleh middleware sebelum sempat melihat halaman ini.
        //
        // Statistik komunitas ditampilkan di panel samping supaya halaman
        // tidak terasa kosong, sama seperti yang sudah ada di beranda.
        return view('pages.lapor', [
            'totalObservasiTerverifikasi' => Observasi::where('status_verifikasi', 'terverifikasi')->count(),
            'totalAnggota' => User::count(),
        ]);
    }

    public function kuis()
    {
        // Sama seperti /lapor, dilindungi middleware auth+verified di
        // routes/web.php, jadi tidak perlu periksaLoginSebelumAksi lagi.
        //
        // Papan peringkat mini ini berbeda dari papanSkor() (yang pakai
        // total_poin gabungan observasi+kuis) — di sini khusus menjumlah
        // poin yang sumbernya 'kuis' saja dari tabel skor_riwayat, supaya
        // relevan dengan konteks halaman ini.
        $topKuis = User::query()
            ->select('users.id', 'users.name')
            ->selectSub(
                DB::table('skor_riwayat')
                    ->selectRaw('COALESCE(SUM(poin), 0)')
                    ->whereColumn('user_id', 'users.id')
                    ->where('sumber_poin', 'kuis'),
                'poin_kuis'
            )
            ->orderByDesc('poin_kuis')
            ->take(5)
            ->get();

        return view('pages.kuis', compact('topKuis'));
    }

    public function papanSkor()
    {
        // Dilindungi middleware auth di routes/web.php: papan peringkat
        // hanya untuk anggota komunitas yang sudah login, bukan untuk
        // pengunjung yang sekadar lewat tanpa mendaftar.
        $pengamat = User::orderByDesc('total_poin')
            ->withCount('observasi')
            ->take(20)
            ->get();

        return view('pages.papan-skor', compact('pengamat'));
    }

    public function riwayatPengamat(User $pengamat)
    {
        // Sama seperti papanSkor(), dilindungi middleware auth: detail
        // riwayat (foto, lokasi, tanggal observasi) seseorang hanya bisa
        // dilihat oleh anggota komunitas lain yang sudah login.
        $pengamat->load(['observasi.spesies', 'kuisHasil']);

        return view('pages.riwayat-pengamat', compact('pengamat'));
    }
}
