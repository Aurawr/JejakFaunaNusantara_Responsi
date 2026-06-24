<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>@yield('title', 'Jejak Fauna Nusantara')</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body class="auth-page">

<x-toast />

<div class="auth-container">
  <div class="auth-card">
    <a href="{{ route('home') }}" class="auth-logo-link">
      <span class="auth-logo">🐾</span>
    </a>
    @yield('content')
  </div>
</div>

@stack('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
