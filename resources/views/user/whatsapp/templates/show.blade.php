@extends('user.whatsapp.layout')
@section('title', 'WhatsApp Template Details')
@section('content')
<a class="back-link" href="{{ route('user.whatsapp.templates.index') }}">← WhatsApp Templates</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">WHATSAPP TEMPLATES</span><h1>{{ $template->name }}</h1><p class="muted">Manage your template and track its approval status.</p></div><a class="button secondary" href="{{ route('user.whatsapp.templates.edit', $template) }}">Edit Template</a></div>
<div class="template-builder-layout wa-builder">
    <div class="template-builder-main">
        <article class="template-builder-card"><h2>Template details</h2><dl><div><dt>Template name</dt><dd>{{ $template->name }}</dd></div><div><dt>Language</dt><dd>{{ \App\Models\WhatsappTemplate::LANGUAGES[$template->language] }}</dd></div><div><dt>Header type</dt><dd>{{ \App\Models\WhatsappTemplate::HEADER_TYPES[$template->header_type] }}</dd></div><div><dt>Updated</dt><dd>{{ $template->updated_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} UTC+7</dd></div><div><dt>Status</dt><dd>@include('partials.whatsapp-approval')</dd></div><div><dt>Revision</dt><dd>{{ $template->revision }}</dd></div></dl></article>
        <article class="template-builder-card"><h2>Administrator approval</h2>
            @if($template->isApproved())
                <p>This template is approved and ready for campaign submissions.</p><a class="button" href="{{ route('user.whatsapp.broadcasts.create', ['template' => $template->id]) }}">Create Broadcast</a>
            @elseif($template->approval_status === \App\Models\WhatsappTemplate::REJECTED)
                <p>This template was rejected. Address the review notes, then resubmit it using Edit Template.</p>
            @else
                <p>This template is awaiting administrator review and cannot be used in broadcasts yet.</p>
            @endif
            @if($template->reviewed_at)<p class="field-hint">Reviewed by {{ $template->reviewer?->name ?? 'Admin' }} on {{ $template->reviewed_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} UTC+7.</p>@endif
            @if($template->review_note)<p class="wa-info wa-review-comment">{{ $template->review_note }}</p>@endif
            <p class="field-hint">Template changes require administrator approval again.</p>
        </article>
    </div>
    @include('user.whatsapp.partials.preview', ['template' => $template])
</div>
@endsection
