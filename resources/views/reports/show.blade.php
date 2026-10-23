@extends('layouts.whatsapp')
@section('title', 'WhatsApp Report Details')
@push('styles')<link rel="stylesheet" href="{{ asset('css/reports.css') }}?v={{ filemtime(public_path('css/reports.css')) }}">@endpush
@section('content')
<div class="report-page">
<a class="report-back" href="{{ route($prefix.'.index') }}">&larr; All reports</a>
<div class="page-heading report-heading"><div><span class="eyebrow">DELIVERY REPORT</span><h1>{{ $broadcast->name }}</h1><p class="muted">{{ $broadcast->user?->name }} &middot; {{ $broadcast->template?->name }} &middot; {{ $broadcast->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</p></div><span class="badge campaign-status campaign-status--{{ $broadcast->status }}">{{ \App\Models\WhatsappBroadcast::STATUS_LABELS[$broadcast->status] ?? $broadcast->status }}</span></div>
@include('reports.summary', ['target' => $broadcast->recipient_count])
<section class="card table-card report-table">
<div class="report-table-head"><div><h2>Recipient details</h2><p>{{ number_format($recipients->total()) }} recipients matching your filters &middot; Time in UTC+7</p></div><div class="report-actions"><a class="button report-excel" href="{{ route($prefix.'.export', ['broadcast' => $broadcast, 'format' => 'xlsx'] + $filters) }}">Download Excel <span aria-hidden="true">&darr;</span></a><a class="button secondary" href="{{ route($prefix.'.export', ['broadcast' => $broadcast] + $filters) }}">CSV</a></div></div>
<form method="GET" class="report-filters" action="{{ route($prefix.'.show', $broadcast) }}">
<div><label for="msisdn">WhatsApp number</label><input name="msisdn" id="msisdn" placeholder="Search recipient numbers..." inputmode="numeric" value="{{ $filters['msisdn'] ?? '' }}"></div>
<div><label for="status">Delivery status</label><select name="status" id="status"><option value="">All statuses</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="report-actions"><button class="button secondary" type="submit">Apply filters</button><a class="text-link" href="{{ route($prefix.'.show', $broadcast) }}">Reset</a></div>
</form>
<div class="table-scroll"><table><thead><tr><th>WhatsApp number</th><th>Status</th><th>Attempts</th><th>Delivery results</th></tr></thead><tbody>
@forelse($recipients as $recipient)<tr><td class="report-number">{{ $recipient->msisdn }}</td><td><span class="report-status report-status--{{ $recipient->status }}">{{ $statuses[$recipient->status] ?? $recipient->status }}</span></td><td>{{ $recipient->attempts }} times</td><td class="report-result">
@if($recipient->status === 'sent')<strong class="report-success">Message sent</strong><span class="cell-detail">{{ $recipient->sent_at?->timezone('Asia/Jakarta')->format('d M Y, H:i:s') ?? 'Time not available yet' }}</span>
@elseif($recipient->status === 'failed')<strong class="report-failure">{{ $recipient->failure_reason ?: 'Delivery failed' }}</strong><span class="cell-detail">{{ $recipient->failed_at?->timezone('Asia/Jakarta')->format('d M Y, H:i:s') ?? 'Time not available yet' }}</span>
@else<span class="muted">Awaiting delivery results</span>@if($recipient->last_attempt_at)<span class="cell-detail">Last attempted {{ $recipient->last_attempt_at->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}</span>@endif
@endif</td></tr>
@empty<tr><td colspan="4"><div class="report-empty"><span aria-hidden="true">&#9744;</span><h3>No recipients yet</h3><p>Try changing the filters. Recipients appear after the broadcast is approved.</p></div></td></tr>@endforelse
</tbody></table></div>
@include('user.whatsapp.partials.pagination', ['paginator' => $recipients])
</section><p class="report-footnote">Downloads use the active filters. The summary includes all broadcast recipients.</p>
</div>
@endsection
