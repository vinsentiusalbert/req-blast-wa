@extends('user.whatsapp.layout')
@section('title', 'Buat Broadcast WhatsApp')
@section('content')
<a class="back-link" href="{{ route('user.whatsapp.broadcasts.index') }}">← Daftar Broadcast</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">BROADCAST WHATSAPP</span><h1>Buat Broadcast WhatsApp</h1><p class="muted">Pilih template pesan, lalu unggah CSV penerima.</p></div></div>
@if($templates->isEmpty())<p class="wa-info">Belum ada template yang disetujui. Campaign belum dapat dibuat.</p>@endif
<form class="template-builder-layout wa-builder" id="whatsapp-broadcast-form" method="POST" enctype="multipart/form-data" action="{{ route('user.whatsapp.broadcasts.store') }}">
    @csrf
    <div class="template-builder-main">
        <article class="template-builder-card">
            <h2>Informasi broadcast</h2>
            <div class="template-builder-field"><label for="broadcast-name">Nama broadcast <span class="wa-required">*</span></label><input id="broadcast-name" name="name" value="{{ old('name', $broadcast->name) }}" placeholder="Contoh: Promo akhir pekan Oktober" maxlength="150" required></div>
            <div class="template-builder-field"><label for="broadcast-template">Template pesan <span class="wa-required">*</span></label><select id="broadcast-template" name="whatsapp_template_id" required><option value="">Pilih template WhatsApp</option>@foreach($templates as $template)<option value="{{ $template->id }}" @selected((string)old('whatsapp_template_id', $broadcast->whatsapp_template_id) === (string)$template->id)>{{ $template->name }} · {{ \App\Models\WhatsappTemplate::LANGUAGES[$template->language] }}</option>@endforeach</select><small>Hanya template milik Anda yang sudah disetujui admin yang ditampilkan.</small></div>
        </article>
        <article class="template-builder-card">
            <h2>Penerima broadcast</h2><p>Unggah file CSV berisi nomor WhatsApp penerima.</p>
            <div class="template-builder-field"><label for="broadcast-recipient-file">File penerima CSV <span class="wa-required">*</span></label><input type="file" id="broadcast-recipient-file" name="recipient_file" accept=".csv" aria-describedby="recipient-file-help" required><small id="recipient-file-help">Ukuran file maksimal 2 MB. Gunakan satu kolom nomor, atau header msisdn, nomor, phone, atau nomor_whatsapp untuk CSV dengan beberapa kolom. Nomor 08 diubah menjadi 628 dan duplikat disatukan. Setelah error, pilih kembali file Anda.</small></div>
            <p><a class="wa-download-link" href="{{ asset('downloads/contoh-penerima.csv') }}" download>Unduh contoh CSV</a></p>
            {{-- <p class="wa-info">@verbatim Gunakan {{var1}} atau {{var2}} pada pesan template. Nilainya diambil dari kolom CSV pada setiap penerima. Untuk nomor duplikat, data baris pertama digunakan. @endverbatim</p> --}}
        </article>
        <article class="template-builder-card">
            <div class="wa-message-browser__heading">
                <div><h2>Jadwal pengiriman</h2><p>Tentukan kapan pengiriman dimulai dan batas akhirnya. Semua waktu menggunakan UTC+7.</p></div>
                <span class="wa-message-browser__total">UTC+7 / WIB</span>
            </div>
            <div class="wa-field-grid">
                <div class="template-builder-field">
                    <label for="broadcast-send-date">Tanggal mulai kirim</label>
                    <input id="broadcast-send-date" type="date" name="send_date" value="{{ old('send_date', $broadcast->sending_starts_at?->timezone(\App\Models\WhatsappBroadcast::SENDING_TIMEZONE)->format('Y-m-d')) }}" aria-describedby="broadcast-schedule-help" data-schedule-input>
                    @error('send_date')<small class="wa-field-error" role="alert">{{ $message }}</small>@enderror
                </div>
                <div class="template-builder-field">
                    <label for="broadcast-send-time">Jam mulai kirim</label>
                    <input id="broadcast-send-time" type="time" name="send_time" value="{{ old('send_time', $broadcast->sending_starts_at?->timezone(\App\Models\WhatsappBroadcast::SENDING_TIMEZONE)->format('H:i')) }}" data-schedule-input>
                    @error('send_time')<small class="wa-field-error" role="alert">{{ $message }}</small>@enderror
                </div>
                <div class="template-builder-field">
                    <label for="broadcast-end-date">Tanggal batas akhir</label>
                    <input id="broadcast-end-date" type="date" name="end_date" value="{{ old('end_date', $broadcast->sending_ends_at?->timezone(\App\Models\WhatsappBroadcast::SENDING_TIMEZONE)->format('Y-m-d')) }}" data-schedule-input>
                    @error('end_date')<small class="wa-field-error" role="alert">{{ $message }}</small>@enderror
                </div>
                <div class="template-builder-field">
                    <label for="broadcast-end-time">Jam batas akhir</label>
                    <input id="broadcast-end-time" type="time" name="end_time" value="{{ old('end_time', $broadcast->sending_ends_at?->timezone(\App\Models\WhatsappBroadcast::SENDING_TIMEZONE)->format('H:i')) }}" data-schedule-input>
                    @error('end_time')<small class="wa-field-error" role="alert">{{ $message }}</small>@enderror
                </div>
            </div>
            <p id="broadcast-schedule-help">Batas akhir berlaku untuk seluruh pengiriman broadcast. Jadwal bersifat opsional; jika diisi, lengkapi keempat kolom sebelum membuat campaign.</p>
            <div class="wa-info" id="broadcast-schedule-summary" role="status" aria-live="polite">Jadwal belum ditentukan.</div>
            <button class="button secondary small" type="button" id="clear-broadcast-schedule">Hapus jadwal</button>
            <div class="wa-info">Periksa template, penerima, dan jadwal sebelum membuat campaign. Setelah dibuat, campaign tidak dapat diedit. Pengiriman mengikuti persetujuan admin dan integrasi layanan WhatsApp.</div>
            <div class="template-builder-inline-actions"><a class="button secondary" href="{{ route('user.whatsapp.broadcasts.index') }}">Batal</a><button class="button" type="submit" data-save-broadcast data-unavailable="{{ $templates->isEmpty() ? 'true' : 'false' }}" @disabled($templates->isEmpty())>Buat Campaign →</button></div>
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
