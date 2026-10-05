@extends('user.whatsapp.layout')
@section('title', 'Ringkasan Broadcast')
@section('content')
<a class="back-link" href="{{ route('user.whatsapp.broadcasts.index') }}">← Daftar Broadcast</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">BROADCAST WHATSAPP</span><h1>{{ $broadcast->name }}</h1><p class="muted">Periksa status, pesan, dan laporan campaign Anda.</p></div></div>
<p class="wa-info">Campaign yang sudah dibuat tidak dapat diedit. Anda dapat melihat detail, jadwal, dan laporan pengirimannya di sini.</p>
<div class="template-builder-layout wa-builder">
    <div class="template-builder-main">
        @include('user.whatsapp.partials.broadcast-details')
        @include('user.whatsapp.partials.delivery-report')
        @include('user.whatsapp.partials.broadcast-recipients')
        @if(!$broadcast->template->isApproved())<div class="notice error" role="alert"><strong>Campaign terblokir: template belum disetujui.</strong><p>Template telah diubah atau belum mendapat persetujuan admin. Tunggu persetujuan template kembali sebelum campaign dapat diproses.</p></div>@endif
        <article class="template-builder-card"><h2>Ringkasan broadcast</h2><dl><div><dt>Status</dt><dd><span class="badge wa-draft-badge">{{ \App\Models\WhatsappBroadcast::STATUS_LABELS[$broadcast->status] ?? $broadcast->status }}</span></dd></div><div><dt>Template pesan</dt><dd><a class="text-link" href="{{ route('user.whatsapp.templates.show', $broadcast->template) }}">{{ $broadcast->template->name }}</a></dd></div><div><dt>Jumlah penerima</dt><dd><strong class="wa-summary-count">{{ number_format($broadcast->recipient_count, 0, ',', '.') }}</strong> nomor unik</dd></div><div><dt>Dibuat</dt><dd>{{ $broadcast->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' }}</dd></div></dl><p class="wa-info">Status campaign dikelola oleh admin. Pengiriman otomatis tersedia setelah layanan WhatsApp dihubungkan.</p></article>
    </div>
    @include('user.whatsapp.partials.preview', ['template' => $broadcast->template])
</div>
@endsection
