@extends('user.whatsapp.layout')
@section('title', 'Template WhatsApp')
@section('content')
<div class="page-heading"><div><span class="eyebrow">BROADCAST WHATSAPP</span><h1>Template WhatsApp</h1><p class="muted">Buat dan kelola template pesan, lalu tunggu persetujuan admin.</p></div><a class="button" href="{{ route('user.whatsapp.templates.create') }}">+ Buat Template</a></div>
<section class="card table-card">
    <div class="section-heading"><h2>Daftar template <span class="badge">{{ $templates->total() }}</span></h2><span class="wa-local-status">Tersimpan di akun Anda</span></div>
    <form method="GET" action="{{ route('user.whatsapp.templates.index') }}" class="search-form wa-filters">
        <label class="sr-only" for="template-search">Cari nama template</label><input id="template-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama template..." maxlength="100">
        <label class="sr-only" for="language">Bahasa</label><select id="language" name="language"><option value="">Semua bahasa</option>@foreach(\App\Models\WhatsappTemplate::LANGUAGES as $value => $label)<option value="{{ $value }}" @selected(($filters['language'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
        <button class="button secondary" type="submit">Filter</button>
        @if(!empty($filters['search']) || !empty($filters['language']))<a href="{{ route('user.whatsapp.templates.index') }}">Reset</a>@endif
    </form>
    <div class="table-scroll" tabindex="0" role="region" aria-label="Daftar template WhatsApp"><table><thead><tr><th>Nama template</th><th>Bahasa</th><th>Status</th><th>Diperbarui</th><th>Aksi</th></tr></thead><tbody>
        @forelse($templates as $template)
        <tr><td><a class="text-link" href="{{ route('user.whatsapp.templates.show', $template) }}">{{ $template->name }}</a><span class="cell-detail">{{ \Illuminate\Support\Str::limit($template->body, 65) }}</span></td><td>{{ \App\Models\WhatsappTemplate::LANGUAGES[$template->language] }}</td><td>@include('partials.whatsapp-approval')</td><td>{{ $template->updated_at->timezone('Asia/Jakarta')->format('d M Y') }}</td><td><a class="button secondary small" href="{{ route('user.whatsapp.templates.show', $template) }}">Lihat Detail</a></td></tr>
        @empty
        <tr><td colspan="5"><div class="wa-empty"><span aria-hidden="true">☷</span><h2>{{ $templates->total() === 0 && empty($filters['search']) && empty($filters['language']) ? 'Buat template pertama Anda' : 'Template tidak ditemukan' }}</h2><p>Susun pesan, tambahkan gambar, lalu lihat preview WhatsApp.</p><a class="button" href="{{ route('user.whatsapp.templates.create') }}">+ Buat Template</a></div></td></tr>
        @endforelse
    </tbody></table></div>
    @include('user.whatsapp.partials.pagination', ['paginator' => $templates])
</section>
@endsection
