<nav class="navbar navbar-expand-lg navbar-light fixed-top cheerful-nav">
  <div class="container-fluid px-3">
    <a class="navbar-brand fw-bold" href="{{ route('home') }}">
      🐾 Jejak Fauna Nusantara
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-label="Buka menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
      <div class="navbar-nav ms-auto">
        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a>
        <a class="nav-link {{ request()->routeIs('peta') ? 'active' : '' }}" href="{{ route('peta') }}">Peta Sebaran</a>
        <a class="nav-link {{ request()->routeIs('tabel') ? 'active' : '' }}" href="{{ route('tabel') }}">Tabel Data</a>
        <a class="nav-link {{ request()->routeIs('galeri') || request()->routeIs('galeri.detail') ? 'active' : '' }}" href="{{ route('galeri') }}">Galeri</a>

        @auth
          <a class="nav-link {{ request()->routeIs('lapor') ? 'active' : '' }}" href="{{ route('lapor') }}">Lapor Observasi</a>
          <a class="nav-link {{ request()->routeIs('kuis') ? 'active' : '' }}" href="{{ route('kuis') }}">Kuis</a>
          <a class="nav-link {{ request()->routeIs('papan-skor') || request()->routeIs('riwayat-pengamat') ? 'active' : '' }}" href="{{ route('papan-skor') }}">Papan Peringkat</a>
          <form method="POST" action="{{ route('logout') }}" class="d-inline ms-2">
            @csrf
            <button class="btn btn-sm btn-danger btn-pill" type="submit">Keluar</button>
          </form>
          @else
          <a class="btn btn-sm btn-success btn-pill ms-2" href="{{ route('login') }}">Masuk</a>
        @endauth
      </div>
    </div>
  </div>
</nav>
