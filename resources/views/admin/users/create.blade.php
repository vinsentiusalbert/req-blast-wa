@extends('layouts.app')
@section('title', 'Tambah Pengguna')
@section('content')
<a href="{{ route('admin.users.index') }}" class="back-link">&larr; Kembali ke pengguna</a>
<div class="page-heading"><div><span class="eyebrow">MANAJEMEN PENGGUNA</span><h1>Tambah pengguna</h1><p class="muted">Buat akun baru dengan akses user. Peran dapat diubah melalui daftar pengguna.</p></div></div>
    <section class="card user-create-card" aria-label="Data pengguna baru">
        <form method="POST" action="{{ route('admin.users.store') }}" class="login-form">
            @csrf
            <div class="field-group"><label class="field-label" for="name">Nama lengkap</label><input class="text-input" id="name" name="name" value="{{ old('name') }}" placeholder="Nama lengkap Anda" autocomplete="name" maxlength="255" required autofocus></div>
            <div class="field-group"><label class="field-label" for="username">Username</label><input class="text-input" id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Contoh: toko.berkah" autocomplete="username" autocapitalize="none" spellcheck="false" minlength="3" maxlength="50" aria-describedby="username-help" required><small class="field-hint" id="username-help">3–50 karakter. Gunakan huruf, angka, titik, garis bawah, atau tanda hubung. Awali dengan huruf atau angka.</small></div>
            <div class="field-group"><label class="field-label" for="email">Alamat email</label><input class="text-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@toko.com" autocomplete="email" maxlength="255" required></div>
            <div class="field-group"><label class="field-label" for="password">Password</label><input class="text-input" id="password" name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="new-password" minlength="8" aria-describedby="password-help" required><small class="field-hint" id="password-help">Minimal 8 karakter, mengandung huruf dan angka.</small></div>
            <div class="field-group"><label class="field-label" for="password_confirmation">Konfirmasi password</label><input class="text-input" id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi password" autocomplete="new-password" minlength="8" required></div>
            <button class="submit-btn" type="submit">Buat akun pengguna</button>
            <p class="register-prompt"><a href="{{ route('admin.users.index') }}">Batal</a></p>
        </form>
    </section>
@endsection
