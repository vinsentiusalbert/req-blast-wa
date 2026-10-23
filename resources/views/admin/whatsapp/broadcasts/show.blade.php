@extends('layouts.whatsapp')
@section('title', 'Broadcast Approval Details')
@section('content')
<a class="back-link" href="{{ route('admin.whatsapp.broadcasts.index') }}">Back to broadcasts</a>
<div class="page-heading"><div><h1>{{ $broadcast->name }}</h1><p class="muted">Submitted by {{ $broadcast->user->name }}</p></div></div>
<div class="template-builder-layout wa-builder">
    <div class="template-builder-main">
        @include('partials.broadcast-cost')
        <article class="template-builder-card">
            <h2>Campaign details</h2>
            <dl><div><dt>Status</dt><dd>{{ \App\Models\WhatsappBroadcast::STATUS_LABELS[$broadcast->status] ?? $broadcast->status }}</dd></div>
                <div><dt>Template</dt><dd>{{ $broadcast->template->name }}</dd></div>
                <div><dt>Recipients</dt><dd>{{ number_format($broadcast->recipient_count, 0, '.', ',') }}</dd></div>
                @if($broadcast->approved_at)<div><dt>Approved at</dt><dd>{{ \Illuminate\Support\Carbon::parse($broadcast->approved_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} UTC+7</dd></div>@endif
                @if($broadcast->finished_at)<div><dt>Finished at</dt><dd>{{ \Illuminate\Support\Carbon::parse($broadcast->finished_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} UTC+7</dd></div>@endif
            <div><dt>Broadcast schedule</dt><dd>{{ $broadcast->scheduled_at ? $broadcast->scheduled_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' UTC+7' : 'Not scheduled' }}</dd></div></dl>
            @if($broadcast->status === 'reject')
                <div class="notice error"><strong>Rejection reason</strong><p class="wa-review-comment">{{ $broadcast->rejection_reason }}</p></div>
            @endif
            @if($broadcast->status === 'pending' && !$broadcast->template->isApproved())<p class="notice error">The template is not approved. Broadcast approval becomes available after template approval.</p>@endif
            @if(in_array($broadcast->status, ['pending', 'approve']))
            <section class="wa-review-actions" aria-label="Administrator decision">
                <h3>Administrator decision</h3>
                <p class="wa-review-actions__hint">{{ $broadcast->status === 'pending' ? 'Review the message and recipients before making a decision.' : 'Mark the campaign Finished when processing is complete.' }}</p>
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
                    <label for="rejection-reason">Rejection reason <span class="wa-required">*</span></label>
                    <p id="rejection-help">The user will see this reason so they can revise the broadcast.</p>
                    <textarea id="rejection-reason" name="rejection_reason" rows="4" maxlength="2000" required aria-describedby="rejection-help rejection-error" @error('rejection_reason') aria-invalid="true" @enderror placeholder="Example: Match the message content to the product being promoted.">{{ old('rejection_reason') }}</textarea>
                    <div class="wa-rejection-panel__meta"><span id="rejection-error">@error('rejection_reason'){{ $message }}@enderror</span><span>Maximum 2,000 characters</span></div>
                    <div class="wa-review-actions__buttons wa-rejection-panel__buttons">
                        <button class="button secondary" type="button" data-reject-cancel hidden>Cancel</button>
                        <button class="button" type="submit">Confirm Rejection</button>
                    </div>
                </form>
                @endif
            </section>
            @endif
        </article>
        <article class="template-builder-card">
            <h2>Message details</h2>
            <p>Template content before variables are replaced with CSV values.</p>
            <dl>
                <div><dt>Header image</dt><dd>
                    @if($broadcast->template->header_type === 'IMAGE' && $broadcast->template->asset_path)
                        <a href="{{ route('admin.whatsapp.templates.asset', $broadcast->template) }}" target="_blank" rel="noopener"><img class="wa-review-header-image" src="{{ route('admin.whatsapp.templates.asset', $broadcast->template) }}" alt="Message header image"></a>
                    @else
                        No header image.
                    @endif
                </dd></div>
                <div><dt>Header text</dt><dd class="wa-review-comment">{{ $broadcast->template->header_type === 'TEXT' ? $broadcast->template->header_text : 'No header text.' }}</dd></div>
                <div><dt>Message</dt><dd class="wa-review-comment">{{ $broadcast->template->body }}</dd></div>
            </dl>
        </article>
        <article class="template-builder-card">
            <h2>Recipients and variables</h2>
            <p>The preview uses variable values from the first CSV record.</p>
            <div class="table-scroll" role="region" tabindex="0" aria-label="Recipients and CSV variables">
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
