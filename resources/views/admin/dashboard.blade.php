@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">ADMIN DASHBOARD</span><h1>Hello, {{ auth()->user()->name }}<span class="accent-dot">.</span></h1><p class="muted">Welcome back. Here is an overview of your accounts and users.</p></div>
    <span class="date-label">{{ now()->timezone('Asia/Jakarta')->format('d / m / Y') }}</span>
</div>
<div class="stats request-queue">
    <a class="card stat-card request-queue__link" href="{{ route('admin.whatsapp.templates.index', ['status' => 'pending']) }}"><h2 class="stat-heading">Templates awaiting review</h2><strong class="stat">{{ $pendingTemplates }}</strong><small>Review templates &rarr;</small></a>
    <a class="card stat-card request-queue__link" href="{{ route('admin.whatsapp.broadcasts.index', ['status' => 'pending']) }}"><h2 class="stat-heading">Campaigns awaiting review</h2><strong class="stat">{{ $pendingBroadcasts }}</strong><small>Review campaigns &rarr;</small></a>
    <a class="card stat-card request-queue__link" href="{{ route('admin.whatsapp.broadcasts.index', ['status' => 'approve']) }}"><h2 class="stat-heading">Approved campaigns</h2><strong class="stat">{{ $approvedBroadcasts }}</strong><small>Track completion &rarr;</small></a>
</div>
<section class="account-banner"><div><h2>Manage your users with ease</h2><p>Manage user roles and access from a single dashboard.</p></div><a class="button" href="{{ route('admin.users.index') }}">Manage users &rarr;</a></section>
<div class="section-label"><h2>User overview</h2><span>USER DATA</span></div>
<div class="stats">
    <article class="card stat-card"><div class="stat-heading"><span>Total users</span><span class="stat-icon" aria-hidden="true">◎</span></div><strong class="stat">{{ str_pad($totalUsers, 2, '0', STR_PAD_LEFT) }}</strong><small><span class="status-dot"></span> All registered accounts</small></article>
    <article class="card stat-card"><div class="stat-heading"><span>Administrator</span><span class="stat-icon" aria-hidden="true">✳</span></div><strong class="stat">{{ str_pad($totalAdmins, 2, '0', STR_PAD_LEFT) }}</strong><small>Team and access management</small></article>
    <article class="card stat-card"><div class="stat-heading"><span>User</span><span class="stat-icon" aria-hidden="true">↗</span></div><strong class="stat">{{ str_pad($totalMembers, 2, '0', STR_PAD_LEFT) }}</strong><small>Individual user accounts</small></article>
</div>
<section class="card table-card">
    <div class="section-heading"><div><span class="eyebrow">USER DIRECTORY</span><h2>Recent users</h2><p class="muted">The five most recently registered accounts.</p></div><a class="text-link" href="{{ route('admin.users.index') }}">View all <span aria-hidden="true">↗</span></a></div>
    <div class="table-scroll" tabindex="0" role="region" aria-label="Recent users"><table><thead><tr><th>Users</th><th>Role</th><th>Joined</th></tr></thead><tbody>
        @forelse($recentUsers as $user)
            <tr><td><div class="person"><span class="avatar" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span><div><strong>{{ $user->name }}</strong><span class="cell-detail">{{ $user->email }}</span></div></div></td><td><span class="badge {{ $user->role }}">{{ ucfirst($user->role) }}</span></td><td>{{ $user->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</td></tr>
        @empty
            <tr><td colspan="3" class="empty">No users yet.</td></tr>
        @endforelse
    </tbody></table></div>
</section>
@endsection