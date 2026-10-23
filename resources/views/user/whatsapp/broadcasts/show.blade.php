@extends('user.whatsapp.layout')
@section('title', 'Broadcast Summary')
@section('content')
<a class="back-link" href="{{ route('user.whatsapp.broadcasts.index') }}">← Broadcast List</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">WHATSAPP BROADCAST</span><h1>{{ $broadcast->name }}</h1><p class="muted">Review the message and recipient list for your broadcast.</p></div>@can('update', $broadcast)<a class="button secondary" href="{{ route('user.whatsapp.broadcasts.edit', $broadcast) }}">Edit Broadcast</a>@endcan</div>
<div class="template-builder-layout wa-builder">
    <div class="template-builder-main">
        @include('partials.broadcast-cost')
        @include('partials.broadcast-rejection')
        @if(!$broadcast->template->isApproved())<div class="notice error" role="alert"><strong>Approval blocked: the template is not approved.</strong><p>The template has changed or is awaiting approval. Wait for approval or choose another template using Edit Broadcast.</p></div>@endif
        <article class="template-builder-card"><h2>Broadcast summary</h2><dl><div><dt>Status</dt><dd><span class="badge campaign-status campaign-status--{{ $broadcast->status }}">{{ \App\Models\WhatsappBroadcast::STATUS_LABELS[$broadcast->status] ?? $broadcast->status }}</span></dd></div><div><dt>Message template</dt><dd><a class="text-link" href="{{ route('user.whatsapp.templates.show', $broadcast->template) }}">{{ $broadcast->template->name }}</a></dd></div><div><dt>Recipient count</dt><dd><strong class="wa-summary-count">{{ number_format($broadcast->recipient_count, 0, '.', ',') }}</strong> unique numbers</dd></div><div><dt>Created</dt><dd>{{ $broadcast->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' UTC+7' }}</dd></div><div><dt>Broadcast schedule</dt><dd>{{ $broadcast->scheduled_at ? $broadcast->scheduled_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' UTC+7' : 'Not scheduled' }}</dd></div></dl><p class="wa-info">This broadcast is saved and managed through Pending, Approved, Rejected, and Finished statuses.</p></article>
        <article class="template-builder-card"><h2>Recipient list</h2><p>The message preview uses the first recipient record.</p><ol class="wa-recipients-list">@foreach($broadcast->recipients as $recipient)<li>+{{ $recipient }}</li>@endforeach</ol></article>
    </div>
    @include('user.whatsapp.partials.preview', ['template' => $previewTemplate])
</div>
@endsection
