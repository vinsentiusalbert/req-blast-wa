@extends('layouts.whatsapp')
@section('title', 'Campaign WhatsApp')
@section('content')
<div class="page-heading"><div><span class="eyebrow">WHATSAPP · ADMIN</span><h1>Campaign WhatsApp</h1><p class="muted">Lihat campaign masuk dan kelola statusnya.</p></div></div>
<section class="card table-card">
    <form method="GET" class="search-form wa-filters wa-review-filters" action="{{ route('admin.whatsapp.campaigns.index') }}">
        <label class="sr-only" for="campaign-search">Cari campaign</label><input id="campaign-search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Cari nama campaign...">
        <label class="sr-only" for="campaign-status">Status campaign</label><select id="campaign-status" name="status"><option value="">Semua status</option>@foreach(\App\Models\WhatsappBroadcast::STATUS_LABELS as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
        <label class="sr-only" for="campaign-approval">Persetujuan campaign</label><select id="campaign-approval" name="approval"><option value="">Semua persetujuan</option><option value="approved" @selected(($filters['approval'] ?? '') === 'approved')>Sudah approve</option><option value="not_approved" @selected(($filters['approval'] ?? '') === 'not_approved')>Belum approve / ditolak / dibatalkan</option></select>
        <label class="sr-only" for="campaign-airing">Status tayang</label><select id="campaign-airing" name="airing"><option value="">Semua status tayang</option><option value="live" @selected(($filters['airing'] ?? '') === 'live')>Sedang tayang</option><option value="not_live" @selected(($filters['airing'] ?? '') === 'not_live')>Belum tayang</option><option value="finished" @selected(($filters['airing'] ?? '') === 'finished')>Sudah selesai</option></select>
        <button class="button secondary" type="submit">Filter</button>
        <a href="{{ route('admin.whatsapp.campaigns.index') }}">Reset filter</a>
    </form>
    <div class="table-scroll" role="region" tabindex="0" aria-label="Daftar campaign"><table><thead><tr><th>Campaign</th><th>Client</th><th>Template</th><th>Penerima</th><th>Status</th><th>Status tayang</th><th>Aksi</th></tr></thead><tbody>
        @forelse($broadcasts as $broadcast)
        <tr><td>{{ $broadcast->name }}</td><td>{{ $broadcast->user->name }}<span class="cell-detail">{{ '@'.$broadcast->user->username }}</span></td><td>{{ $broadcast->template->name }}</td><td>{{ number_format($broadcast->recipient_count, 0, ',', '.') }}</td><td><span class="badge">{{ \App\Models\WhatsappBroadcast::STATUS_LABELS[$broadcast->status] ?? $broadcast->status }}</span></td><td>{{ match ($broadcast->status) { 'processing' => 'Sedang tayang', 'completed' => 'Sudah selesai', default => 'Belum tayang' } }}</td><td><a class="button secondary small" href="{{ route('admin.whatsapp.campaigns.show', $broadcast) }}">Kelola Campaign</a></td></tr>
        @empty
        <tr><td colspan="7" class="empty">Tidak ada campaign yang sesuai.</td></tr>
        @endforelse
    </tbody></table></div>
    @include('user.whatsapp.partials.pagination', ['paginator' => $broadcasts])
</section>
@endsection
