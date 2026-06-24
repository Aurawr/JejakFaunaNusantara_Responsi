@extends('auth.guest')

@section('title', 'Masuk — Jejak Fauna Nusantara')

@section('content')

<h1 class="auth-title">Masuk ke Akunmu</h1>
<p class="auth-subtitle">Masukkan email dan kata sandi</p>

@if (session('status'))
  <div class="alert alert-success small">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('login') }}">
  @csrf

  <div class="mb-3">
    <label for="email" class="auth-label">Alamat Email</label>
    <input
      id="email"
      type="email"
      name="email"
      class="form-control auth-input @error('email') is-invalid @enderror"
      placeholder="email@contoh.com"
      value="{{ old('email') }}"
      required
      autofocus
    >
    @error('email')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <label for="password" class="auth-label mb-0">Kata Sandi</label>
      @if (Route::has('password.request'))
        <a href="{{ route('password.request') }}" class="auth-link-small">Lupa kata sandi?</a>
      @endif
    </div>
    <input
      id="password"
      type="password"
      name="password"
      class="form-control auth-input @error('password') is-invalid @enderror"
      placeholder="Kata sandi"
      required
      autocomplete="current-password"
    >
    @error('password')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-4 form-check">
    <input type="checkbox" name="remember" id="remember" class="form-check-input">
    <label for="remember" class="form-check-label auth-label-checkbox">Ingat saya</label>
  </div>

  <button type="submit" class="btn btn-success w-100 auth-submit-btn">Masuk</button>
</form>

<p class="auth-footer-text">
  Belum punya akun? <a href="{{ route('register') }}" class="auth-link">Daftar</a>
</p>

@endsection
