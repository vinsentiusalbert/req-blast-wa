@extends('layouts.whatsapp')
@section('title', 'Detail Approval Broadcast')
@section('content')
<a class="back-link" href="{{ route('admin.whatsapp.broadcasts.index') }}">Kembali ke daftar broadcast</a>
<div class="page-heading"><div><h1>{{ $broadcast->name }}</h1><p class="muted">Diajukan oleh {{ $broadcast->user->name }}</p></div></div>
<div class="template-builder-layout wa-builder">
    <div class="template-builder-main">
        <article class="template-builder-card">
            <h2>Informasi iklan</h2>
            <dl><div><dt>Status</dt><dd>{{ \App\Models\WhatsappBroadcast::STATUS_LABELS[$broadcast->status] ?? $broadcast->status }}</dd></div>
                <div><dt>Template</dt><dd>{{ $broadcast->template->name }}</dd></div>
                <div><dt>Penerima</dt><dd>{{ number_format($broadcast->recipient_count, 0, ',', '.') }}</dd></div>
                @if($broadcast->approved_at)<div><dt>Waktu Approve</dt><dd>{{ \Illuminate\Support\Carbon::parse($broadcast->approved_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</dd></div>@endif
                @if($broadcast->finished_at)<div><dt>Waktu Finish</dt><dd>{{ \Illuminate\Support\Carbon::parse($broadcast->finished_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</dd></div>@endif
            <div><dt>Jadwal broadcast</dt><dd>{{ $broadcast->scheduled_at ? $broadcast->scheduled_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : 'Belum ditentukan' }}</dd></div></dl>
            @if($broadcast->status === 'reject')
                <div class="notice error"><strong>Alasan penolakan</strong><p class="wa-review-comment">{{ $broadcast->rejection_reason }}</p></div>
            @endif
            @if($broadcast->status === 'pending' && !$broadcast->template->isApproved())<p class="notice error">Template belum disetujui. Approve broadcast tersedia setelah template disetujui.</p>@endif
            @if(in_array($broadcast->status, ['pending', 'approve']))
            <section class="wa-review-actions" aria-label="Keputusan admin">
                <h3>Keputusan admin</h3>
                <p class="wa-review-actions__hint">{{ $broadcast->status === 'pending' ? 'Periksa detail pesan dan penerima sebelum mengambil keputusan.' : 'Tandai Finish setelah iklan selesai diproses.' }}</p>
                <div class="wa-review-actions__buttons">
                    <form method="POST" action="{{ route('admin.whatsapp.broadcasts.review', $broadcast) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="revision" value="{{ $broadcast->revision }}">
                        <input type="hidden" name="status" value="{{ $broadcast->status === 'pending' ? 'approve' : 'finish' }}">
                        <button class="button" type="submit" @disabled($broadcast->status === 'pending' && !$broadcast->template->isApproved())>{{ $broadcast->status === 'pending' ? 'Approve' : 'Finish' }}</button>
                    </form>
                    @if($broadcast->status === 'pending')
                        <button class="button secondary wa-reject-toggle" type="button" data-reject-toggle aria-controls="broadcast-rejection-panel" aria-expanded="true" hidden>Reject</button>
                    @endif
                </div>
                @if($broadcast->status === 'pending')
                <form id="broadcast-rejection-panel" class="wa-rejection-panel" data-has-error="{{ $errors->has('rejection_reason') || old('status') === 'reject' ? 'true' : 'false' }}" method="POST" action="{{ route('admin.whatsapp.broadcasts.review', $broadcast) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="revision" value="{{ $broadcast->revision }}">
                    <input type="hidden" name="status" value="reject">
                    <label for="rejection-reason">Alasan penolakan <span class="wa-required">*</span></label>
                    <p id="rejection-help">Alasan ini akan terlihat oleh user agar broadcast dapat diperbaiki.</p>
                    <textarea id="rejection-reason" name="rejection_reason" rows="4" maxlength="2000" required aria-describedby="rejection-help rejection-error" @error('rejection_reason') aria-invalid="true" @enderror placeholder="Contoh: Sesuaikan isi pesan dengan produk yang dipromosikan.">{{ old('rejection_reason') }}</textarea>
                    <div class="wa-rejection-panel__meta"><span id="rejection-error">@error('rejection_reason'){{ $message }}@enderror</span><span>Maksimal 2.000 karakter</span></div>
                    <div class="wa-review-actions__buttons wa-rejection-panel__buttons">
                        <button class="button secondary" type="button" data-reject-cancel hidden>Batal</button>
                        <button class="button" type="submit">Konfirmasi Reject</button>
                    </div>
                </form>
                @endif
            </section>
            @endif
        </article>
        <article class="template-builder-card">
            <h2>Detail pesan</h2>
            <p>Isi template sebelum variabel diganti dengan data CSV.</p>
            <dl>
                <div><dt>Gambar header</dt><dd>
                    @if($broadcast->template->header_type === 'IMAGE' && $broadcast->template->asset_path)
                        <a href="{{ route('admin.whatsapp.templates.asset', $broadcast->template) }}" target="_blank" rel="noopener"><img class="wa-review-header-image" src="{{ route('admin.whatsapp.templates.asset', $broadcast->template) }}" alt="Gambar header pesan"></a>
                    @else
                        Tidak ada gambar header.
                    @endif
                </dd></div>
                <div><dt>Teks header</dt><dd class="wa-review-comment">{{ $broadcast->template->header_type === 'TEXT' ? $broadcast->template->header_text : 'Tidak ada teks header.' }}</dd></div>
                <div><dt>Pesan</dt><dd class="wa-review-comment">{{ $broadcast->template->body }}</dd></div>
            </dl>
        </article>
        <article class="template-builder-card">
            <h2>Daftar penerima dan variabel</h2>
            <p>Preview menggunakan nilai variabel dari record pertama CSV.</p>
            <div class="table-scroll" role="region" tabindex="0" aria-label="Penerima dan variabel CSV">
                <table>
                    <thead><tr><th>msisdn</th>@foreach($variableColumns as $column)<th>&#123;&#123;{{ $column }}&#125;&#125;</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach($records as $record)
                            <tr><td>{{ $record['msisdn'] }}</td>@foreach($variableColumns as $column)<td class="wa-review-comment">{{ $record[$column] ?? '' }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    </div>
    @include('user.whatsapp.partials.preview', ['template' => $previewTemplate])
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/whatsapp-broadcast-review.js') }}" defer></script>
@endpush
