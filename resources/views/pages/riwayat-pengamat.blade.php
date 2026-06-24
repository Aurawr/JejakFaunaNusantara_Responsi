@extends('auth.app')

@section('title', $pengamat->name . ' — Riwayat Pengamat — Jejak Fauna Nusantara')

@section('content')

<div class="container mt-5 pt-4">
  <a href="{{ route('papan-skor') }}" class="small text-decoration-none d-inline-block mb-3">&larr; Kembali ke Papan Peringkat</a>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h3 class="fw-bold mb-1">{{ $pengamat->name }}</h3>
      <p class="text-muted mb-2">Bergabung sejak {{ $pengamat->created_at->translatedFormat('F Y') }}</p>
      <span class="badge bg-success fs-6">{{ $pengamat->total_poin }} poin</span>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold mb-3">Laporan observasi</h5>
          @forelse ($pengamat->observasi as $obs)
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
              <span>{{ $obs->namaTampil() }}</span>
              <span class="badge {{ $obs->status_verifikasi === 'terverifikasi' ? 'bg-success' : ($obs->status_verifikasi === 'ditolak' ? 'bg-danger' : 'bg-secondary') }}">
                {{ ucfirst($obs->status_verifikasi) }}
              </span>
            </div>
          @empty
            <p class="text-muted">Belum ada laporan observasi.</p>
          @endforelse
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold mb-3">Riwayat kuis</h5>
          @forelse ($pengamat->kuisHasil as $hasil)
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
              <span>{{ $hasil->jumlah_benar }} dari {{ $hasil->jumlah_soal }} benar</span>
              <span class="fw-semibold">{{ $hasil->skor }}%</span>
            </div>
          @empty
            <p class="text-muted">Belum pernah main kuis.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
