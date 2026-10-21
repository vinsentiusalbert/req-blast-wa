@extends('user.whatsapp.layout')
@section('title', $broadcast->exists ? 'Edit Broadcast' : 'Buat Broadcast WhatsApp')
@section('content')
<a class="back-link" href="{{ route('user.whatsapp.broadcasts.index') }}">← Daftar Broadcast</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">BROADCAST WHATSAPP</span><h1>{{ $broadcast->exists ? 'Edit Broadcast' : 'Buat Broadcast WhatsApp' }}</h1><p class="muted">Pilih template pesan, lalu unggah CSV penerima dan variabel pesan.</p></div></div>
@if($broadcast->exists && !$templates->contains('id', $broadcast->whatsapp_template_id))
    <p class="notice error" role="alert">Template pada broadcast ini belum disetujui atau sedang diajukan ulang. Pilih template lain yang disetujui, atau tunggu persetujuan admin.</p>
@endif
@if($templates->isEmpty())<p class="wa-info">Belum ada template yang disetujui. Broadcast belum dapat disimpan ulang.</p>@endif
<form class="template-builder-layout wa-builder" id="whatsapp-broadcast-form" enctype="multipart/form-data" data-preview-url="{{ route('user.whatsapp.broadcasts.preview-csv') }}" method="POST" action="{{ $broadcast->exists ? route('user.whatsapp.broadcasts.update', $broadcast) : route('user.whatsapp.broadcasts.store') }}">
    @csrf
    @if($broadcast->exists) @method('PUT') @endif
    <div class="template-builder-main">
        @include('partials.broadcast-rejection')
        <article class="template-builder-card">
            <h2>Informasi broadcast</h2>
            <div class="template-builder-field"><label for="broadcast-name">Nama broadcast <span class="wa-required">*</span></label><input id="broadcast-name" name="name" value="{{ old('name', $broadcast->name) }}" placeholder="Contoh: Promo akhir pekan Oktober" maxlength="150" required></div>
            <div class="template-builder-field"><label for="broadcast-template">Template pesan <span class="wa-required">*</span></label><select id="broadcast-template" name="whatsapp_template_id" required><option value="">Pilih template WhatsApp</option>@foreach($templates as $template)<option value="{{ $template->id }}" @selected((string)old('whatsapp_template_id', $broadcast->whatsapp_template_id) === (string)$template->id)>{{ $template->name }} · {{ \App\Models\WhatsappTemplate::LANGUAGES[$template->language] }}</option>@endforeach</select><small>Hanya template milik Anda yang sudah disetujui admin yang ditampilkan.</small></div>
        </article>
        <article class="template-builder-card">
            <h2>Jadwal broadcast</h2>
            <p>Pilih tanggal dan jam dalam WIB (UTC+7). Kosongkan keduanya jika belum menentukan jadwal.</p>
            <div class="wa-field-grid">
                <div class="template-builder-field"><label for="schedule-date">Tanggal broadcast</label><input id="schedule-date" type="date" name="schedule_date" min="{{ now('Asia/Jakarta')->format('Y-m-d') }}" value="{{ old('schedule_date', $broadcast->scheduled_at?->timezone('Asia/Jakarta')->format('Y-m-d')) }}"></div>
                <div class="template-builder-field"><label for="schedule-time">Jam broadcast (WIB)</label><input id="schedule-time" type="time" name="schedule_time" value="{{ old('schedule_time', $broadcast->scheduled_at?->timezone('Asia/Jakarta')->format('H:i')) }}" step="60"></div>
            </div>
            <small>Tanggal dan jam tidak boleh sebelum waktu sekarang. Jadwal diajukan bersama broadcast untuk diproses admin.</small>
        </article>
        <article class="template-builder-card">
            <h2>Upload CSV penerima</h2>
            <p>Kolom pertama wajib <code>msisdn</code>. Kolom berikutnya bebas sesuai nama variabel di template, misalnya <code>@{{nama}}</code> dan <code>@{{produk}}</code>.</p>
            <div class="template-builder-field">
                <label for="broadcast-csv">File CSV penerima</label>
                <input id="broadcast-csv" type="file" name="recipients_csv" accept=".csv,text/csv" @required(!$broadcast->exists) aria-describedby="recipients-help">
                <a class="wa-csv-download" href="{{ asset('downloads/contoh-broadcast.csv') }}" download="contoh-broadcast.csv">Download Contoh CSV</a>
                <small id="recipients-help">CSV UTF-8, pemisah koma, tanpa batas jumlah penerima. Nama variabel peka huruf besar/kecil. Nomor wajib diawali 62 dan hanya berisi angka, tanpa + atau awalan 08. Nomor duplikat ditolak.</small>
                @if($broadcast->exists)<small>Unggah file baru untuk mengganti penerima. Kosongkan untuk mempertahankan data tersimpan.</small>@endif
                <small data-recipient-count aria-live="polite"></small>
                <p data-csv-error class="notice error" role="alert" hidden></p>
            </div>
            <p>Preview menggunakan record pertama CSV. Jika validasi gagal setelah menyimpan, pilih ulang file CSV.</p>
            <div class="wa-info">Simpan broadcast untuk diajukan ke admin. Status awal Pending, kemudian Approve dan Finish setelah diproses admin.</div>
            <div class="template-builder-inline-actions"><a class="button secondary" href="{{ route('user.whatsapp.broadcasts.index') }}">Batal</a><button class="button" type="submit" data-save-broadcast data-unavailable="{{ $templates->isEmpty() ? 'true' : 'false' }}" @disabled($templates->isEmpty())>Simpan Broadcast →</button></div>
        </article>
    </div>
    @include('user.whatsapp.partials.preview', ['template' => null])
</form>
@endsection
@push('scripts')
<script src="{{ asset('js/broadcast-schedule.js') }}" defer></script>
<script id="broadcast-first-record" type="application/json">@json($broadcast->recipient_records[0] ?? null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script id="whatsapp-template-previews" type="application/json">@json($previews, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script src="{{ asset('js/whatsapp-preview.js') }}" defer></script>
<script src="{{ asset('js/whatsapp-broadcast.js') }}" defer></script>
@endpush
