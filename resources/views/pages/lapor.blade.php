@extends('auth.app')

@section('title', 'Lapor Observasi — Jejak Fauna Nusantara')

@section('content')

<div class="container mt-5 pt-4 pb-5" style="max-width: 640px;">
  <h3 class="fw-bold mb-1">Lapor Observasi Fauna</h3>
  <p class="text-muted mb-4">
    Temukan satwa di sekitarmu? Laporkan di sini lengkap dengan foto dan lokasinya.
    Laporanmu akan diperiksa komunitas sebelum tampil terverifikasi di
    <a href="{{ route('peta') }}">Peta Sebaran</a>.
  </p>

  {{-- Langkah 1: pilih cara dapat foto & lokasi --}}
  <div id="langkah-pilih-cara" class="card shadow-sm">
    <div class="card-body">
      <h5 class="fw-bold mb-3">Bagaimana kamu menemukan satwa ini?</h5>
      <button class="btn btn-success w-100 mb-2" onclick="mulaiAmbilFotoLangsung()">
        Ambil foto sekarang (pakai lokasi GPS saat ini)
      </button>
      <button class="btn btn-outline-secondary w-100" onclick="mulaiUploadFotoLama()">
        Upload foto lama (tandai lokasi manual di peta)
      </button>
    </div>
  </div>

  {{-- Langkah 2: tandai lokasi manual di peta kecil (hanya untuk jalur upload foto lama) --}}
  <div id="langkah-tandai-peta" class="card shadow-sm d-none">
    <div class="card-body">
      <h5 class="fw-bold mb-2">Tandai Lokasi Penemuan</h5>
      <p class="small text-muted mb-2">Klik pada peta di titik tempat satwa ini ditemukan.</p>
      <div id="mini-map" style="height: 320px; border-radius: 8px;"></div>
      <button id="btn-konfirmasi-titik" class="btn btn-success w-100 mt-3 d-none" onclick="konfirmasiTitikManual()">
        Gunakan Titik Ini
      </button>
    </div>
  </div>

  {{-- Langkah 3: form atribut laporan --}}
  <div id="langkah-form-atribut" class="card shadow-sm d-none">
    <div class="card-body">
      <h5 class="fw-bold mb-3">Detail Laporan</h5>
      <div class="mb-3 text-center">
        <img id="preview-foto-lapor" class="fauna-img-sidebar w-100" alt="Pratinjau foto" style="max-height: 240px; object-fit: cover;">
      </div>
      <label class="form-label small fw-semibold">Nama fauna (jika tahu)</label>
      <input id="input-nama-usulan" class="form-control mb-2" placeholder="Contoh: Burung Cenderawasih">
      <label class="form-label small fw-semibold">Tanggal pengamatan</label>
      <input id="input-tanggal" type="date" class="form-control mb-2" value="{{ now()->format('Y-m-d') }}">
      <label class="form-label small fw-semibold">Catatan tambahan</label>
      <textarea id="input-catatan" class="form-control mb-3" rows="2" placeholder="Ciri-ciri, jumlah individu, perilaku, dst."></textarea>
      <button class="btn btn-success w-100" onclick="kirimObservasi()">Kirim Laporan</button>
      <button class="btn btn-link w-100 text-muted" onclick="batalkanLaporan()">Batal, mulai ulang</button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
  window.routeObservasiStore = "{{ url('/api/observasi') }}";
  window.csrfToken = "{{ csrf_token() }}";

  /* ================= STATE LAPORAN SEMENTARA ================= */
  let modeLapor = null;
  let fotoLaporSementara = null;
  let titikLaporSementara = null;
  let miniMap = null;
  let markerTitikManual = null;

  function tampilkanLangkah(idDitampilkan) {
    ["langkah-pilih-cara", "langkah-tandai-peta", "langkah-form-atribut"].forEach((id) => {
      document.getElementById(id).classList.toggle("d-none", id !== idDitampilkan);
    });
  }

  /* ================= JALUR 1: AMBIL FOTO SEKARANG + GPS ================= */
  function mulaiAmbilFotoLangsung() {
    modeLapor = "gps_realtime";

    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";
    input.setAttribute("capture", "environment");

    input.onchange = (e) => {
      fotoLaporSementara = e.target.files[0];
      if (!fotoLaporSementara) return;

      if (!navigator.geolocation) {
        tampilkanToast("Perangkat tidak mendukung GPS. Silakan tandai lokasi secara manual.", "error");
        mulaiUploadFotoLama();
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (pos) => {
          titikLaporSementara = { lat: pos.coords.latitude, lng: pos.coords.longitude };
          bukaFormAtribut();
        },
        () => {
          tampilkanToast("Tidak dapat mengambil lokasi GPS. Silakan tandai lokasi secara manual.", "error");
          mulaiUploadFotoLama();
        }
      );
    };

    input.click();
  }

  /* ================= JALUR 2: UPLOAD FOTO LAMA + TANDAI MANUAL ================= */
  function mulaiUploadFotoLama() {
    modeLapor = "klik_manual";

    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";

    input.onchange = (e) => {
      fotoLaporSementara = e.target.files[0];
      if (!fotoLaporSementara) return;
      bukaPetaTandaiManual();
    };

    input.click();
  }

  function bukaPetaTandaiManual() {
    tampilkanLangkah("langkah-tandai-peta");

    // Peta kecil dibuat sekali saja (bukan setiap kali jalur ini dibuka),
    // karena Leaflet error kalau di-init dua kali pada elemen yang sama.
    if (!miniMap) {
      miniMap = L.map("mini-map").setView([-2, 118], 5);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors"
      }).addTo(miniMap);

      miniMap.on("click", (e) => {
        titikLaporSementara = { lat: e.latlng.lat, lng: e.latlng.lng };
        if (markerTitikManual) miniMap.removeLayer(markerTitikManual);
        markerTitikManual = L.marker([e.latlng.lat, e.latlng.lng]).addTo(miniMap);
        document.getElementById("btn-konfirmasi-titik").classList.remove("d-none");
      });
    } else {
      setTimeout(() => miniMap.invalidateSize(), 0);
    }
  }

  function konfirmasiTitikManual() {
    if (!titikLaporSementara) return;
    bukaFormAtribut();
  }

  /* ================= LANGKAH 3: FORM ATRIBUT + KIRIM ================= */
  function bukaFormAtribut() {
    tampilkanLangkah("langkah-form-atribut");
    document.getElementById("preview-foto-lapor").src = window.URL.createObjectURL(fotoLaporSementara);
  }

  function batalkanLaporan() {
    modeLapor = null;
    fotoLaporSementara = null;
    titikLaporSementara = null;
    if (markerTitikManual && miniMap) {
      miniMap.removeLayer(markerTitikManual);
      markerTitikManual = null;
    }
    document.getElementById("btn-konfirmasi-titik").classList.add("d-none");
    document.getElementById("input-nama-usulan").value = "";
    document.getElementById("input-catatan").value = "";
    tampilkanLangkah("langkah-pilih-cara");
  }

  function kirimObservasi() {
    if (!fotoLaporSementara || !titikLaporSementara) {
      tampilkanToast("Foto dan lokasi belum lengkap. Mulai ulang laporan.", "error");
      return;
    }

    const formData = new FormData();
    formData.append("foto", fotoLaporSementara);
    formData.append("geometry_point", `POINT(${titikLaporSementara.lng} ${titikLaporSementara.lat})`);
    formData.append("nama_usulan", document.getElementById("input-nama-usulan").value);
    formData.append("tanggal_amatan", document.getElementById("input-tanggal").value);
    formData.append("catatan", document.getElementById("input-catatan").value);
    formData.append("sumber_lokasi", modeLapor);

    fetch(window.routeObservasiStore, {
      method: "POST",
      headers: { "X-CSRF-TOKEN": window.csrfToken },
      body: formData
    })
      .then(r => r.json())
      .then(data => {
        tampilkanToast(data.message || "Laporan terkirim, menunggu verifikasi komunitas.", "success");
        batalkanLaporan();
        setTimeout(() => { window.location.href = "{{ route('peta') }}"; }, 1200);
      })
      .catch(() => tampilkanToast("Gagal mengirim laporan. Periksa koneksi dan coba lagi.", "error"));
  }
</script>
@endpush
