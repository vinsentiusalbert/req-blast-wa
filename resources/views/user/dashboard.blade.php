@extends('layouts.app')
@section('title', 'User Dashboard')
@section('content')
<div class="page-heading"><div><span class="eyebrow">USER DASHBOARD</span><h1>Hello, {{ auth()->user()->name }}<span class="accent-dot">.</span></h1><p class="muted">Track your WhatsApp templates and campaigns by status.</p></div><span class="badge user"><span class="status-dot"></span> Active account</span></div>

<section class="account-banner dashboard-welcome">
    <div><span class="banner-eyebrow">START A CONVERSATION</span><h2>Great messages start here.</h2><p>Prepare templates, request approval, and reach your audience with WhatsApp campaigns.</p></div>
    <a class="button" href="{{ route('user.whatsapp.templates.create') }}">Create template &rarr;</a>
</section>

<section class="account-banner"><div><span class="banner-eyebrow">AVAILABLE BALANCE</span><h2>IDR {{ number_format(auth()->user()->balance, 0, '.', ',') }}</h2><p>Your rate is IDR {{ number_format(auth()->user()->broadcast_rate, 0, '.', ',') }} per number. Your balance is charged when a broadcast is approved.</p></div><a class="button secondary" href="{{ route('user.wallet') }}">View transactions &rarr;</a></section>
<div class="stats">
    <section class="card stat-card"><h2 class="stat-heading">Overall total</h2><strong class="stat">{{ number_format($totalTemplates + $totalCampaigns, 0, '.', ',') }}</strong><small>Your templates and campaigns</small></section>
    <section class="card stat-card"><h2 class="stat-heading">Total templates</h2><strong class="stat">{{ number_format($totalTemplates, 0, '.', ',') }}</strong><small>All approval statuses</small></section>
    <section class="card stat-card"><h2 class="stat-heading">Total campaigns</h2><strong class="stat">{{ number_format($totalCampaigns, 0, '.', ',') }}</strong><small>All campaign statuses</small></section>
</div>

<div class="dashboard-status-grid">
    <section class="card">
        <div class="section-label"><h2>Template status</h2><a class="text-link" href="{{ route('user.whatsapp.templates.index') }}">View templates &rarr;</a></div>
        <p class="muted dashboard-description">An overview of template approvals.</p>
        <dl class="dashboard-status-list">
            @foreach(\App\Models\WhatsappTemplate::APPROVAL_LABELS as $status => $label)
            <div><dt><span class="dashboard-status-dot {{ $status }}" aria-hidden="true"></span>{{ $label }}</dt><dd>{{ number_format($templateCounts->get($status, 0), 0, '.', ',') }}</dd></div>
            @endforeach
            <div class="dashboard-status-total"><dt>Total templates</dt><dd>{{ number_format($totalTemplates, 0, '.', ',') }}</dd></div>
        </dl>
        @if($totalTemplates === 0)<p class="note">No templates yet. <a class="text-link" href="{{ route('user.whatsapp.templates.create') }}">Create your first template</a></p>@endif
    </section>
    <section class="card">
        <div class="section-label"><h2>Campaign status</h2><a class="text-link" href="{{ route('user.whatsapp.broadcasts.index') }}">View campaigns &rarr;</a></div>
        <p class="muted dashboard-description">Your saved WhatsApp broadcast campaigns.</p>
        <dl class="dashboard-status-list">
            @foreach(\App\Models\WhatsappBroadcast::STATUS_LABELS as $status => $label)
            <div><dt>{{ $label }}</dt><dd>{{ number_format($campaignCounts->get($status, 0), 0, '.', ',') }}</dd></div>
            @endforeach
            <div class="dashboard-status-total"><dt>Total campaigns</dt><dd>{{ number_format($totalCampaigns, 0, '.', ',') }}</dd></div>
        </dl>
        <p class="note">New broadcasts are Pending. An administrator approves them, then marks them Finished.</p>
        @if($totalCampaigns === 0)<p class="note">No campaigns yet. Use an approved template to create a campaign.</p>@endif
    </section>
</div>
@endsection
