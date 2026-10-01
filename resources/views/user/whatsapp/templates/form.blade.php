@extends('user.whatsapp.layout')
@section('title', $template->exists ? 'Edit Template WhatsApp' : 'Buat Template WhatsApp')
@section('content')
<a class="back-link" href="{{ route('user.whatsapp.templates.index') }}">← Template WhatsApp</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">TEMPLATE WHATSAPP</span><h1>{{ $template->exists ? 'Edit Template WhatsApp' : 'Buat Template WhatsApp' }}</h1><p class="muted">Susun template pesan dan lihat preview secara langsung.</p></div></div>
@if($template->exists)<p class="wa-info">Perubahan akan diajukan ulang kepada admin. Template tidak dapat dipakai untuk broadcast sampai disetujui kembali.</p>@endif
<form class="template-builder-layout wa-builder" id="whatsapp-template-form" method="POST" enctype="multipart/form-data" action="{{ $template->exists ? route('user.whatsapp.templates.update', $template) : route('user.whatsapp.templates.store') }}">
    @csrf
    @if($template->exists) @method('PUT') @endif
    <div class="template-builder-main">
        <article class="template-builder-card">
            <h2>Nama dan bahasa template</h2><p>Berikan nama yang mudah dikenali untuk pesan Anda.</p>
            <div class="template-builder-field"><label for="template-name">Nama template <span class="wa-required">*</span></label><input id="template-name" name="name" value="{{ old('name', $template->name) }}" placeholder="Contoh: promo_akhir_pekan" pattern="[a-z][a-z0-9_]*" maxlength="100" aria-describedby="template-name-help" required><small id="template-name-help">Gunakan huruf kecil, angka, dan garis bawah. Awali dengan huruf.</small></div>
            <div class="template-builder-field"><label for="template-language">Bahasa <span class="wa-required">*</span></label><select id="template-language" name="language" required>@foreach(\App\Models\WhatsappTemplate::LANGUAGES as $value => $label)<option value="{{ $value }}" @selected(old('language', $template->language) === $value)>{{ $label }}</option>@endforeach</select></div>
        </article>
        <article class="template-builder-card">
            <h2>Konten pesan</h2><p>Lengkapi header dan isi pesan template.</p>
            <div class="template-builder-field"><label for="template-header-type">Tipe header</label><select id="template-header-type" name="header_type">@foreach(\App\Models\WhatsappTemplate::HEADER_TYPES as $value => $label)<option value="{{ $value }}" @selected(old('header_type', $template->header_type) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="template-builder-field" data-header-text-field @if(old('header_type', $template->header_type) !== 'TEXT') hidden @endif><label for="template-header-text">Teks header</label><input id="template-header-text" name="header_text" value="{{ old('header_text', $template->header_text) }}" maxlength="60" placeholder="Judul pesan Anda" @disabled(old('header_type', $template->header_type) !== 'TEXT')><small data-header-count>0/60 karakter</small></div>
            <div class="template-builder-field" data-header-image-field @if(old('header_type', $template->header_type) !== 'IMAGE') hidden @endif>
                <label for="template-asset">Gambar header</label>
                <div class="template-builder-upload wa-upload"><span class="wa-upload-icon" aria-hidden="true">↑</span><strong>Upload gambar template</strong><span>JPG, PNG, atau WebP. Ukuran di bawah 1 MB.</span><input type="file" id="template-asset" name="asset" accept="image/jpeg,image/png,image/webp" data-existing-image="{{ $template->asset_path ? route('user.whatsapp.templates.asset', $template) : '' }}" @disabled(old('header_type', $template->header_type) !== 'IMAGE')><small data-asset-label>{{ $template->asset_path ? 'Gambar tersimpan. Pilih file untuk menggantinya.' : 'Belum ada gambar dipilih.' }}</small></div>
                <p class="error-text" data-asset-error role="alert" hidden></p>
                @if($errors->any())<small>Jika sebelumnya memilih file, silakan pilih ulang gambar sebelum menyimpan.</small>@endif
            </div>
            <div class="template-builder-field"><label for="template-body">Isi pesan <span class="wa-required">*</span></label>
                <div class="template-builder-editor">
                    <div class="template-builder-editor__toolbar" role="toolbar" aria-label="Format pesan">
                        <button type="button" data-format="bold" aria-label="Tebal"><b>B</b></button><button type="button" data-format="italic" aria-label="Miring"><i>I</i></button><button type="button" data-format="strike" aria-label="Coret"><s>S</s></button><button type="button" data-format="code" aria-label="Monospace">&lt;/&gt;</button><button type="button" data-format="bullet" aria-label="Daftar poin">•</button><button type="button" data-format="number" aria-label="Daftar nomor">1.</button>
                    </div>
                    <textarea id="template-body" name="body" maxlength="{{ \App\Models\WhatsappTemplate::MAX_BODY_LENGTH }}" rows="7" placeholder="Tulis pesan yang ingin Anda sampaikan..." required>{{ old('body', $template->body) }}</textarea>
                    <span class="template-builder-editor__count" data-body-count>0/550 karakter</span>
                </div>
            </div>
            <div class="template-builder-inline-actions"><a class="button secondary" href="{{ route('user.whatsapp.templates.index') }}">Batal</a><button class="button" type="submit" data-save-template>{{ $template->exists ? 'Ajukan Ulang Template' : 'Ajukan Template' }} →</button></div>
        </article>
    </div>
    @include('user.whatsapp.partials.preview', ['template' => $template])
</form>
@endsection
@push('scripts')
<script src="{{ asset('js/whatsapp-preview.js') }}" defer></script>
<script src="{{ asset('js/whatsapp-template.js') }}" defer></script>
@endpush
