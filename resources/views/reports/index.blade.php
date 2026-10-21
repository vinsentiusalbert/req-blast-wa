@extends('layouts.whatsapp')
@section('title', 'Report WhatsApp')
@push('styles')<link rel="stylesheet" href="{{ asset('css/reports.css') }}?v={{ filemtime(public_path('css/reports.css')) }}">@endpush
@section('content')
<div class="report-page">
<div class="page-heading report-heading"><div><span class="eyebrow">WHATSAPP ANALYTICS</span><h1>Report WhatsApp</h1><p class="muted">{{ auth()->user()->isAdmin() ? 'Pantau hasil pengiriman seluruh user dalam satu tempat.' : 'Pantau hasil pengiriman dan performa broadcast Anda.' }}</p></div><span class="report-count">{{ number_format($total) }} broadcast</span></div>
@include('reports.summary', ['target' => $recipientTotal])
<section class="card table-card report-table">
<div class="report-table-head"><div><h2>Daftar broadcast</h2><p>Filter berdasarkan tanggal pembuatan broadcast dalam WIB (UTC+7).</p></div></div>
<form method="GET" class="report-filters" action="{{ route($prefix.'.index') }}">
<div><label for="from">Dari tanggal</label><input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
<div><label for="to">Sampai tanggal</label><input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div>
<div><label for="status">Status broadcast</label><select id="status" name="status"><option value="">Semua status</option>@foreach(\App\Models\WhatsappBroadcast::STATUS_LABELS as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
@if(auth()->user()->isAdmin())<div><label for="user_id">User</label><select id="user_id" name="user_id"><option value="">Semua user</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string)($filters['user_id'] ?? '') === (string)$user->id)>{{ $user->name }}</option>@endforeach</select></div>@endif
<button class="button secondary" type="submit">Filter</button><a class="text-link" href="{{ route($prefix.'.index') }}">Reset</a>
</form>
<div class="table-scroll"><table><thead><tr><th>Broadcast</th>@if(auth()->user()->isAdmin())<th>User</th>@endif<th>Tanggal</th><th>Status broadcast</th><th>Target</th><th>Terkirim</th>@if($counts->get('failed', 0) > 0)<th>Gagal</th>@endif<th>Aksi</th></tr></thead><tbody>
@forelse($broadcasts as $broadcast)<tr><td>{{ $broadcast->name }}<span class="cell-detail">{{ $broadcast->template?->name }}</span></td>@if(auth()->user()->isAdmin())<td>{{ $broadcast->user?->name }}</td>@endif<td>{{ $broadcast->created_at->timezone('Asia/Jakarta')->format('d/m/Y') }}</td><td><span class="badge campaign-status campaign-status--{{ $broadcast->status }}">{{ \App\Models\WhatsappBroadcast::STATUS_LABELS[$broadcast->status] ?? $broadcast->status }}</span></td><td>{{ number_format($broadcast->recipient_count) }}</td><td><span class="report-success">{{ number_format($broadcast->sent_count) }}</span></td>@if($counts->get('failed', 0) > 0)<td>@if($broadcast->failed_count > 0)<span class="report-status report-status--failed">{{ number_format($broadcast->failed_count) }}</span>@else &mdash; @endif</td>@endif<td><a class="button secondary small" href="{{ route($prefix.'.show', $broadcast) }}">Lihat report</a></td></tr>
@empty<tr><td colspan="8"><div class="report-empty"><h3>Belum ada broadcast</h3><p>Report akan muncul di sini. Coba ubah filter untuk melihat hasil lainnya.</p></div></td></tr>@endforelse
</tbody></table></div>
@include('user.whatsapp.partials.pagination', ['paginator' => $broadcasts])
</section>
<p class="report-footnote">Status Finish adalah status proses broadcast. Hasil terkirim mengikuti data pengiriman penerima.</p>
</div>
@endsection
