@extends('layouts.app')
@section('title', 'Kelola Pengguna')
@section('content')
<div class="page-heading"><div><span class="eyebrow">MANAJEMEN PENGGUNA</span><h1>Pengguna<span class="accent-dot">.</span></h1><p class="muted">Cari pengguna dan kelola peran serta akses akun mereka.</p></div><span class="badge">{{ $users->total() }} akun</span></div>
<section class="card table-card">
    <form class="search-form" method="GET" action="{{ route('admin.users.index') }}">
        <label class="sr-only" for="search">Cari nama, username, atau email</label>
        <input id="search" name="search" value="{{ $search }}" placeholder="Cari nama, username, atau email…" maxlength="255">
        <button class="button secondary" type="submit">Cari</button>
        @if($search !== '')<a href="{{ route('admin.users.index') }}">Reset</a>@endif
    </form>
    <div class="table-scroll" tabindex="0" role="region" aria-label="Daftar pengguna dan role"><table><thead><tr><th>Pengguna</th><th>Role saat ini</th><th>Bergabung</th><th>Ubah role</th></tr></thead><tbody>
        @forelse($users as $user)
            <tr>
                <td><strong>{{ $user->name }}</strong>@if($user->is(auth()->user())) <small>(Anda)</small>@endif<span class="cell-detail">{{ '@'.$user->username }}</span><span class="cell-detail">{{ $user->email }}</span></td>
                <td><span class="badge {{ $user->role }}">{{ ucfirst($user->role) }}</span></td>
                <td>{{ $user->created_at->format('d M Y') }}</td>
                <td>
                    @if($user->is(auth()->user()))
                        <span class="muted">Akun Anda sendiri</span>
                    @else
                        <form class="role-form" method="POST" action="{{ route('admin.users.role', $user) }}">
                            @csrf @method('PATCH')
                            <label class="sr-only" for="role-{{ $user->id }}">Role untuk {{ $user->name }}</label>
                            <select id="role-{{ $user->id }}" name="role"><option value="user" @selected($user->role === 'user')>User</option><option value="admin" @selected($user->role === 'admin')>Admin</option></select>
                            <button class="button secondary small" type="submit">Simpan</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td class="empty" colspan="4">Tidak ada pengguna yang sesuai dengan pencarian Anda.</td></tr>
        @endforelse
    </tbody></table></div>
    @if($users->hasPages())
        <nav class="pagination" aria-label="Halaman pengguna">
            @if($users->onFirstPage())<span class="muted">← Sebelumnya</span>@else<a href="{{ $users->previousPageUrl() }}">← Sebelumnya</a>@endif
            <span>{{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
            @if($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}">Berikutnya →</a>@else<span class="muted">Berikutnya →</span>@endif
        </nav>
    @endif
</section>
<p class="muted note">Admin dapat mengelola role pengguna. Role akun Anda sendiri tidak dapat diubah dari halaman ini.</p>
@endsection
