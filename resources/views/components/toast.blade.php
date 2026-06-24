<div id="toast-container" class="toast-container"></div>

@if (session('success'))
  <script>document.addEventListener('DOMContentLoaded', () => tampilkanToast('{{ session('success') }}', 'success'));</script>
@endif

@if (session('error'))
  <script>document.addEventListener('DOMContentLoaded', () => tampilkanToast('{{ session('error') }}', 'error'));</script>
@endif

@if ($errors->any())
  <script>document.addEventListener('DOMContentLoaded', () => tampilkanToast('{{ $errors->first() }}', 'error'));</script>
@endif

<script>
  function tampilkanToast(pesan, tipe = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `quiz-alert ${tipe === 'success' ? 'quiz-correct' : 'quiz-wrong'}`;
    toast.textContent = pesan;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  }
</script>
