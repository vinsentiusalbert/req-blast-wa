@extends('user.whatsapp.layout')
@section('title', $broadcast->exists ? 'Edit Draft Broadcast' : 'Buat Broadcast WhatsApp')
@section('content')
<a class="back-link" href="{{ route('user.whatsapp.broadcasts.index') }}">← Daftar Broadcast</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">BROADCAST WHATSAPP</span><h1>{{ $broadcast->exists ? 'Edit Draft Broadcast' : 'Buat Broadcast WhatsApp' }}</h1><p class="muted">Pilih template pesan, lalu unggah CSV penerima.</p></div></div>
@if($broadcast->exists && !$templates->contains('id', $broadcast->whatsapp_template_id))
    <p class="notice error" role="alert">Template pada draft ini belum disetujui atau sedang diajukan ulang. Pilih template lain yang disetujui, atau tunggu persetujuan admin.</p>
@endif
@if($templates->isEmpty())<p class="wa-info">Belum ada template yang disetujui. Draft belum dapat disimpan ulang.</p>@endif
<form class="template-builder-layout wa-builder" id="whatsapp-broadcast-form" method="POST" enctype="multipart/form-data" action="{{ $broadcast->exists ? route('user.whatsapp.broadcasts.update', $broadcast) : route('user.whatsapp.broadcasts.store') }}">
    @csrf
    @if($broadcast->exists) @method('PUT') @endif
    <div class="template-builder-main">
        <article class="template-builder-card">
            <h2>Informasi broadcast</h2>
            <div class="template-builder-field"><label for="broadcast-name">Nama broadcast <span class="wa-required">*</span></label><input id="broadcast-name" name="name" value="{{ old('name', $broadcast->name) }}" placeholder="Contoh: Promo akhir pekan Oktober" maxlength="150" required></div>
            <div class="template-builder-field"><label for="broadcast-template">Template pesan <span class="wa-required">*</span></label><select id="broadcast-template" name="whatsapp_template_id" required><option value="">Pilih template WhatsApp</option>@foreach($templates as $template)<option value="{{ $template->id }}" @selected((string)old('whatsapp_template_id', $broadcast->whatsapp_template_id) === (string)$template->id)>{{ $template->name }} · {{ \App\Models\WhatsappTemplate::LANGUAGES[$template->language] }}</option>@endforeach</select><small>Hanya template milik Anda yang sudah disetujui admin yang ditampilkan.</small></div>
        </article>
        <article class="template-builder-card">
            <h2>Penerima broadcast</h2><p>Unggah file CSV berisi nomor WhatsApp penerima.</p>
            <div class="template-builder-field"><label for="broadcast-recipient-file">File penerima CSV @if(!$broadcast->exists)<span class="wa-required">*</span>@endif</label><input type="file" id="broadcast-recipient-file" name="recipient_file" accept=".csv" aria-describedby="recipient-file-help" @required(!$broadcast->exists)><small id="recipient-file-help">Ukuran file maksimal 2 MB. Gunakan satu kolom nomor, atau header msisdn, nomor, phone, atau nomor_whatsapp untuk CSV dengan beberapa kolom. Nomor 08 diubah menjadi 628 dan duplikat disatukan. Setelah error, pilih kembali file Anda.</small></div>
            <p><a class="wa-download-link" href="{{ asset('downloads/contoh-penerima.csv') }}" download>Unduh contoh CSV</a></p>
            <p class="wa-info">@verbatim Gunakan {{var1}} atau {{var2}} pada pesan template. Nilainya diambil dari kolom CSV pada setiap penerima. Untuk nomor duplikat, data baris pertama digunakan. @endverbatim</p>
            @if($broadcast->exists)<p class="wa-info">{{ number_format($broadcast->recipient_count, 0, ',', '.') }} penerima tersimpan. Unggah CSV baru untuk mengganti seluruh penerima, atau biarkan kosong untuk mempertahankannya.</p>@endif
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
