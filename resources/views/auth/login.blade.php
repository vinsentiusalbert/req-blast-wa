@extends('layouts.auth')
@section('title', 'Masuk')
@section('content')
<main class="auth-shell" id="main-content">
    <section class="auth-frame" aria-label="Masuk ke Ruang Pesan">
        <div class="auth-hero">
            <div class="auth-hero-inner">
                <div>
                    <a class="brand-lockup" href="{{ route('login') }}"><div class="brand-mark"><img src="{{ asset('assets/ruang-pesan.svg') }}" alt="" class="brand-logo"></div><p class="brand-subtitle">Ruang Pesan</p></a>
                    <div class="hero-copy">
                        <p class="eyebrow">RUANG UNTUK TERHUBUNG</p>
                        <h1 class="auth-title">Pesan yang tepat.<br>Hubungan yang lebih dekat.</h1>
                        <p class="auth-description">Kelola template dan campaign WhatsApp dalam satu ruang yang rapi. Dari ide pesan hingga siap dikirim.</p>
                        <div class="auth-message" aria-hidden="true">
                            <div class="auth-message__label"><span class="status-dot"></span> PRATINJAU PESAN</div>
                            <div class="auth-message__bubble"><strong>Halo, selamat datang 👋</strong><p>Setiap percakapan adalah awal dari hubungan yang baik. Mari buat pesan Anda lebih berarti.</p></div>
                            <span class="auth-message__foot">Dirancang untuk percakapan yang lebih baik</span>
                        </div>
                    </div>
                </div>
                <div class="hero-footer"><p>Satu ruang. Setiap percakapan.</p><span>&copy; {{ date('Y') }} Ruang Pesan</span></div>
            </div>
        </div>
        <div class="auth-panel">
            <div class="panel-card">
                <h2 class="panel-title">Masuk akun Ruang Pesan</h2>
                <p class="panel-copy">Masukkan username dan password Anda untuk melanjutkan.</p>
                @include('partials.notices')
                <form method="POST" action="{{ route('login') }}" class="login-form">
                    @csrf
                    <div class="field-group"><label for="username" class="field-label">Username</label><input class="text-input" id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Masukkan username Anda" autocomplete="username" autocapitalize="none" spellcheck="false" minlength="3" maxlength="50" required autofocus @error('username') aria-invalid="true" @enderror></div>
                    <div class="field-group"><label for="password" class="field-label">Password</label><input class="text-input" id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required @error('password') aria-invalid="true" @enderror></div>
                    <div class="field-actions"><label class="checkbox-label"><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Ingat saya</span></label></div>
                    <button class="submit-btn" type="submit">Masuk</button>
                    <p class="register-prompt">Belum memiliki akun? Hubungi admin untuk dibuatkan akun.</p>
                </form>
            </div>
        </div>
    </section>
</main>
@endsection
