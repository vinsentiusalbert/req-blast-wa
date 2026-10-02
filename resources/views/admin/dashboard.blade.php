@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">DASHBOARD ADMIN</span><h1>Halo, {{ auth()->user()->name }}<span class="accent-dot">.</span></h1><p class="muted">Selamat datang kembali. Berikut ringkasan akun dan pengguna Anda.</p></div>
    <span class="date-label">{{ now()->format('d / m / Y') }}</span>
</div>
<section class="account-banner"><div><h2>Kelola pengguna dengan mudah</h2><p>Atur peran dan akses pengguna dari satu dashboard.</p></div><a class="button" href="{{ route('admin.users.index') }}">Kelola pengguna &rarr;</a></section>
@include('partials.whatsapp-dashboard-stats')
<div class="section-label"><h2>Ringkasan pengguna</h2><span>DATA PENGGUNA</span></div>
<div class="stats">
    <article class="card stat-card"><div class="stat-heading"><span>Total pengguna</span><span class="stat-icon" aria-hidden="true">◎</span></div><strong class="stat">{{ str_pad($totalUsers, 2, '0', STR_PAD_LEFT) }}</strong><small><span class="status-dot"></span> Seluruh akun terdaftar</small></article>
    <article class="card stat-card"><div class="stat-heading"><span>Administrator</span><span class="stat-icon" aria-hidden="true">✳</span></div><strong class="stat">{{ str_pad($totalAdmins, 2, '0', STR_PAD_LEFT) }}</strong><small>Pengelola tim & akses</small></article>
    <article class="card stat-card"><div class="stat-heading"><span>User</span><span class="stat-icon" aria-hidden="true">↗</span></div><strong class="stat">{{ str_pad($totalMembers, 2, '0', STR_PAD_LEFT) }}</strong><small>Akun dengan akses personal</small></article>
</div>
<section class="card table-card">
    <div class="section-heading"><div><span class="eyebrow">DAFTAR PENGGUNA</span><h2>Pengguna terbaru</h2><p class="muted">Lima akun yang terakhir bergabung.</p></div><a class="text-link" href="{{ route('admin.users.index') }}">Lihat semua <span aria-hidden="true">↗</span></a></div>
    <div class="table-scroll" tabindex="0" role="region" aria-label="Daftar pengguna terbaru"><table><thead><tr><th>Pengguna</th><th>Role</th><th>Bergabung</th></tr></thead><tbody>
        @forelse($recentUsers as $user)
            <tr><td><div class="person"><span class="avatar" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span><div><strong>{{ $user->name }}</strong><span class="cell-detail">{{ $user->email }}</span></div></div></td><td><span class="badge {{ $user->role }}">{{ ucfirst($user->role) }}</span></td><td>{{ $user->created_at->format('d M Y') }}</td></tr>
        @empty
            <tr><td colspan="3" class="empty">Belum ada pengguna.</td></tr>
        @endforelse
    </tbody></table></div>
</section>
@endsection
