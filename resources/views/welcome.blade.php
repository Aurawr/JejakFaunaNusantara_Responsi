{{--
  File bawaan default Laravel. Tidak dipakai di project ini karena route
  utama "/" sudah diarahkan ke PageController::home() lewat routes/web.php.
  File ini dibiarkan kosong-redirect sebagai pengaman jika ada yang
  mengarah ke view 'welcome' secara tidak sengaja.
--}}
<script>window.location.href = "{{ route('home') }}";</script>
