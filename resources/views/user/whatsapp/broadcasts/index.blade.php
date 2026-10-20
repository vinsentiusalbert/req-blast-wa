@extends('user.whatsapp.layout')
@section('title', 'Broadcast WhatsApp')
@section('content')
<div class="page-heading"><div><span class="eyebrow">WHATSAPP BUSINESS</span><h1>Broadcast WhatsApp</h1><p class="muted">Siapkan broadcast menggunakan template yang sudah disetujui admin.</p></div>@if($hasApprovedTemplates)<a class="button" href="{{ route('user.whatsapp.broadcasts.create') }}">+ Buat Broadcast</a>@endif</div>
@if(!$hasApprovedTemplates)
<section class="account-banner"><div><h2>Belum ada template yang disetujui</h2><p>Kelola pengajuan pada menu Template WhatsApp. Broadcast dapat dibuat setelah admin menyetujui template Anda.</p></div><a class="button" href="{{ route('user.whatsapp.templates.index') }}">Lihat Template</a></section>
@endif
<p class="wa-info">Pantau status iklan Anda: Pending, Approve, Reject, dan Finish.</p>
<section class="card table-card">
    <div class="section-heading"><h2>Daftar broadcast <span class="badge">{{ $broadcasts->total() }}</span></h2></div>
    <div class="table-scroll" tabindex="0" role="region" aria-label="Daftar broadcast"><table><thead><tr><th>Nama broadcast</th><th>Template</th><th>Penerima</th><th>Status</th><th>Dibuat</th><th>Aksi</th></tr></thead><tbody>
        @forelse($broadcasts as $broadcast)
        <tr><td><a class="text-link" href="{{ route('user.whatsapp.broadcasts.show', $broadcast) }}">{{ $broadcast->name }}</a></td><td>{{ $broadcast->template->name }}@if(!$broadcast->template->isApproved())<span class="cell-detail">Template belum disetujui</span>@endif</td><td>{{ number_format($broadcast->recipient_count, 0, ',', '.') }} nomor</td><td><span class="badge campaign-status campaign-status--{{ $broadcast->status }}">{{ \App\Models\WhatsappBroadcast::STATUS_LABELS[$broadcast->status] ?? $broadcast->status }}</span>@if($broadcast->status === 'reject')<span class="cell-detail">{{ \Illuminate\Support\Str::limit($broadcast->rejection_reason, 100) }}</span>@endif</td><td>{{ $broadcast->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</td><td><a class="button secondary small" href="{{ route('user.whatsapp.broadcasts.show', $broadcast) }}">Lihat Detail</a> @can('update', $broadcast)<a class="button secondary small" href="{{ route('user.whatsapp.broadcasts.edit', $broadcast) }}">Edit Broadcast</a>@endcan</td></tr>
        @empty
        <tr><td colspan="6"><div class="wa-empty"><span aria-hidden="true">↗</span><h2>Belum ada broadcast</h2><p>{{ $hasApprovedTemplates ? 'Pilih template yang disetujui dan siapkan daftar penerima Anda.' : 'Template yang menunggu persetujuan atau ditolak belum dapat digunakan.' }}</p>@if($hasApprovedTemplates)<a class="button" href="{{ route('user.whatsapp.broadcasts.create') }}">Buat Broadcast</a>@endif</div></td></tr>
        @endforelse
    </tbody></table></div>
    @include('user.whatsapp.partials.pagination', ['paginator' => $broadcasts])
</section>
@endsection
