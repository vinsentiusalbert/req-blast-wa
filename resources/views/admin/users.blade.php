@extends('layouts.app')
@section('title', 'Manage Users')
@section('content')
<div class="page-heading"><div><span class="eyebrow">USER MANAGEMENT</span><h1>Users<span class="accent-dot">.</span></h1><p class="muted">{{ $users->total() }} registered accounts. Add users and manage their roles and access.</p></div><a class="button" href="{{ route('admin.users.create') }}">+ Add user</a></div>
<section class="card table-card">
    <form class="search-form" method="GET" action="{{ route('admin.users.index') }}">
        <label class="sr-only" for="search">Search name, username, or email</label>
        <input id="search" name="search" value="{{ $search }}" placeholder="Search name, username, or email…" maxlength="255">
        <button class="button secondary" type="submit">Search</button>
        @if($search !== '')<a href="{{ route('admin.users.index') }}">Reset</a>@endif
    </form>
    <div class="table-scroll" tabindex="0" role="region" aria-label="Users and roles"><table><thead><tr><th>Users</th><th>Current role</th><th>Balance &amp; rate</th><th>Joined</th><th>Change role</th></tr></thead><tbody>
        @forelse($users as $user)
            <tr>
                <td><strong>{{ $user->name }}</strong>@if($user->is(auth()->user())) <small>(You)</small>@endif<span class="cell-detail">{{ '@'.$user->username }}</span><span class="cell-detail">{{ $user->email }}</span></td>
                <td><span class="badge {{ $user->role }}">{{ ucfirst($user->role) }}</span></td>
                <td><strong>IDR {{ number_format($user->balance, 0, '.', ',') }}</strong><span class="cell-detail">IDR {{ number_format($user->broadcast_rate, 0, '.', ',') }} / number</span><a class="text-link" href="{{ route('admin.users.wallet', $user) }}">Manage balance</a></td>
                <td>{{ $user->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</td>
                <td>
                    @if($user->is(auth()->user()))
                        <span class="muted">Your own account</span>
                    @else
                        <form class="role-form" method="POST" action="{{ route('admin.users.role', $user) }}">
                            @csrf @method('PATCH')
                            <label class="sr-only" for="role-{{ $user->id }}">Role for {{ $user->name }}</label>
                            <select id="role-{{ $user->id }}" name="role"><option value="user" @selected($user->role === 'user')>User</option><option value="admin" @selected($user->role === 'admin')>Admin</option></select>
                            <button class="button secondary small" type="submit">Save</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td class="empty" colspan="5">No users match your search.</td></tr>
        @endforelse
    </tbody></table></div>
    @if($users->hasPages())
        <nav class="pagination" aria-label="User pages">
            @if($users->onFirstPage())<span class="muted">← Previous</span>@else<a href="{{ $users->previousPageUrl() }}">← Previous</a>@endif
            <span>{{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
            @if($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}">Next →</a>@else<span class="muted">Next →</span>@endif
        </nav>
    @endif
</section>
<p class="muted note">Administrators can manage user roles. You cannot change your own role from this page.</p>
@endsection
