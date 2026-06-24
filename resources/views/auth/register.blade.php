@extends('auth.guest')

@section('title', 'Daftar — Jejak Fauna Nusantara')

@section('content')

<h1 class="auth-title">Buat Akun Baru</h1>

<form method="POST" action="{{ route('register') }}">
  @csrf

  <div class="mb-3">
    <label for="name" class="auth-label">Nama Lengkap</label>
    <input
      id="name"
      type="text"
      name="name"
      class="form-control auth-input @error('name') is-invalid @enderror"
      placeholder="Nama kamu"
      value="{{ old('name') }}"
      required
      autofocus
    >
    @error('name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

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
    >
    @error('email')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label for="password" class="auth-label">Kata Sandi</label>
    <input
      id="password"
      type="password"
      name="password"
      class="form-control auth-input @error('password') is-invalid @enderror"
      placeholder="Kata sandi"
      required
      autocomplete="new-password"
    >
    @error('password')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-4">
    <label for="password_confirmation" class="auth-label">Konfirmasi Kata Sandi</label>
    <input
      id="password_confirmation"
      type="password"
      name="password_confirmation"
      class="form-control auth-input"
      placeholder="Ulangi kata sandi"
      required
      autocomplete="new-password"
    >
  </div>

  <button type="submit" class="btn btn-success w-100 auth-submit-btn">Daftar</button>
</form>

<p class="auth-footer-text">
  Sudah punya akun? <a href="{{ route('login') }}" class="auth-link">Masuk</a>
</p>

@endsection
