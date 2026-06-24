@extends('auth.app')

@section('title', 'Galeri Fauna — Jejak Fauna Nusantara')

@section('content')

<div class="container mt-5 pt-4 pb-5">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
      <h3 class="fw-bold mb-1">Galeri Fauna</h3>
      <p class="text-muted mb-0">Kumpulan foto fauna khas Indonesia dan temuan warga dari seluruh penjuru Nusantara.</p>
    </div>
    <div style="min-width: 260px;">
      <input id="input-cari-galeri" class="form-control" placeholder="Cari nama fauna...">
    </div>
  </div>

  <div id="galeri-grid" class="row g-3">
    <div class="col-12 text-center text-muted py-5">Memuat galeri...</div>
  </div>

  <nav class="mt-4">
    <ul id="galeri-pagination" class="pagination justify-content-center"></ul>
  </nav>
</div>

@endsection

@push('scripts')
<script>
  let halamanGaleriAktif = 1;
  let timerCariGaleri = null;

  function muatGaleri(halaman = 1) {
    halamanGaleriAktif = halaman;
    const cari = document.getElementById('input-cari-galeri').value.trim();
    const grid = document.getElementById('galeri-grid');

    grid.innerHTML = '<div class="col-12 text-center text-muted py-5">Memuat galeri...</div>';

    fetch(`/api/galeri?halaman=${halaman}&cari=${encodeURIComponent(cari)}`)
      .then(r => r.json())
      .then(data => {
        console.log(data.data[0]);
        if (!data.data.length) {
          grid.innerHTML = '<div class="col-12 text-center text-muted py-5">Belum ada data yang cocok.</div>';
          document.getElementById('galeri-pagination').innerHTML = '';
          return;
        }

        grid.innerHTML = data.data.map(renderKartuGaleri).join('');
        renderPaginationGaleri(data.halaman_saat_ini, data.total_halaman);
      })
      .catch(() => {
        grid.innerHTML = '<div class="col-12 text-center text-danger py-5">Gagal memuat galeri. Coba muat ulang halaman.</div>';
      });
  }

  function renderKartuGaleri(item) {
    const labelTipe = item.tipe === 'fauna_khas' ? 'Fauna khas' : 'Observasi warga';
    const warnaBadge = item.tipe === 'fauna_khas' ? 'bg-success' : 'bg-primary';
    const subInfo = item.tipe === 'fauna_khas'
      ? (item.nama_provinsi || 'Indonesia')
      : `oleh ${item.nama_pengamat || 'Anonim'}`;

    return `
      <div class="col-6 col-md-4 col-lg-3">
        <a href="/galeri/${item.tipe}/${item.id}" class="text-decoration-none">
          <div class="card h-100 shadow-sm galeri-card">
            <img src="${item.foto_url ?? ''}" class="card-img-top galeri-thumb" alt="${item.nama}">
            <div class="card-body p-2">
              <span class="badge ${warnaBadge} mb-1">${labelTipe}</span>
              <div class="fw-bold small text-truncate">${item.nama}</div>
              <div class="text-muted small text-truncate">${subInfo}</div>
            </div>
          </div>
        </a>
      </div>
    `;
  }

  function renderPaginationGaleri(halamanSaatIni, totalHalaman) {
    const ul = document.getElementById('galeri-pagination');

    if (totalHalaman <= 1) {
      ul.innerHTML = '';
      return;
    }

    let html = '';
    for (let i = 1; i <= totalHalaman; i++) {
      html += `
        <li class="page-item ${i === halamanSaatIni ? 'active' : ''}">
          <button class="page-link" onclick="muatGaleri(${i})">${i}</button>
        </li>
      `;
    }
    ul.innerHTML = html;
  }

  document.getElementById('input-cari-galeri').addEventListener('input', () => {
    clearTimeout(timerCariGaleri);
    timerCariGaleri = setTimeout(() => muatGaleri(1), 400);
  });

  muatGaleri(1);
</script>
@endpush
