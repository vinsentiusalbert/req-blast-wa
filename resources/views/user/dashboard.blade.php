@extends('layouts.app')
@section('title', 'Dashboard User')
@section('content')
<div class="page-heading"><div><span class="eyebrow">DASHBOARD USER</span><h1>Halo, {{ auth()->user()->name }}<span class="accent-dot">.</span></h1><p class="muted">Selamat datang kembali. Kelola informasi akun Anda di sini.</p></div><span class="badge user"><span class="status-dot"></span> Akun aktif</span></div>
<section class="account-banner"><div><h2>Broadcast WhatsApp untuk pelanggan Anda</h2><p>Ajukan template untuk persetujuan admin, lalu kelola broadcast pada menu terpisah.</p></div><a class="button" href="{{ route('user.whatsapp.broadcasts.index') }}">Buka Broadcast WhatsApp &rarr;</a></section>
@include('partials.whatsapp-dashboard-stats')
    <section class="card profile" id="profile"><span class="eyebrow">PROFIL ANDA</span><h2>Informasi akun</h2><dl><div><dt>Nama lengkap</dt><dd>{{ auth()->user()->name }}</dd></div><div><dt>Username</dt><dd>{{ auth()->user()->username }}</dd></div><div><dt>Alamat email</dt><dd>{{ auth()->user()->email }}</dd></div><div><dt>Role</dt><dd><span class="badge user">User</span></dd></div><div><dt>Tanggal bergabung</dt><dd>{{ auth()->user()->created_at->format('d M Y') }}</dd></div></dl></section>
@endsection
