@extends('auth.app')

@section('title', 'Kuis Fauna Nusantara')

@section('content')

<div class="container mt-5 pt-4 pb-5" style="max-width: 640px;">
  <h3 class="fw-bold mb-1">Kuis Fauna Nusantara</h3>
  <p class="text-muted mb-4">
    Uji wawasanmu soal satwa khas Indonesia, atau bantu identifikasi temuan warga lain dan kumpulkan poin.
  </p>

  <div id="area-pilih-mode" class="d-grid gap-2">
    <button class="btn btn-primary" onclick="mulaiKuis('edukasi')">Kuis Edukasi</button>
    <button class="btn btn-outline-primary" onclick="mulaiKuis('tantangan_identifikasi')">Tebak Temuan Warga</button>
  </div>

  <div id="area-kuis-aktif" class="card shadow-sm d-none mt-3">
    <div class="card-body" id="isi-kuis-aktif"></div>
  </div>
</div>

@endsection

@push('scripts')
<script>
  window.csrfToken = "{{ csrf_token() }}";

  let soalKuis = [];
  let indeksSoal = 0;
  let jumlahBenar = 0;

  function tampilkanIsiKuis(html) {
    document.getElementById("area-pilih-mode").classList.add("d-none");
    document.getElementById("area-kuis-aktif").classList.remove("d-none");
    document.getElementById("isi-kuis-aktif").innerHTML = html;
  }

  function kembaliKePilihanMode() {
    document.getElementById("area-kuis-aktif").classList.add("d-none");
    document.getElementById("area-pilih-mode").classList.remove("d-none");
  }

  function mulaiKuis(tipe = "edukasi") {
    fetch(`/api/kuis/soal?tipe=${tipe}`, {
      headers: { "X-CSRF-TOKEN": window.csrfToken }
    })
      .then(r => r.json())
      .then(data => {
        soalKuis = data;
        indeksSoal = 0;
        jumlahBenar = 0;

        if (!soalKuis.length) {
          tampilkanToast("Belum ada soal tersedia untuk mode ini. Coba mode lain.", "error");
          return;
        }

        tampilkanSoal();
      });
  }

  function tampilkanSoal() {
    if (indeksSoal >= soalKuis.length) {
      selesaikanKuis();
      return;
    }

    const soal = soalKuis[indeksSoal];
    const konteks = soal.tipe_soal === "tantangan_identifikasi"
      ? `Foto ini dilaporkan oleh pengamat lain di ${soal.provinsi_konteks || "Indonesia"}. Bantu identifikasi spesiesnya.`
      : `Fauna khas dari ${soal.provinsi_konteks || "Indonesia"}`;

    tampilkanIsiKuis(`
      <p class="text-muted small mb-1">Soal ${indeksSoal + 1} dari ${soalKuis.length}</p>
      <img src="storage/${soal.foto}" class="fauna-img-sidebar w-100 mb-2">
      <p class="mb-1 text-muted small">${konteks}</p>
      <input id="jawaban-kuis" class="form-control mb-2" placeholder="Nama fauna">
      <div id="feedback-kuis" class="mb-2"></div>
      <button class="btn btn-primary w-100" onclick="jawabSoal(${soal.id})">Jawab</button>
    `);
  }

  function jawabSoal(soalId) {
    const jawaban = document.getElementById("jawaban-kuis").value.trim();
    if (!jawaban) return;

    fetch(`/api/kuis/soal/${soalId}/jawab`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": window.csrfToken
      },
      body: JSON.stringify({ jawaban })
    })
      .then(r => r.json())
      .then(data => {
        if (data.benar) jumlahBenar++;

        const feedback = document.getElementById("feedback-kuis");
        feedback.innerHTML = data.benar
          ? `<div class="alert alert-success text-center p-2">Jawaban benar!</div>`
          : `<div class="alert alert-danger text-center p-2">Kurang tepat. Jawaban: ${data.jawaban_benar}</div>`;

        indeksSoal++;
        setTimeout(tampilkanSoal, 1200);
      });
  }

  function selesaikanKuis() {
    fetch("/api/kuis/selesai", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": window.csrfToken
      },
      body: JSON.stringify({ jumlah_benar: jumlahBenar, jumlah_soal: soalKuis.length })
    })
      .then(r => r.json())
      .then(data => {
        showConfetti();
        tampilkanIsiKuis(`
          <div class="alert alert-success text-center">
            <h5 class="fw-bold mb-2">Hebat!</h5>
            Kamu menjawab benar ${jumlahBenar} dari ${soalKuis.length} soal.
          </div>
          <p class="text-center">+${data.poin_didapat} poin &middot; Total poin: ${data.total_poin}</p>
          <button class="btn btn-success w-100 mb-2" onclick="mulaiKuis('edukasi')">Main Lagi</button>
          <button class="btn btn-outline-secondary w-100" onclick="kembaliKePilihanMode()">Tutup</button>
        `);
      });
  }

  function showConfetti() {
    const canvas = document.createElement("canvas");
    canvas.style.position = "fixed";
    canvas.style.top = "0";
    canvas.style.left = "0";
    canvas.style.width = "100vw";
    canvas.style.height = "100vh";
    canvas.style.pointerEvents = "none";
    canvas.style.zIndex = "2000";
    document.body.appendChild(canvas);

    const myConfetti = confetti.create(canvas, { resize: true, useWorker: true });
    const duration = 3 * 1000;
    const end = Date.now() + duration;

    (function frame() {
      myConfetti({ particleCount: 6, angle: 60, spread: 70, origin: { x: 0 } });
      myConfetti({ particleCount: 6, angle: 120, spread: 70, origin: { x: 1 } });
      if (Date.now() < end) {
        requestAnimationFrame(frame);
      } else {
        document.body.removeChild(canvas);
      }
    })();
  }
</script>
@endpush
