@extends('auth.app')

@section('title', 'Peta Sebaran — Jejak Fauna Nusantara')

@section('content')

<div id="map" role="application" aria-label="Peta sebaran fauna Indonesia"></div>
<div id="sidebar"></div>

@include('pages.map-edit-point')

@push('scripts')
<script>
  window.routeObservasiGeojson = "{{ url('/api/observasi/geojson') }}";
  window.routeProvinsiGeojson = "{{ url('/api/provinsi/wfs-proxy') }}";
  window.routeTitikFaunaKhasGeojson = "{{ url('/api/spesies/titik-geojson') }}";
  window.csrfToken = "{{ csrf_token() }}";
  window.currentUserId = {{ auth()->id() ?? 'null' }};
  window.currentUserRole = "{{ auth()->user()->role ?? '' }}";
</script>
<script>
  /* ================= PALET WARNA PROVINSI ================= */
  const ASIATIS = "#A5D6A7";
  const PERALIHAN = "#FFE082";
  const AUSTRALIS = "#90CAF9";
  const DEFAULT_CENTER = [-2, 118];
  const DEFAULT_ZOOM = 5.4;

  /* ================= IKON MARKER (DISAMAKAN UNTUK FAUNA KHAS & OBSERVASI WARGA) =================*/
  const iconTitikFauna = L.divIcon({
    className: "marker-fauna-icon",
    html: '<div class="marker-fauna-pin"><span>🐾</span></div>',
    iconSize: [34, 34],
    iconAnchor: [17, 32],
    popupAnchor: [0, -30],
  });

  const map = L.map("map").setView([-2, 118], 5.4);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
  }).addTo(map);

  let provinsiPolygonLayer;
  let provinsiPolylineLayer;
  let observasiLayer = L.layerGroup();
  let faunaKhasLayer = L.layerGroup();
  let bufferLayer = L.layerGroup().addTo(map);

  const sidebar = document.getElementById("sidebar");

  /* ================= GABUNGAN TITIK FAUNA KHAS + OBSERVASI WARGA =================
     Dipakai bersama oleh fitur cari/locate dan buffer radius, supaya kedua
     fitur ini bekerja lintas sumber data, bukan cuma salah satu layer. */
  let semuaTitik = [];

  function hitungJarakKm(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  function jsArg(str) {
    return JSON.stringify(str).replace(/"/g, "&quot;");
  }

  /* ================= KONTROL CARI FAUNA + RADIUS BUFFER ================= */
  const kontrolCariBuffer = L.control({ position: "topright" });
  kontrolCariBuffer.onAdd = function () {
    const div = L.DomUtil.create("div", "map-search-box");
    div.innerHTML = `
      <div class="position-relative">
        <input type="text" id="cari-fauna-input" class="form-control form-control-sm mb-2"
               placeholder="Cari nama fauna, lalu Enter" autocomplete="off">
        <div id="saran-fauna" class="search-suggestions"></div>
      </div>
      <div class="d-flex align-items-center gap-1">
        <label class="small text-muted mb-0" for="radius-buffer-km">Radius buffer:</label>
        <input type="number" id="radius-buffer-km" class="form-control form-control-sm" style="width:64px" value="10" min="1" max="500">
        <span class="small text-muted">km</span>
      </div>
    `;
    L.DomEvent.disableClickPropagation(div);
    L.DomEvent.disableScrollPropagation(div);
    return div;
  };
  kontrolCariBuffer.addTo(map);

  map.whenReady(() => {
    const inputCari = document.getElementById("cari-fauna-input");
    const kotakSaran = document.getElementById("saran-fauna");

    inputCari.addEventListener("input", () => tampilkanSaranFauna(inputCari.value));
    inputCari.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        sembunyikanSaranFauna();
        cariFaunaFlyTo(inputCari.value);
      }
    });

    document.addEventListener("click", (e) => {
      if (!kotakSaran.contains(e.target) && e.target !== inputCari) {
        sembunyikanSaranFauna();
      }
    });
  });

  function tampilkanSaranFauna(query) {
    const kotakSaran = document.getElementById("saran-fauna");
    query = (query || "").trim().toLowerCase();

    if (!query) {
      sembunyikanSaranFauna();
      return;
    }

    const namaUnik = [...new Set(semuaTitik.map(t => t.nama))].sort();
    const cocok = namaUnik.filter(n => n.toLowerCase().includes(query)).slice(0, 8);

    if (!cocok.length) {
      sembunyikanSaranFauna();
      return;
    }

    kotakSaran.innerHTML = cocok
      .map(n => `<div class="search-suggestion-item" onclick="pilihSaranFauna(${jsArg(n)})">${n}</div>`)
      .join("");
    kotakSaran.style.display = "block";
  }

  function sembunyikanSaranFauna() {
    const kotakSaran = document.getElementById("saran-fauna");
    kotakSaran.style.display = "none";
    kotakSaran.innerHTML = "";
  }

  function pilihSaranFauna(nama) {
    document.getElementById("cari-fauna-input").value = nama;
    sembunyikanSaranFauna();
    cariFaunaFlyTo(nama);
  }

  function cariFaunaFlyTo(nama) {
    nama = (nama || "").trim();
    if (!nama) return;

    let target = semuaTitik.find(t => t.nama.toLowerCase() === nama.toLowerCase());
    if (!target) {
      target = semuaTitik.find(t => t.nama.toLowerCase().includes(nama.toLowerCase()));
    }

    if (!target) {
      tampilkanToast(`Fauna "${nama}" tidak ditemukan di titik peta manapun.`, "error");
      return;
    }

    map.flyTo([target.lat, target.lng], 13);
    setTimeout(() => {
      target.layer.openPopup();
      // Buffer radius otomatis terbentuk begitu hasil pencarian ditemukan,
      // tidak perlu lagi klik tombol apa pun di popup.
      tampilkanBuffer(target.lat, target.lng, target.nama);
    }, 700);
  }

  /* ================= BUFFER RADIUS DARI TITIK YANG DIKLIK ================= */
  function tampilkanBuffer(lat, lng, namaAsal) {
    const radiusKm = parseFloat(document.getElementById("radius-buffer-km").value) || 10;
    bufferLayer.clearLayers();

    const circle = L.circle([lat, lng], {
      radius: radiusKm * 1000,
      color: "#D32F2F",
      weight: 1.5,
      fillColor: "#D32F2F",
      fillOpacity: 0.08,
    }).addTo(bufferLayer);

    const titikDalamRadius = semuaTitik
      .map(t => ({ ...t, jarak: hitungJarakKm(lat, lng, t.lat, t.lng) }))
      .filter(t => t.jarak <= radiusKm && t.jarak > 0.05)
      .sort((a, b) => a.jarak - b.jarak);

    openSidebar(`Hasil dalam Radius ${radiusKm} km`, `
      <p class="small text-muted mb-3">Titik acuan: <strong>${namaAsal}</strong></p>
      ${titikDalamRadius.length
        ? titikDalamRadius.map(t => `
            <div class="d-flex align-items-center gap-2 border-bottom py-2" style="cursor:pointer" onclick="flyToTitikByNama(${jsArg(t.nama)})">
              <img src="${t.foto}" style="width:42px;height:42px;object-fit:cover;border-radius:6px;" onerror="this.style.visibility='hidden'">
              <div>
                <div class="small fw-semibold">${t.nama}</div>
                <div class="small text-muted">${t.sumber === "fauna_khas" ? "Fauna khas" : "Observasi warga"} &middot; ${t.jarak.toFixed(1)} km</div>
              </div>
            </div>
          `).join("")
        : `<p class="text-muted small">Tidak ada titik fauna khas atau observasi lain dalam radius ini.</p>`
      }
      <button class="btn btn-sm btn-outline-secondary w-100 mt-3" onclick="hapusBuffer()">Hapus Buffer dari Peta</button>
    `);

    map.fitBounds(circle.getBounds());
  }

  function flyToTitikByNama(nama) {
    const target = semuaTitik.find(t => t.nama === nama);
    if (target) {
      map.flyTo([target.lat, target.lng], 13);
      setTimeout(() => target.layer.openPopup(), 700);
    }
  }

  function hapusBuffer() {
    bufferLayer.clearLayers();
  }

  /* ================= SIDEBAR ================= */
  function openSidebar(title, content) {
    sidebar.innerHTML = `
      <div class="sidebar-header d-flex align-items-center">
        <h5 class="fw-bold mb-0">${title}</h5>
        <button class="btn btn-sm btn-outline-danger ms-auto" onclick="closeSidebar()">&times;</button>
      </div>
      <div class="sidebar-body">${content}</div>
    `;
    sidebar.classList.add("sidebar-open");
    sidebar.style.display = "block";
    sidebar.style.width = window.innerWidth < 768 ? "100%" : "30%";
  }

  function closeSidebar() {
    // batalkan mode edit jika sedang ada marker edit
    if (markerEdit) {
        map.removeLayer(markerEdit);
        markerEdit = null;
    }

    // munculkan kembali marker asli
    if (window.markerYangDiedit) {
        observasiLayer.addLayer(window.markerYangDiedit);
        window.markerYangDiedit = null;
    }
    
    window.titikEditBaru = null;

    sidebar.classList.remove("sidebar-open");
    sidebar.style.width = "0";

    setTimeout(() => {
        sidebar.style.display = "none";
    }, 300);

    map.setView([-2, 118], 5.4);
}

  /* ================= STYLING PROVINSI ================= */

  const ZONA_ASIATIS = [
    "ACEH", "SUMATERA UTARA", "SUMATERA BARAT", "KEPULAUAN RIAU", "RIAU", "JAMBI",
    "SUMATERA SELATAN", "BENGKULU", "LAMPUNG", "BANTEN", "DKI JAKARTA", "JAWA BARAT",
    "JAWA TENGAH", "DAERAH ISTIMEWA YOGYAKARTA", "JAWA TIMUR", "KALIMANTAN BARAT",
    "KALIMANTAN TENGAH", "KALIMANTAN SELATAN", "KALIMANTAN TIMUR", "KALIMANTAN UTARA",
    "BALI", "KEPULAUAN BANGKA BELITUNG"
  ];
  const ZONA_PERALIHAN = [
    "SULAWESI UTARA", "SULAWESI TENGAH", "SULAWESI SELATAN", "SULAWESI TENGGARA",
    "GORONTALO", "SULAWESI BARAT", "NUSA TENGGARA BARAT", "NUSA TENGGARA TIMUR",
    "MALUKU", "MALUKU UTARA"
  ];
  const ZONA_AUSTRALIS = [
    "PAPUA", "PAPUA BARAT", "PAPUA SELATAN", "PAPUA TENGAH", "PAPUA PEGUNUNGAN", "PAPUA BARAT DAYA"
  ];

  function ambilNamaProvinsi(feature) {
    return (feature.properties.PROVINSI || feature.properties.NAMOBJ || "").trim().toUpperCase();
  }

  function getBiogeografiColor(namaProv) {
    if (ZONA_ASIATIS.includes(namaProv)) return ASIATIS;
    if (ZONA_PERALIHAN.includes(namaProv)) return PERALIHAN;
    if (ZONA_AUSTRALIS.includes(namaProv)) return AUSTRALIS;
    return "#E0E0E0";
  }

  function stylePolygon(feature) {
    return { color: "#455A64", weight: 1, fillColor: getBiogeografiColor(ambilNamaProvinsi(feature)), fillOpacity: 0.85 };
  }

  function stylePolyline(feature) {
    return { color: "#455A64", weight: 2, fillOpacity: 0 };
  }

  /* ================= LOAD LAYER PROVINSI (POLYGON + POLYLINE) ================= */
  fetch(window.routeProvinsiGeojson)
    .then(r => r.json())
    .then(data => {
      provinsiPolygonLayer = L.geoJSON(data, {
        style: stylePolygon,
        onEachFeature: (feature, layer) => {
          const namaProv = ambilNamaProvinsi(feature) || "Tidak diketahui";
          layer.bindTooltip(namaProv, { sticky: true });
        }
      });

      provinsiPolylineLayer = L.geoJSON(data, { style: stylePolyline });
      provinsiPolygonLayer.addTo(map);
      daftarkanLayerControl();
    });


  /* ================= LOAD LAYER MARKER FAUNA KHAS (TITIK REPRESENTATIF) ================= */
  function loadTitikFaunaKhas() {
    faunaKhasLayer.clearLayers();
    semuaTitik = semuaTitik.filter(t => t.sumber !== "fauna_khas");
    fetch(window.routeTitikFaunaKhasGeojson)
      .then(r => r.json())
      .then(data => {
        data.features.forEach(f => {
          const [lng, lat] = f.geometry.coordinates;
          const m = L.marker([lat, lng], { icon: iconTitikFauna });
          const fotoUrl = `storage/${f.properties.foto_referensi ?? ''}`;

          m.bindPopup(`
            <div class="popup-simple text-center">
              <img src="${fotoUrl}" onerror="this.style.visibility='hidden'">
              <div class="fw-bold mt-2">${f.properties.nama_lokal}</div>
              <div class="text-muted small">${f.properties.nama_provinsi}</div>
            </div>
          `);
          m.on("click", () => bukaSidebarFaunaKhas(f.properties));
          faunaKhasLayer.addLayer(m);

          semuaTitik.push({
            sumber: "fauna_khas",
            nama: f.properties.nama_lokal,
            lat, lng,
            foto: fotoUrl,
            layer: m,
          });
        });
      });
  }
  loadTitikFaunaKhas();

  /* ================= SIDEBAR INFO FAUNA KHAS ================= */
  function bukaSidebarFaunaKhas(p) {
    const fotoUrl = `storage/${p.foto_referensi ?? ''}`;
    openSidebar("Informasi Fauna", `
      <img src="${fotoUrl}" class="fauna-img-sidebar w-100 mb-3" onerror="this.style.visibility='hidden'">
      <h5 class="fw-bold mb-1">${p.nama_lokal}</h5>
      <p class="fst-italic text-muted mb-2">${p.nama_ilmiah || "Nama ilmiah belum tercatat"}</p>
      <span class="badge ${p.status_konservasi === "dilindungi" ? "bg-success" : "bg-secondary"} mb-3">
        ${p.status_konservasi || "Status tidak diketahui"}
      </span>
      <p class="small text-muted mb-2">Provinsi: ${p.nama_provinsi || "—"}</p>
      <p>${p.deskripsi || "Belum ada deskripsi untuk fauna ini."}</p>
    `);
  }

  /* ================= LOAD LAYER OBSERVASI WARGA ================= */
  function badgeStatus(status) {
    return {
      terverifikasi: 'bg-success',
      pending: 'bg-secondary',
      ditolak: 'bg-danger',
    }[status] || 'bg-secondary';
  }

  function labelStatus(status) {
    return {
      terverifikasi: 'Terverifikasi',
      pending: 'Menunggu verifikasi',
      ditolak: 'Ditolak',
    }[status] || status;
  }

  function loadObservasiWarga() {
    window.markerObservasi = {};
    observasiLayer.clearLayers();
    semuaTitik = semuaTitik.filter(t => t.sumber !== "observasi");
    fetch(window.routeObservasiGeojson)
      .then(r => r.json())
      .then(data => {
        data.features.forEach(f => {
          const [lng, lat] = f.geometry.coordinates;
          const m = L.marker([lat, lng], { icon: iconTitikFauna });
          window.markerObservasi[f.properties.id] = m;
          const milikSaya = window.currentUserId && f.properties.user_id === window.currentUserId;
          const bisaKelola = milikSaya || window.currentUserRole === 'verifikator' || window.currentUserRole === 'admin';
          const fotoUrl = `storage/observasi/${f.properties.foto}`;

          m.bindPopup(`
            <div class="popup-simple text-center">
              <img src="${fotoUrl}" onerror="this.style.visibility='hidden'">
              <div class="fw-bold mt-2">${f.properties.nama}</div>
              <div class="text-muted small">Dilaporkan oleh ${f.properties.nama_pengamat}</div>
              <div class="text-muted small">${f.properties.tanggal_amatan}</div>
              <span class="badge mt-1 ${badgeStatus(f.properties.status_verifikasi)}">${labelStatus(f.properties.status_verifikasi)}</span>
              ${bisaKelola ? `
                <div class="d-flex gap-2 mt-2">
                  <button class="btn btn-sm btn-warning btn-fill-aksi w-50" onclick="mulaiEditObservasi(${f.properties.id})">Edit</button>
                  <button class="btn btn-sm btn-danger btn-fill-aksi w-50" onclick="hapusObservasi(${f.properties.id})">Hapus</button>
                </div>
              ` : ''}
            </div>
          `);
          m.on("click", () => bukaSidebarObservasi(f.properties));
          observasiLayer.addLayer(m);

          semuaTitik.push({
            sumber: "observasi",
            nama: f.properties.nama,
            lat, lng,
            foto: fotoUrl,
            layer: m,
          });
        });
      });
  }
  loadObservasiWarga();

  /* ================= SIDEBAR INFO OBSERVASI WARGA ================= */
  function bukaSidebarObservasi(p) {
    const fotoUrl = `storage/observasi/${p.foto}`;
    openSidebar("Informasi Fauna (Observasi Warga)", `
      <img src="${fotoUrl}" class="fauna-img-sidebar w-100 mb-3" onerror="this.style.visibility='hidden'">
      <h5 class="fw-bold mb-1">${p.nama}</h5>
      <p class="fst-italic text-muted mb-2">${p.nama_ilmiah || "Belum teridentifikasi"}</p>
      <span class="badge ${badgeStatus(p.status_verifikasi)} mb-3">${labelStatus(p.status_verifikasi)}</span>
      <p class="small text-muted mb-2">Dilaporkan oleh ${p.nama_pengamat} &middot; ${p.tanggal_amatan}</p>
      <p>${p.catatan || "Belum ada deskripsi tambahan dari pelapor."}</p>
    `);
  }

  /* ================= LAYER CONTROL ================= */
  function daftarkanLayerControl() {
    const overlays = {
      "Batas provinsi (area)": provinsiPolygonLayer,
      "Batas provinsi (garis)": provinsiPolylineLayer,
      "Fauna khas": faunaKhasLayer,
      "Observasi warga": observasiLayer
    };
    faunaKhasLayer.addTo(map);
    observasiLayer.addTo(map);
    L.control.layers(null, overlays, { collapsed: window.innerWidth < 768 }).addTo(map);
  }

  /* ================= GARIS WALLACE & WEBER ================= */
  function addLineLabel(lat, lng, text) {
    return L.marker([lat, lng], {
      icon: L.divIcon({
        className: "line-label",
        html: text,
        iconSize: [120, 20],
        iconAnchor: [60, 0]
      }),
      interactive: false
    }).addTo(map);
  }

  const wallaceLine = L.polyline(
    [[6, 118], [-11, 118]],
    { color: "#D32F2F", weight: 2, dashArray: "6,6", opacity: 0.9 }
  ).addTo(map);

  wallaceLine.bindTooltip("Garis Wallace", { permanent: false, direction: "top", className: "line-tooltip" });
  addLineLabel(6, 118, "Garis Wallace");

  const weberLine = L.polyline(
    [[6, 130], [-11, 130]],
    { color: "#1976D2", weight: 2, dashArray: "6,6", opacity: 0.9 }
  ).addTo(map);

  weberLine.bindTooltip("Garis Weber", { permanent: false, direction: "top", className: "line-tooltip" });
  addLineLabel(6, 130, "Garis Weber");

  /* ================= LEGENDA ================= */
  const legend = L.control({ position: "bottomleft" });
  legend.onAdd = function () {
    const div = L.DomUtil.create("div", "map-legend");
    div.innerHTML = `
      <h6 class="legend-title">Legenda</h6>
      <div class="legend-section">
        <div class="legend-item">
          <span class="legend-marker-pin"><span>🐾</span></span>
          Titik Fauna
        </div>
      </div>
      <hr>
      <div class="legend-section">
        <div class="legend-item">
          <span class="legend-line wallace"></span>
          Garis Wallace
        </div>
        <div class="legend-item">
          <span class="legend-line weber"></span>
          Garis Weber
        </div>
      </div>
      <hr>
      <div class="legend-section">
        <div class="legend-item"><span class="legend-box provinsi-asiatis"></span>Daerah Asiatis</div>
        <div class="legend-item"><span class="legend-box provinsi-peralihan"></span>Daerah Peralihan</div>
        <div class="legend-item"><span class="legend-box provinsi-australis"></span>Daerah Australis</div>
      </div>
    `;
    return div;
  };
  legend.addTo(map);
</script>
@endpush

@endsection
