@extends('auth.app')

@section('title', 'Tabel Data — Jejak Fauna Nusantara')

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.0.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

<div class="container mt-5 pt-4 pb-5">
  <h3 class="fw-bold mb-1">Tabel Data Fauna</h3>
  <p class="text-muted mb-4">
    Lihat seluruh data dalam bentuk tabel: fauna khas Indonesia dan laporan observasi dari warga.
    Untuk melihatnya di peta, kunjungi <a href="{{ route('peta') }}">Peta Sebaran</a>.
  </p>

  <ul class="nav nav-tabs" id="tabTabel" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="tab-fauna-khas-btn" data-bs-toggle="tab" data-bs-target="#tab-fauna-khas" type="button" role="tab">
        Fauna Khas
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-observasi-btn" data-bs-toggle="tab" data-bs-target="#tab-observasi" type="button" role="tab">
        Observasi Warga
      </button>
    </li>
  </ul>

  <div class="tab-content border border-top-0 rounded-bottom p-3 bg-white" id="tabTabelContent">
    <div class="tab-pane fade show active" id="tab-fauna-khas" role="tabpanel">
      <div class="table-responsive">
        <table id="tabelFaunaKhas" class="table table-striped table-hover align-middle w-100">
          <thead class="table-success">
            <tr>
              <th>Foto</th>
              <th>Nama Lokal</th>
              <th>Nama Ilmiah</th>
              <th>Kelas Taksonomi</th>
              <th>Status Konservasi</th>
              <th>Tersebar di (provinsi)</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-observasi" role="tabpanel">
      <div class="table-responsive">
        <table id="tabelObservasi" class="table table-striped table-hover align-middle w-100">
          <thead class="table-success">
            <tr>
              <th>Foto</th>
              <th>Nama</th>
              <th>Pengamat</th>
              <th>Tanggal Amatan</th>
              <th>Provinsi</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.0.8/js/dataTables.bootstrap5.min.js"></script>
<script>
  // Helper kecil dipakai dua tabel: foto jadi thumbnail, status_verifikasi
  // jadi badge warna. Diletakkan di sini (bukan file JS terpisah) karena
  // halaman ini berdiri sendiri dan tidak dipakai oleh halaman lain.
  function selFoto(url) {
    return url
      ? `<img src="${url}" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">`
      : `<span class="text-muted small">Tidak ada</span>`;
  }

  function selStatusVerifikasi(status) {
    const kelas = {
      terverifikasi: 'bg-success',
      pending: 'bg-warning text-dark',
      ditolak: 'bg-danger',
    }[status] || 'bg-secondary';

    return `<span class="badge ${kelas}">${status}</span>`;
  }

  const opsiBahasaDataTables = {
    processing: 'Memuat data...',
    search: 'Cari:',
    lengthMenu: 'Tampilkan _MENU_ baris',
    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
    infoEmpty: 'Tidak ada data',
    infoFiltered: '(disaring dari _MAX_ total data)',
    paginate: { first: 'Awal', last: 'Akhir', next: 'Selanjutnya', previous: 'Sebelumnya' },
    emptyTable: 'Belum ada data.',
  };

  $('#tabelFaunaKhas').DataTable({
    serverSide: true,
    processing: true,
    ajax: { url: '/api/tabel/fauna-khas', type: 'GET' },
    language: opsiBahasaDataTables,
    order: [[1, 'asc']],
    columns: [
      { data: 'foto_url', orderable: false, render: selFoto },
      { data: 'nama_lokal' },
      { data: 'nama_ilmiah' },
      { data: 'kelas_taksonomi' },
      { data: 'status_konservasi' },
      { data: 'jumlah_provinsi', orderable: true, render: (v) => `${v} provinsi` },
    ],
  });

  $('#tabelObservasi').DataTable({
    serverSide: true,
    processing: true,
    ajax: { url: '/api/tabel/observasi', type: 'GET' },
    language: opsiBahasaDataTables,
    order: [[3, 'desc']],
    columns: [
      { data: 'foto_url', orderable: false, render: selFoto },
      { data: 'nama' },
      { data: 'nama_pengamat' },
      { data: 'tanggal_amatan' },
      { data: 'nama_provinsi' },
      { data: 'status_verifikasi', render: selStatusVerifikasi },
    ],
  });
</script>
@endpush
