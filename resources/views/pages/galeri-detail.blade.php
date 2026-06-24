@extends('auth.app')

@section('title', 'Detail Fauna — Jejak Fauna Nusantara')

@section('content')

<div class="container mt-5 pt-4 pb-5">
  <a href="{{ route('galeri') }}" class="small text-decoration-none d-inline-block mb-3">&larr; Kembali ke Galeri</a>

  <div id="detail-galeri-isi" class="row g-4">
    <div class="col-12 text-center text-muted py-5">Memuat detail...</div>
  </div>
</div>

@endsection

@push('scripts')
    <script>
      const tipeGaleri = "{{ $tipe }}";
      const idGaleri = {{ $id }};
      let detailMiniMap = null;

      function badgeStatusDetail(tipe, status) {
        if (tipe === 'fauna_khas') {
          return status === 'dilindungi'
            ? `<span class="badge bg-success">Dilindungi</span>`
            : `<span class="badge bg-secondary">${status}</span>`;
        }

        const kelas = { terverifikasi: 'bg-success', pending: 'bg-warning text-dark', ditolak: 'bg-danger' }[status] || 'bg-secondary';
        return `<span class="badge ${kelas}">${status}</span>`;
      }

      fetch(`/api/galeri/${tipeGaleri}/${idGaleri}`)
        .then(r => {
          if (!r.ok) throw new Error('not found');
          return r.json();
        })
        .then(data => {
            console.log('DATA MASUK', data);
          const isiKanan = data.tipe === 'fauna_khas'
            ? renderInfoFaunaKhas(data)
            : renderInfoObservasi(data);

          document.getElementById('detail-galeri-isi').innerHTML = `
      <div class="col-lg-7">
        <img
            src="${data.foto_url ?? ''}"
            class="w-100 rounded shadow-sm"
            style="height: 520px; object-fit: cover;"
            alt="${data.nama}">
      </div>

      <div class="col-lg-5">

        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            ${isiKanan}
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h6 class="fw-bold mb-3">Lokasi Persebaran</h6>
            <div id="detail-mini-map"
                 style="height:250px; border-radius:8px;">
            </div>
          </div>
        </div>

      </div>
    `;

    if (data.tipe === 'observasi' && data.geometry) {
      inisialisasiMiniMapObservasi(data.geometry);
    }

    if (data.tipe === 'fauna_khas') {
      inisialisasiMiniMapFauna(data);
    }
        })
        .catch(() => {
          document.getElementById('detail-galeri-isi').innerHTML = `
            <div class="col-12 text-center text-danger py-5">Data tidak ditemukan.</div>
          `;
        });

      function renderInfoFaunaKhas(data) {
        const daftarProvinsi = (data.provinsi_terkait || [])
          .map(p => `<li class="mb-2"><span class="fw-semibold">${p.nama_provinsi}</span>${p.deskripsi ? ` &mdash; <span class="text-muted small">${p.deskripsi}</span>` : ''}</li>`)
          .join('');

        return `
          <span class="badge bg-success mb-2">Fauna khas</span>
          <h3 class="fw-bold mb-1">${data.nama}</h3>
          <p class="text-muted fst-italic mb-2">${data.nama_ilmiah ?? '(belum diisi)'}</p>
          <div class="d-flex align-items-center gap-2 mt-2 mb-3">
            ${badgeStatusDetail('fauna_khas', data.status)}
            <span class="text-muted small">
                Kelas taksonomi: ${data.kelas_taksonomi ?? '-'}
            </span>
          </div>
          <h6 class="fw-bold mt-4 mb-2">Tersebar di provinsi</h6>
          <ul class="list-unstyled mb-0">${daftarProvinsi || '<li class="text-muted small">Belum ada data provinsi terkait.</li>'}</ul>
        `;
      }

      function renderInfoObservasi(data) {
        return `
          <span class="badge bg-primary mb-2">Observasi warga</span>
          <h3 class="fw-bold mb-1">${data.nama}</h3>
          <p class="text-muted fst-italic mb-2">${data.nama_ilmiah ?? 'Belum teridentifikasi'}</p>
          ${badgeStatusDetail('observasi', data.status)}
          <div class="mt-3">
            <p class="mb-1"><span class="fw-semibold">Pengamat:</span> ${data.nama_pengamat}</p>
            <p class="mb-1"><span class="fw-semibold">Tanggal amatan:</span> ${data.tanggal_amatan}</p>
            <p class="mb-1"><span class="fw-semibold">Provinsi:</span> ${data.nama_provinsi ?? '-'}</p>
            ${data.catatan ? `<p class="mb-1"><span class="fw-semibold">Catatan:</span> ${data.catatan}</p>` : ''}
          </div>
        `;
      }

      function inisialisasiMiniMapObservasi(geometry) {
        setTimeout(() => {
          const [lng, lat] = geometry.coordinates;
          detailMiniMap = L.map('detail-mini-map', { zoomControl: false }).setView([lat, lng], 9);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
          }).addTo(detailMiniMap);
          L.marker([lat, lng]).addTo(detailMiniMap);
        }, 50);
      }

      function inisialisasiMiniMapFauna(data) {

      const map = L.map('detail-mini-map', {
        zoomControl: false
      }).setView([-2.5, 118], 4);

      L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
          attribution: '&copy; OpenStreetMap contributors'
        }
      ).addTo(map);

      const koordinatProvinsi = {
        'RIAU': [0.5, 101.4],
        'ACEH': [5.5, 95.3],
        'SUMATERA BARAT': [-0.9, 100.3],
        'JAWA BARAT': [-6.9, 107.6],
        'BALI': [-8.4, 115.1]
      };

      (data.provinsi_terkait || []).forEach(p => {

        const nama = p.nama_provinsi?.toUpperCase();
        if (koordinatProvinsi[nama]) {
          L.marker(
            koordinatProvinsi[nama]
          ).addTo(map)
          .bindPopup(p.nama_provinsi);
        }
      });

    }
    </script>
@endpush
