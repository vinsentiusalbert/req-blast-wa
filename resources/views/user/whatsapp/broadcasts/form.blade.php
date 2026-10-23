@extends('user.whatsapp.layout')
@section('title', $broadcast->exists ? 'Edit Broadcast' : 'Create WhatsApp Broadcast')
@section('content')
<a class="back-link" href="{{ route('user.whatsapp.broadcasts.index') }}">← Broadcast List</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">WHATSAPP BROADCAST</span><h1>{{ $broadcast->exists ? 'Edit Broadcast' : 'Create WhatsApp Broadcast' }}</h1><p class="muted">Choose a message template, then upload a CSV with recipients and message variables.</p></div></div>
@if($broadcast->exists && !$templates->contains('id', $broadcast->whatsapp_template_id))
    <p class="notice error" role="alert">This broadcast template is not approved or has been resubmitted. Choose another approved template or wait for administrator approval.</p>
@endif
@if($templates->isEmpty())<p class="wa-info">No approved templates are available. This broadcast cannot be resubmitted yet.</p>@endif
<form class="template-builder-layout wa-builder" id="whatsapp-broadcast-form" enctype="multipart/form-data" data-preview-url="{{ route('user.whatsapp.broadcasts.preview-csv') }}" method="POST" action="{{ $broadcast->exists ? route('user.whatsapp.broadcasts.update', $broadcast) : route('user.whatsapp.broadcasts.store') }}">
    @csrf
    @if($broadcast->exists) @method('PUT') @endif
    <div class="template-builder-main">
        @include('partials.broadcast-rejection')
        <article class="template-builder-card" data-billing-estimate data-rate="{{ auth()->user()->broadcast_rate }}" data-balance="{{ auth()->user()->balance }}" data-count="{{ $broadcast->recipient_count ?? 0 }}">
            <h2>Balance & estimated cost</h2>
            <p>Available balance <strong>IDR {{ number_format(auth()->user()->balance, 0, '.', ',') }}</strong>. Rate for this submission <strong>IDR {{ number_format(auth()->user()->broadcast_rate, 0, '.', ',') }} / number</strong>.</p>
            <p data-broadcast-cost aria-live="polite">Upload a CSV to calculate the cost.</p>
            <small>Saving the broadcast locks its rate. Resubmitting uses the latest rate. Your balance is charged on approval, not while Pending or Rejected.</small>
        </article>
        <article class="template-builder-card">
            <h2>Broadcast details</h2>
            <div class="template-builder-field"><label for="broadcast-name">Broadcast name <span class="wa-required">*</span></label><input id="broadcast-name" name="name" value="{{ old('name', $broadcast->name) }}" placeholder="Example: October weekend promotion" maxlength="150" required></div>
            <div class="template-builder-field"><label for="broadcast-template">Message template <span class="wa-required">*</span></label><select id="broadcast-template" name="whatsapp_template_id" required><option value="">Select a WhatsApp template</option>@foreach($templates as $template)<option value="{{ $template->id }}" @selected((string)old('whatsapp_template_id', $broadcast->whatsapp_template_id) === (string)$template->id)>{{ $template->name }} · {{ \App\Models\WhatsappTemplate::LANGUAGES[$template->language] }}</option>@endforeach</select><small>Only your templates approved by an administrator are shown.</small></div>
        </article>
        <article class="template-builder-card">
            <h2>Broadcast schedule</h2>
            <p>Select a date and time in UTC+7. Leave both fields empty if the schedule is undecided.</p>
            <div class="wa-field-grid">
                <div class="template-builder-field"><label for="schedule-date">Broadcast date</label><input id="schedule-date" type="date" name="schedule_date" min="{{ now('Asia/Jakarta')->format('Y-m-d') }}" value="{{ old('schedule_date', $broadcast->scheduled_at?->timezone('Asia/Jakarta')->format('Y-m-d')) }}"></div>
                <div class="template-builder-field"><label for="schedule-time">Broadcast time (UTC+7)</label><input id="schedule-time" type="time" name="schedule_time" value="{{ old('schedule_time', $broadcast->scheduled_at?->timezone('Asia/Jakarta')->format('H:i')) }}" step="60"></div>
            </div>
            <small>The date and time cannot be in the past. The schedule is submitted with the broadcast for administrator review.</small>
        </article>
        <article class="template-builder-card">
            <h2>Upload recipient CSV</h2>
            <p>The first column must be <code>msisdn</code>. Additional columns match your template variables, such as <code>@{{name}}</code> and <code>@{{product}}</code>.</p>
            <div class="template-builder-field">
                <label for="broadcast-csv">Recipient CSV file</label>
                <input id="broadcast-csv" type="file" name="recipients_csv" accept=".csv,text/csv" @required(!$broadcast->exists) aria-describedby="recipients-help">
                <a class="wa-csv-download" href="{{ asset('downloads/sample-broadcast.csv') }}" download="sample-broadcast.csv">Download sample CSV</a>
                <small id="recipients-help">Use comma-separated UTF-8 CSV with no recipient-count limit. Variable names are case-sensitive. Numbers must start with 62 and contain digits only, without + or a local 08 prefix. Duplicate numbers are rejected.</small>
                @if($broadcast->exists)<small>Upload a new file to replace the recipients. Leave it empty to keep the saved data.</small>@endif
                <small data-recipient-count aria-live="polite"></small>
                <p data-csv-error class="notice error" role="alert" hidden></p>
            </div>
            <p>The preview uses the first CSV record. If validation fails after saving, select the CSV file again.</p>
            <div class="wa-info">Save the broadcast to submit it for review. It starts as Pending, then moves to Approved and Finished as the administrator processes it.</div>
            <div class="template-builder-inline-actions"><a class="button secondary" href="{{ route('user.whatsapp.broadcasts.index') }}">Cancel</a><button class="button" type="submit" data-save-broadcast data-unavailable="{{ $templates->isEmpty() ? 'true' : 'false' }}" @disabled($templates->isEmpty())>Save Broadcast →</button></div>
        </article>
    </div>
    @include('user.whatsapp.partials.preview', ['template' => null])
</form>
@endsection
@push('scripts')
<script src="{{ asset('js/broadcast-schedule.js') }}" defer></script>
<script id="broadcast-first-record" type="application/json">@json($broadcast->recipient_records[0] ?? null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script id="whatsapp-template-previews" type="application/json">@json($previews, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script src="{{ asset('js/whatsapp-broadcast.js') }}?v={{ filemtime(public_path('js/whatsapp-broadcast.js')) }}" defer></script>
@endpush
