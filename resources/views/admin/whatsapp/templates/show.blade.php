@extends('layouts.whatsapp')
@section('title', 'Review WhatsApp Template')
@section('content')
<a class="back-link" href="{{ route('admin.whatsapp.templates.index') }}">← Template Approvals</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">TEMPLATE REVIEW · REVISION {{ $template->revision }}</span><h1>{{ $template->name }}</h1><p class="muted">Check the message and images before making a decision.</p></div>@include('partials.whatsapp-approval')</div>
<div class="template-builder-layout wa-builder">
    <div class="template-builder-main">
        <article class="template-builder-card"><h2>Submission details</h2><dl><div><dt>Owner</dt><dd>{{ $template->user->name }} ({{ '@'.$template->user->username }})</dd></div><div><dt>Language</dt><dd>{{ \App\Models\WhatsappTemplate::LANGUAGES[$template->language] }}</dd></div><div><dt>Header type</dt><dd>{{ \App\Models\WhatsappTemplate::HEADER_TYPES[$template->header_type] }}</dd></div><div><dt>Revision</dt><dd>{{ $template->revision }}</dd></div></dl></article>
        <article class="template-builder-card">
            @if($template->approval_status === \App\Models\WhatsappTemplate::PENDING)
                <h2>Administrator decision</h2><p>Approval applies to the revision shown here.</p>
                <form method="POST" action="{{ route('admin.whatsapp.templates.review', $template) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="revision" value="{{ $template->revision }}">
                    <div class="template-builder-field"><label for="review-note">Notes for the user</label><textarea id="review-note" name="review_note" class="wa-recipients wa-review-note" maxlength="1000" rows="4" placeholder="Required when rejecting a template.">{{ old('review_note') }}</textarea></div>
                    <div class="template-builder-inline-actions"><button class="button secondary" name="decision" value="rejected" type="submit">Reject Template</button><button class="button" name="decision" value="approved" type="submit">Approve Template</button></div>
                </form>
            @else
                <h2>Review outcome</h2><p>{{ $template->reviewer?->name ?? 'Admin' }} · {{ $template->reviewed_at?->timezone('Asia/Jakarta')->format('d M Y, H:i') }} UTC+7</p>
                @include('partials.whatsapp-approval')
                @if($template->review_note)<p class="wa-info wa-review-comment">{{ $template->review_note }}</p>@endif
            @endif
        </article>
    </div>
    @include('user.whatsapp.partials.preview', ['template' => $template])
</div>
@endsection
