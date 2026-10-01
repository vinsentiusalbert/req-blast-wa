@extends('user.whatsapp.layout')
@section('title', 'Detail Template WhatsApp')
@section('content')
<a class="back-link" href="{{ route('user.whatsapp.templates.index') }}">← Template WhatsApp</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">TEMPLATE WHATSAPP</span><h1>{{ $template->name }}</h1><p class="muted">Kelola template dan pantau hasil persetujuan admin.</p></div><a class="button secondary" href="{{ route('user.whatsapp.templates.edit', $template) }}">Edit Template</a></div>
<div class="template-builder-layout wa-builder">
    <div class="template-builder-main">
        <article class="template-builder-card"><h2>Informasi template</h2><dl><div><dt>Nama template</dt><dd>{{ $template->name }}</dd></div><div><dt>Bahasa</dt><dd>{{ \App\Models\WhatsappTemplate::LANGUAGES[$template->language] }}</dd></div><div><dt>Tipe header</dt><dd>{{ \App\Models\WhatsappTemplate::HEADER_TYPES[$template->header_type] }}</dd></div><div><dt>Diperbarui</dt><dd>{{ $template->updated_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</dd></div><div><dt>Status</dt><dd>@include('partials.whatsapp-approval')</dd></div><div><dt>Revisi</dt><dd>{{ $template->revision }}</dd></div></dl></article>
        <article class="template-builder-card"><h2>Persetujuan admin</h2>
            @if($template->isApproved())
                <p>Template sudah disetujui dan tersedia pada pilihan template di menu Broadcast WhatsApp.</p>
            @elseif($template->approval_status === \App\Models\WhatsappTemplate::REJECTED)
                <p>Template ditolak. Perbaiki sesuai catatan admin, lalu ajukan ulang melalui Edit Template.</p>
            @else
                <p>Template sedang menunggu peninjauan admin. Template belum dapat dipakai untuk broadcast.</p>
            @endif
            @if($template->reviewed_at)<p class="field-hint">Ditinjau oleh {{ $template->reviewer?->name ?? 'Admin' }} pada {{ $template->reviewed_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB.</p>@endif
            @if($template->review_note)<p class="wa-info wa-review-comment">{{ $template->review_note }}</p>@endif
            <p class="field-hint">Setiap perubahan template perlu mendapat persetujuan admin kembali.</p>
        </article>
    </div>
    @include('user.whatsapp.partials.preview', ['template' => $template])
</div>
@endsection
