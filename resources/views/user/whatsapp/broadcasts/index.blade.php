@extends('user.whatsapp.layout')
@section('title', 'WhatsApp Broadcasts')
@section('content')
<div class="page-heading"><div><span class="eyebrow">WHATSAPP BUSINESS</span><h1>WhatsApp Broadcasts</h1><p class="muted">Prepare a broadcast using an approved template.</p></div>@if($hasApprovedTemplates)<a class="button" href="{{ route('user.whatsapp.broadcasts.create') }}">+ Create Broadcast</a>@endif</div>
@if(!$hasApprovedTemplates)
<section class="account-banner"><div><h2>No approved templates yet</h2><p>Manage submissions in WhatsApp Templates. You can create broadcasts after an administrator approves your template.</p></div><a class="button" href="{{ route('user.whatsapp.templates.index') }}">View Templates</a></section>
@endif
<p class="wa-info">Track your campaign status: Pending, Approved, Rejected, and Finished.</p>
<section class="card table-card">
    <div class="section-heading"><h2>Broadcast list <span class="badge">{{ $broadcasts->total() }}</span></h2></div>
    <div class="table-scroll" tabindex="0" role="region" aria-label="Broadcast list"><table><thead><tr><th>Broadcast name</th><th>Template</th><th>Recipients</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead><tbody>
        @forelse($broadcasts as $broadcast)
        <tr><td><a class="text-link" href="{{ route('user.whatsapp.broadcasts.show', $broadcast) }}">{{ $broadcast->name }}</a></td><td>{{ $broadcast->template->name }}@if(!$broadcast->template->isApproved())<span class="cell-detail">Template not approved</span>@endif</td><td>{{ number_format($broadcast->recipient_count, 0, '.', ',') }} numbers</td><td><span class="badge campaign-status campaign-status--{{ $broadcast->status }}">{{ \App\Models\WhatsappBroadcast::STATUS_LABELS[$broadcast->status] ?? $broadcast->status }}</span>@if($broadcast->status === 'reject')<span class="cell-detail">{{ \Illuminate\Support\Str::limit($broadcast->rejection_reason, 100) }}</span>@endif</td><td>{{ $broadcast->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</td><td><a class="button secondary small" href="{{ route('user.whatsapp.broadcasts.show', $broadcast) }}">View Details</a> @can('update', $broadcast)<a class="button secondary small" href="{{ route('user.whatsapp.broadcasts.edit', $broadcast) }}">Edit Broadcast</a>@endcan</td></tr>
        @empty
        <tr><td colspan="6"><div class="wa-empty"><span aria-hidden="true">↗</span><h2>No broadcasts yet</h2><p>{{ $hasApprovedTemplates ? 'Choose an approved template and prepare your recipient list.' : 'Templates awaiting approval or rejected cannot be used yet.' }}</p>@if($hasApprovedTemplates)<a class="button" href="{{ route('user.whatsapp.broadcasts.create') }}">Create Broadcast</a>@endif</div></td></tr>
        @endforelse
    </tbody></table></div>
    @include('user.whatsapp.partials.pagination', ['paginator' => $broadcasts])
</section>
@endsection
