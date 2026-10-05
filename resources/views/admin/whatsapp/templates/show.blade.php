@extends('layouts.whatsapp')
@section('title', 'Tinjau Template WhatsApp')
@section('content')
<a class="back-link" href="{{ route('admin.whatsapp.templates.index') }}">← Persetujuan Template</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">TINJAU TEMPLATE · REVISI {{ $template->revision }}</span><h1>{{ $template->name }}</h1><p class="muted">Periksa konten dan gambar sebelum memberikan keputusan.</p></div>@include('partials.whatsapp-approval')</div>
<div class="template-builder-layout wa-builder">
    <div class="template-builder-main">
        @include('user.whatsapp.partials.template-details', ['template' => $template])
        <article class="template-builder-card"><h2>Informasi pengajuan</h2><dl><div><dt>Pemilik</dt><dd>{{ $template->user->name }} ({{ '@'.$template->user->username }})</dd></div><div><dt>Bahasa</dt><dd>{{ \App\Models\WhatsappTemplate::LANGUAGES[$template->language] }}</dd></div><div><dt>Tipe header</dt><dd>{{ \App\Models\WhatsappTemplate::HEADER_TYPES[$template->header_type] }}</dd></div><div><dt>Revisi</dt><dd>{{ $template->revision }}</dd></div></dl></article>
        <article class="template-builder-card">
            @if($template->approval_status === \App\Models\WhatsappTemplate::PENDING)
                <h2>Keputusan admin</h2><p>Persetujuan berlaku untuk revisi yang sedang ditampilkan.</p>
                <form method="POST" action="{{ route('admin.whatsapp.templates.review', $template) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="revision" value="{{ $template->revision }}">
                    <div class="template-builder-field"><label for="review-note">Catatan untuk user</label><textarea id="review-note" name="review_note" class="wa-recipients wa-review-note" maxlength="1000" rows="4" placeholder="Wajib diisi jika template ditolak.">{{ old('review_note') }}</textarea></div>
                    <div class="template-builder-inline-actions"><button class="button secondary" name="decision" value="rejected" type="submit">Tolak Template</button><button class="button" name="decision" value="approved" type="submit">Setujui Template</button></div>
                </form>
            @else
                <h2>Hasil peninjauan</h2><p>{{ $template->reviewer?->name ?? 'Admin' }} · {{ $template->reviewed_at?->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p>
                @include('partials.whatsapp-approval')
                @if($template->review_note)<p class="wa-info wa-review-comment">{{ $template->review_note }}</p>@endif
            @endif
        </article>
    </div>
    @include('user.whatsapp.partials.preview', ['template' => $template])
</div>
@endsection
