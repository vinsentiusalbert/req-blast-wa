@extends('layouts.app')
@section('title', 'Dashboard User')
@section('content')
<div class="page-heading"><div><span class="eyebrow">DASHBOARD USER</span><h1>Halo, {{ auth()->user()->name }}<span class="accent-dot">.</span></h1><p class="muted">Pantau jumlah template dan campaign WhatsApp Anda berdasarkan statusnya.</p></div><span class="badge user"><span class="status-dot"></span> Akun aktif</span></div>

<section class="account-banner dashboard-welcome">
    <div><span class="banner-eyebrow">MULAI PERCAKAPAN</span><h2>Pesan yang baik dimulai di sini.</h2><p>Siapkan template, ajukan persetujuan, dan jangkau audiens Anda lewat campaign WhatsApp.</p></div>
    <a class="button" href="{{ route('user.whatsapp.templates.create') }}">Buat template &rarr;</a>
</section>

<div class="stats">
    <section class="card stat-card"><h2 class="stat-heading">Total keseluruhan</h2><strong class="stat">{{ number_format($totalTemplates + $totalCampaigns, 0, ',', '.') }}</strong><small>Template dan campaign Anda</small></section>
    <section class="card stat-card"><h2 class="stat-heading">Total template</h2><strong class="stat">{{ number_format($totalTemplates, 0, ',', '.') }}</strong><small>Semua status persetujuan</small></section>
    <section class="card stat-card"><h2 class="stat-heading">Total campaign</h2><strong class="stat">{{ number_format($totalCampaigns, 0, ',', '.') }}</strong><small>Semua status campaign</small></section>
</div>

<div class="dashboard-status-grid">
    <section class="card">
        <div class="section-label"><h2>Status template</h2><a class="text-link" href="{{ route('user.whatsapp.templates.index') }}">Lihat template &rarr;</a></div>
        <p class="muted dashboard-description">Ringkasan persetujuan template oleh admin.</p>
        <dl class="dashboard-status-list">
            @foreach(\App\Models\WhatsappTemplate::APPROVAL_LABELS as $status => $label)
            <div><dt><span class="dashboard-status-dot {{ $status }}" aria-hidden="true"></span>{{ $label }}</dt><dd>{{ number_format($templateCounts->get($status, 0), 0, ',', '.') }}</dd></div>
            @endforeach
            <div class="dashboard-status-total"><dt>Total template</dt><dd>{{ number_format($totalTemplates, 0, ',', '.') }}</dd></div>
        </dl>
        @if($totalTemplates === 0)<p class="note">Belum ada template. <a class="text-link" href="{{ route('user.whatsapp.templates.create') }}">Buat template pertama</a></p>@endif
    </section>
    <section class="card">
        <div class="section-label"><h2>Status campaign</h2><a class="text-link" href="{{ route('user.whatsapp.broadcasts.index') }}">Lihat campaign &rarr;</a></div>
        <p class="muted dashboard-description">Campaign WhatsApp yang tersimpan sebagai broadcast.</p>
        <dl class="dashboard-status-list">
            @foreach(\App\Models\WhatsappBroadcast::STATUS_LABELS as $status => $label)
            <div><dt>{{ $label }}</dt><dd>{{ number_format($campaignCounts->get($status, 0), 0, ',', '.') }}</dd></div>
            @endforeach
            <div class="dashboard-status-total"><dt>Total campaign</dt><dd>{{ number_format($totalCampaigns, 0, ',', '.') }}</dd></div>
        </dl>
        <p class="note">Broadcast baru berstatus Pending. Admin mengubah status menjadi Approve, lalu Finish.</p>
        @if($totalCampaigns === 0)<p class="note">Belum ada campaign. Gunakan template yang disetujui untuk membuat campaign.</p>@endif
    </section>
</div>
@endsection
