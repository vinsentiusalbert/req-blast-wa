@extends('layouts.whatsapp')
@section('title', 'Kelola Campaign')
@section('content')
<a class="back-link" href="{{ route('admin.whatsapp.campaigns.index') }}">← Daftar Campaign</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">WHATSAPP · ADMIN</span><h1>{{ $broadcast->name }}</h1><p class="muted">Campaign dari {{ $broadcast->user->name }} ({{ '@'.$broadcast->user->username }}).</p></div></div>
<div class="template-builder-layout wa-builder">
    <div class="template-builder-main">
        @include('user.whatsapp.partials.broadcast-details')
        <article class="template-builder-card">
            <h2>Status campaign</h2>
            <p>Template: {{ $broadcast->template->name }} · {{ number_format($broadcast->recipient_count, 0, ',', '.') }} penerima.</p>
            @if(!$broadcast->template->isApproved())<p class="notice error">Template belum disetujui. Campaign belum dapat diterima atau diproses.</p>@endif
            <form method="POST" action="{{ route('admin.whatsapp.campaigns.update', $broadcast) }}">
                @csrf @method('PATCH')
                <div class="template-builder-field"><label for="campaign-status">Status campaign</label><select id="campaign-status" name="status" required>@foreach(\App\Models\WhatsappBroadcast::STATUS_LABELS as $value => $label)<option value="{{ $value }}" @selected(old('status', $broadcast->status) === $value)>{{ $label }}</option>@endforeach</select></div>
                <p class="wa-info">Perubahan status dicatat untuk client. Pesan WhatsApp belum dikirim otomatis.</p>
                <button class="button" type="submit">Simpan Status</button>
            </form>
        </article>
        @include('admin.whatsapp.campaigns.schedules')
        @include('user.whatsapp.partials.delivery-report')
        @include('user.whatsapp.partials.broadcast-recipients')
    </div>
    @include('user.whatsapp.partials.preview', ['template' => $broadcast->template])
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/whatsapp-schedules.js') }}" defer></script>
@endpush
