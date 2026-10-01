@extends('user.whatsapp.layout')
@section('title', 'Ringkasan Broadcast')
@section('content')
<a class="back-link" href="{{ route('user.whatsapp.broadcasts.index') }}">← Daftar Broadcast</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">BROADCAST WHATSAPP</span><h1>{{ $broadcast->name }}</h1><p class="muted">Periksa pesan dan daftar penerima pada draft Anda.</p></div><a class="button secondary" href="{{ route('user.whatsapp.broadcasts.edit', $broadcast) }}">Edit Draft</a></div>
<div class="template-builder-layout wa-builder">
    <div class="template-builder-main">
        @if(!$broadcast->template->isApproved())<div class="notice error" role="alert"><strong>Draft terblokir: template belum disetujui.</strong><p>Template telah diubah atau belum mendapat persetujuan admin. Tunggu persetujuan kembali atau pilih template lain melalui Edit Draft.</p></div>@endif
        <article class="template-builder-card"><h2>Ringkasan broadcast</h2><dl><div><dt>Status</dt><dd><span class="badge wa-draft-badge">Draft · Belum dikirim</span></dd></div><div><dt>Template pesan</dt><dd><a class="text-link" href="{{ route('user.whatsapp.templates.show', $broadcast->template) }}">{{ $broadcast->template->name }}</a></dd></div><div><dt>Jumlah penerima</dt><dd><strong class="wa-summary-count">{{ number_format($broadcast->recipient_count, 0, ',', '.') }}</strong> nomor unik</dd></div><div><dt>Dibuat</dt><dd>{{ $broadcast->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' }}</dd></div></dl><p class="wa-info">Draft tersimpan. Hubungkan layanan WhatsApp untuk mengaktifkan pengiriman. Template harus tetap berstatus disetujui sebelum draft dapat digunakan.</p></article>
        <article class="template-builder-card"><h2>Daftar penerima</h2><p>Nomor telah dinormalisasi dan duplikat telah disatukan.</p><ol class="wa-recipients-list">@foreach($broadcast->recipients as $recipient)<li>+{{ $recipient }}</li>@endforeach</ol></article>
    </div>
    @include('user.whatsapp.partials.preview', ['template' => $broadcast->template])
</div>
@endsection
