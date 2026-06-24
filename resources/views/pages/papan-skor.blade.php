@extends('auth.app')

@section('title', 'Papan Peringkat Pengamat — Jejak Fauna Nusantara')

@section('content')

<div class="container mt-5 pt-4">
  <h3 class="fw-bold mb-1">Papan peringkat pengamat</h3>
  <p class="text-muted mb-4">Diurutkan dari gabungan poin laporan observasi dan kuis. Klik nama untuk melihat detail riwayatnya.</p>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-success">
            <tr>
              <th>Peringkat</th>
              <th>Nama Pengamat</th>
              <th>Jumlah Laporan</th>
              <th>Total Poin</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($pengamat as $i => $p)
              <tr class="cursor-pointer" onclick="window.location.href='{{ route('riwayat-pengamat', $p) }}'">
                <td>{{ $i + 1 }}</td>
                <td class="fw-semibold">{{ $p->name }}</td>
                <td>{{ $p->observasi_count }}</td>
                <td><span class="badge bg-success">{{ $p->total_poin }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@endsection
