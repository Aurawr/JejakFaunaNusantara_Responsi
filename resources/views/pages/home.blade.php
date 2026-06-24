@extends('auth.app')

@section('title', 'Jejak Fauna Nusantara — Petakan Satwa di Sekitarmu')

@section('content')

<section class="hero">
  <div class="hero-content">
    <h1 class="display-4 fw-bold">Setiap Satwa yang Kamu Temui, Berarti bagi Nusantara</h1>
    <p class="lead"><b>Lapor temuan satwa lewat foto dan lokasi, bantu petakan keanekaragaman hayati Indonesia bersama pengamat lainnya</b></p>
    <a href="{{ route('peta') }}" class="btn btn-success btn-lg mt-3">
      Jelajahi Peta Sebaran
    </a>
  </div>
</section>

<section class="py-5">
  <div class="container text-center">
    <h3 class="fw-bold mb-3">Tahukah Kamu? 🤔</h3>
    <b class="lead">
      Indonesia adalah salah satu negara dengan
      <b>keanekaragaman fauna tertinggi di dunia 🌏</b>
    <br>
      Dari Harimau Sumatra sampai Burung Cenderawasih, setiap laporanmu membantu menjaga datanya tetap hidup</b>
    </p>
  </div>
</section>

<section class="bg-light py-5">
<div class="container">
  <h3 class="fw-bold mb-1">Jejak Fauna Nusantara dalam Angka</h3>
  <p class="text-muted mb-4">Statistik komunitas Jejak Fauna Nusantara, diperbarui setiap ada laporan baru.</p>

  <div class="row text-center g-4">
    <div class="col-6 col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h2 class="fw-bold text-success">{{ $totalSpesiesDilaporkan }}</h2>
          <p class="text-muted mb-0 small">Spesies dilaporkan warga</p>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h2 class="fw-bold text-success">{{ $totalObservasiTerverifikasi }}</h2>
          <p class="text-muted mb-0 small">Laporan terverifikasi</p>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h2 class="fw-bold text-success">{{ $totalAnggota }}</h2>
          <p class="text-muted mb-0 small">Anggota pengamat</p>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h2 class="fw-bold text-success">{{ $totalSpesiesEndemik }}+</h2>
          <p class="text-muted mb-0 small">Spesies endemik Indonesia</p>
        </div>
      </div>
    </div>
  </div>

  <p class="text-muted small mt-3">
    Jumlah spesies endemik berdasarkan data nasional Kementerian Lingkungan Hidup dan Kehutanan.
  </p>
</div>
</section>

@endsection
