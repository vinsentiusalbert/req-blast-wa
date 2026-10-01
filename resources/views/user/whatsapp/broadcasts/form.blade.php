@extends('user.whatsapp.layout')
@section('title', $broadcast->exists ? 'Edit Draft Broadcast' : 'Buat Broadcast WhatsApp')
@section('content')
<a class="back-link" href="{{ route('user.whatsapp.broadcasts.index') }}">← Daftar Broadcast</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">BROADCAST WHATSAPP</span><h1>{{ $broadcast->exists ? 'Edit Draft Broadcast' : 'Buat Broadcast WhatsApp' }}</h1><p class="muted">Pilih template pesan, lalu masukkan daftar penerima.</p></div></div>
@if($broadcast->exists && !$templates->contains('id', $broadcast->whatsapp_template_id))
    <p class="notice error" role="alert">Template pada draft ini belum disetujui atau sedang diajukan ulang. Pilih template lain yang disetujui, atau tunggu persetujuan admin.</p>
@endif
@if($templates->isEmpty())<p class="wa-info">Belum ada template yang disetujui. Draft belum dapat disimpan ulang.</p>@endif
<form class="template-builder-layout wa-builder" id="whatsapp-broadcast-form" method="POST" action="{{ $broadcast->exists ? route('user.whatsapp.broadcasts.update', $broadcast) : route('user.whatsapp.broadcasts.store') }}">
    @csrf
    @if($broadcast->exists) @method('PUT') @endif
    <div class="template-builder-main">
        <article class="template-builder-card">
            <h2>Informasi broadcast</h2>
            <div class="template-builder-field"><label for="broadcast-name">Nama broadcast <span class="wa-required">*</span></label><input id="broadcast-name" name="name" value="{{ old('name', $broadcast->name) }}" placeholder="Contoh: Promo akhir pekan Oktober" maxlength="150" required></div>
            <div class="template-builder-field"><label for="broadcast-template">Template pesan <span class="wa-required">*</span></label><select id="broadcast-template" name="whatsapp_template_id" required><option value="">Pilih template WhatsApp</option>@foreach($templates as $template)<option value="{{ $template->id }}" @selected((string)old('whatsapp_template_id', $broadcast->whatsapp_template_id) === (string)$template->id)>{{ $template->name }} · {{ \App\Models\WhatsappTemplate::LANGUAGES[$template->language] }}</option>@endforeach</select><small>Hanya template milik Anda yang sudah disetujui admin yang ditampilkan.</small></div>
        </article>
        <article class="template-builder-card">
            <h2>Penerima broadcast</h2><p>Masukkan satu nomor per baris. Anda juga dapat memisahkannya dengan koma atau titik koma.</p>
            <div class="template-builder-field"><label for="broadcast-recipients">Nomor WhatsApp <span class="wa-required">*</span></label><textarea class="wa-recipients" id="broadcast-recipients" name="recipients" rows="8" maxlength="40000" placeholder="081234567890&#10;+6281234567891&#10;6281234567892" aria-describedby="recipients-help" required>{{ old('recipients', implode("\n", $broadcast->recipients ?? [])) }}</textarea><small id="recipients-help">Maksimal 1.000 nomor unik. Nomor 08 akan diubah menjadi 628; nomor duplikat disatukan saat disimpan.</small><small data-recipient-count aria-live="polite"></small></div>
            <div class="wa-info">Simpan dan periksa draft terlebih dahulu. Pesan belum dikirim karena layanan WhatsApp belum dihubungkan.</div>
            <div class="template-builder-inline-actions"><a class="button secondary" href="{{ route('user.whatsapp.broadcasts.index') }}">Batal</a><button class="button" type="submit" data-save-broadcast data-unavailable="{{ $templates->isEmpty() ? 'true' : 'false' }}" @disabled($templates->isEmpty())>Simpan Draft →</button></div>
        </article>
    </div>
    @include('user.whatsapp.partials.preview', ['template' => null])
</form>
@endsection
@push('scripts')
<script id="whatsapp-template-previews" type="application/json">@json($previews, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script src="{{ asset('js/whatsapp-preview.js') }}" defer></script>
<script src="{{ asset('js/whatsapp-broadcast.js') }}" defer></script>
@endpush
