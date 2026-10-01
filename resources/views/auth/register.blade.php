@extends('layouts.auth')
@section('title', 'Daftar')
@section('content')
<main class="auth-simple-shell" id="main-content">
    <section class="auth-simple-card" aria-labelledby="register-title">
        <a href="{{ route('login') }}" class="back-link">&larr; Kembali ke login</a>
        <div class="simple-header">
            <a class="portal-brand register-brand" href="{{ route('login') }}"><img src="{{ asset('assets/shopads-icon.svg') }}" alt="" class="portal-brand__logo"><span class="portal-brand__text">ShopAds</span></a>
            <h1 class="simple-title" id="register-title">Daftar Akun ShopAds</h1>
            <p class="simple-copy">Lengkapi data berikut untuk membuat akun Anda. Akun baru terdaftar sebagai user.</p>
        </div>
        @include('partials.notices')
        <form method="POST" action="{{ route('register') }}" class="login-form">
            @csrf
            <div class="field-group"><label class="field-label" for="name">Nama lengkap</label><input class="text-input" id="name" name="name" value="{{ old('name') }}" placeholder="Nama lengkap Anda" autocomplete="name" maxlength="255" required autofocus></div>
            <div class="field-group"><label class="field-label" for="username">Username</label><input class="text-input" id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Contoh: toko.berkah" autocomplete="username" autocapitalize="none" spellcheck="false" minlength="3" maxlength="50" aria-describedby="username-help" required><small class="field-hint" id="username-help">3–50 karakter. Gunakan huruf, angka, titik, garis bawah, atau tanda hubung. Awali dengan huruf atau angka.</small></div>
            <div class="field-group"><label class="field-label" for="email">Alamat email</label><input class="text-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@toko.com" autocomplete="email" maxlength="255" required></div>
            <div class="field-group"><label class="field-label" for="password">Password</label><input class="text-input" id="password" name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="new-password" minlength="8" aria-describedby="password-help" required><small class="field-hint" id="password-help">Minimal 8 karakter, mengandung huruf dan angka.</small></div>
            <div class="field-group"><label class="field-label" for="password_confirmation">Konfirmasi password</label><input class="text-input" id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi password" autocomplete="new-password" minlength="8" required></div>
            <button class="submit-btn" type="submit">Daftar Akun</button>
            <p class="register-prompt">Sudah memiliki akun? <a href="{{ route('login') }}">Masuk di sini</a></p>
        </form>
    </section>
</main>
@endsection
