<div id="instruksi-klik-peta" class="instruksi-klik d-none">
  Tandai lokasi baru di peta untuk laporan ini
</div>

@push('scripts')
<script>
  /* ================= EDIT OBSERVASI TITIK ================= */
  let markerEdit = null;
  function mulaiEditObservasi(id) {
    map.closePopup();
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

    window.titikEditBaru = null;

    window.markerYangDiedit = window.markerObservasi[data.id];

    if (window.markerYangDiedit) {
        map.removeLayer(window.markerYangDiedit);
    }
    if (markerEdit) {
        map.removeLayer(markerEdit);
    }

    markerEdit = L.marker([lat, lng], {
        draggable: true,
        icon: iconTitikFauna
    }).addTo(map);

    markerEdit.bindPopup(
        "Geser marker untuk mengubah lokasi observasi"
    ).openPopup();

    markerEdit.on("dragend", function (e) {

    const posisiBaru = e.target.getLatLng();

    window.titikEditBaru = {
        lat: posisiBaru.lat,
        lng: posisiBaru.lng
    };

    tampilkanToast(
        "Lokasi baru dipilih. Klik Simpan Perubahan untuk menerapkan.",
        "success"
    );
});

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
      <p class="small text-muted mb-2"> Geser marker pada peta untuk mengubah lokasi observasi.</p>
      <button class="btn btn-success w-100" onclick="simpanEditObservasi(${data.id})">Simpan Perubahan</button>
    `);

    window.titikEditBaru = null;

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

        if (markerEdit) {
            map.removeLayer(markerEdit);
            markerEdit = null;
        }

        window.markerYangDiedit = null;
        
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
