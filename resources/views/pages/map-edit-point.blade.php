{{--
  File ini khusus menangani geometri titik (Point) untuk observasi fauna
  yang SUDAH ADA di peta: edit dan hapus. Di-include oleh map.blade.php.
  Fungsi loadObservasiWarga() dipanggil dari sini karena observasi memang
  selalu berupa titik di platform ini.

  Form untuk lapor observasi BARU sekarang punya halaman sendiri di
  /lapor (lihat pages/lapor.blade.php), bukan FAB+modal di atas peta ini
  lagi, supaya peta utama fokus untuk eksplorasi data.
--}}

<div id="instruksi-klik-peta" class="instruksi-klik d-none">
  Tandai lokasi baru di peta untuk laporan ini
</div>

@push('scripts')
<script>
  /* ================= EDIT OBSERVASI TITIK ================= */
  function mulaiEditObservasi(id) {
    fetch(`/api/observasi/${id}`, {
      headers: { "X-CSRF-TOKEN": window.csrfToken }
    })
      .then(r => r.json())
      .then(data => {
        if (data.message) {
          tampilkanToast(data.message, "error");
          return;
        }
        bukaFormEditAtribut(data);
      });
  }

  function bukaFormEditAtribut(data) {
    const [lng, lat] = data.geometry.coordinates;
    map.setView([lat, lng], 14);

    openSidebar("Ubah Laporan Observasi", `
      <div class="mb-2 text-center">
        <img class="fauna-img-sidebar w-100" src="storage/observasi/${data.foto}" alt="Foto saat ini">
        <p class="small text-muted mt-1">Foto saat ini. Pilih file baru di bawah untuk mengganti.</p>
      </div>
      <label class="form-label small fw-semibold">Ganti foto (opsional)</label>
      <input id="input-foto-edit" type="file" accept="image/*" class="form-control mb-2">
      <label class="form-label small fw-semibold">Nama fauna (jika tahu)</label>
      <input id="input-nama-usulan-edit" class="form-control mb-2" value="${data.nama_usulan || ''}">
      <label class="form-label small fw-semibold">Tanggal pengamatan</label>
      <input id="input-tanggal-edit" type="date" class="form-control mb-2" value="${data.tanggal_amatan}">
      <label class="form-label small fw-semibold">Catatan tambahan</label>
      <textarea id="input-catatan-edit" class="form-control mb-2" rows="2">${data.catatan || ''}</textarea>
      <p class="small text-muted mb-2">Klik di peta untuk memindahkan titik lokasi, atau biarkan jika tidak berubah.</p>
      <button class="btn btn-outline-secondary w-100 mb-2" onclick="aktifkanModeKlikUlangTitik(${data.id})">Pindahkan Titik di Peta</button>
      <button class="btn btn-success w-100" onclick="simpanEditObservasi(${data.id})">Simpan Perubahan</button>
    `);

    window.titikEditBaru = null;
  }

  function aktifkanModeKlikUlangTitik(id) {
    document.getElementById("instruksi-klik-peta").classList.remove("d-none");
    map.getContainer().style.cursor = "crosshair";
    map.once("click", (e) => {
      window.titikEditBaru = { lat: e.latlng.lat, lng: e.latlng.lng };
      document.getElementById("instruksi-klik-peta").classList.add("d-none");
      map.getContainer().style.cursor = "";
      tampilkanToast("Titik baru dicatat. Klik 'Simpan Perubahan' untuk menyimpan.", "success");
    });
  }

  function simpanEditObservasi(id) {
    const formData = new FormData();
    formData.append("_method", "PATCH");
    formData.append("nama_usulan", document.getElementById("input-nama-usulan-edit").value);
    formData.append("tanggal_amatan", document.getElementById("input-tanggal-edit").value);
    formData.append("catatan", document.getElementById("input-catatan-edit").value);

    const fotoBaru = document.getElementById("input-foto-edit").files[0];
    if (fotoBaru) formData.append("foto", fotoBaru);

    if (window.titikEditBaru) {
      formData.append("geometry_point", `POINT(${window.titikEditBaru.lng} ${window.titikEditBaru.lat})`);
    }

    fetch(`/api/observasi/${id}`, {
      method: "POST",
      headers: { "X-CSRF-TOKEN": window.csrfToken },
      body: formData
    })
      .then(r => r.json())
      .then(data => {
        closeSidebar();
        tampilkanToast(data.message || "Perubahan tersimpan.", "success");
        loadObservasiWarga();
      })
      .catch(() => tampilkanToast("Gagal menyimpan perubahan. Periksa koneksi dan coba lagi.", "error"));
  }

  /* ================= HAPUS OBSERVASI TITIK ================= */
  function hapusObservasi(id) {
    if (!window.confirm("Yakin ingin menghapus laporan observasi ini? Tindakan ini tidak dapat dibatalkan.")) return;

    fetch(`/api/observasi/${id}`, {
      method: "DELETE",
      headers: { "X-CSRF-TOKEN": window.csrfToken }
    })
      .then(r => r.json())
      .then(data => {
        map.closePopup();
        tampilkanToast(data.message || "Laporan berhasil dihapus.", "success");
        loadObservasiWarga();
      })
      .catch(() => tampilkanToast("Gagal menghapus laporan. Periksa koneksi dan coba lagi.", "error"));
  }
</script>
@endpush
