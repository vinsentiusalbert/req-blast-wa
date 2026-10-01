@extends('layouts.auth')
@section('title', 'Masuk')
@section('content')
<main class="auth-shell" id="main-content">
    <section class="auth-frame" aria-label="Masuk ke ShopAds">
        <div class="auth-hero">
            <div class="auth-hero-inner">
                <div>
                    <a class="brand-lockup" href="{{ route('login') }}"><div class="brand-mark"><img src="{{ asset('assets/shopads-icon.svg') }}" alt="" class="brand-logo"></div><p class="brand-subtitle">ShopAds</p></a>
                    <div class="hero-copy">
                        <p class="eyebrow">Selamat Datang</p>
                        <h1 class="auth-title">Tingkatkan penjualan toko Anda dengan periklanan tertarget ShopAds.</h1>
                    </div>
                </div>
                <div class="hero-footer"><p>Senang berjumpa kembali. Masuk ke akun Anda untuk mengakses dashboard.</p><span>&copy; ShopAds, {{ date('Y') }}</span></div>
            </div>
        </div>
        <div class="auth-panel">
            <div class="panel-card">
                <div class="panel-topbar"><span class="panel-register">Belum punya akun?</span><a href="{{ route('register') }}" class="panel-link">Daftar sekarang</a></div>
                <h2 class="panel-title">Masuk akun ShopAds</h2>
                <p class="panel-copy">Masukkan username dan password Anda untuk melanjutkan.</p>
                @include('partials.notices')
                <form method="POST" action="{{ route('login') }}" class="login-form">
                    @csrf
                    <div class="field-group"><label for="username" class="field-label">Username</label><input class="text-input" id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Masukkan username Anda" autocomplete="username" autocapitalize="none" spellcheck="false" minlength="3" maxlength="50" required autofocus @error('username') aria-invalid="true" @enderror></div>
                    <div class="field-group"><label for="password" class="field-label">Password</label><input class="text-input" id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required @error('password') aria-invalid="true" @enderror></div>
                    <div class="field-actions"><label class="checkbox-label"><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Ingat saya</span></label></div>
                    <button class="submit-btn" type="submit">Masuk</button>
                    <p class="register-prompt">Belum memiliki akun? <a href="{{ route('register') }}">Daftar di sini</a></p>
                </form>
            </div>
        </div>
    </section>
</main>
@endsection
