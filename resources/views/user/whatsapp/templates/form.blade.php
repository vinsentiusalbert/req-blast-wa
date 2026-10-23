@extends('user.whatsapp.layout')
@section('title', $template->exists ? 'Edit WhatsApp Template' : 'Create WhatsApp Template')
@section('content')
<a class="back-link" href="{{ route('user.whatsapp.templates.index') }}">← WhatsApp Templates</a>
<div class="page-heading wa-page-heading"><div><span class="eyebrow">WHATSAPP TEMPLATES</span><h1>{{ $template->exists ? 'Edit WhatsApp Template' : 'Create WhatsApp Template' }}</h1><p class="muted">Compose your message template and see a live preview.</p></div></div>
@if($template->exists)<p class="wa-info">Changes are resubmitted for administrator review. The template can be used in broadcasts once it is approved again.</p>@endif
<form class="template-builder-layout wa-builder" id="whatsapp-template-form" method="POST" enctype="multipart/form-data" action="{{ $template->exists ? route('user.whatsapp.templates.update', $template) : route('user.whatsapp.templates.store') }}">
    @csrf
    @if($template->exists) @method('PUT') @endif
    <div class="template-builder-main">
        <article class="template-builder-card">
            <h2>Template name and language</h2><p>Give your message a recognizable name.</p>
            <div class="template-builder-field"><label for="template-name">Template name <span class="wa-required">*</span></label><input id="template-name" name="name" value="{{ old('name', $template->name) }}" placeholder="Example: weekend_promo" pattern="[a-z][a-z0-9_]*" maxlength="100" aria-describedby="template-name-help" required><small id="template-name-help">Use lowercase letters, numbers, and underscores. Start with a letter.</small></div>
            <div class="template-builder-field"><label for="template-language">Language <span class="wa-required">*</span></label><select id="template-language" name="language" required>@foreach(\App\Models\WhatsappTemplate::LANGUAGES as $value => $label)<option value="{{ $value }}" @selected(old('language', $template->language) === $value)>{{ $label }}</option>@endforeach</select></div>
        </article>
        <article class="template-builder-card">
            <h2>Message content</h2><p>Add a header and compose the message body.</p>
            <div class="template-builder-field"><label for="template-header-type">Header type</label><select id="template-header-type" name="header_type">@foreach(\App\Models\WhatsappTemplate::HEADER_TYPES as $value => $label)<option value="{{ $value }}" @selected(old('header_type', $template->header_type) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="template-builder-field" data-header-text-field @if(old('header_type', $template->header_type) !== 'TEXT') hidden @endif><label for="template-header-text">Header text</label><input id="template-header-text" name="header_text" value="{{ old('header_text', \App\Models\WhatsappTemplate::headerContent($template->header_text)) }}" maxlength="60" placeholder="Your message title" @disabled(old('header_type', $template->header_type) !== 'TEXT')><small data-header-count>0/60 characters</small><small>Text headers are automatically formatted as <code>*Header text*</code> in WhatsApp messages.</small></div>
            <div class="template-builder-field" data-header-image-field @if(old('header_type', $template->header_type) !== 'IMAGE') hidden @endif>
                <label for="template-asset">Header image</label>
                <div class="template-builder-upload wa-upload"><span class="wa-upload-icon" aria-hidden="true">↑</span><strong>Upload template image</strong><span>JPG, PNG, or WebP. Under 1 MB.</span><input type="file" id="template-asset" name="asset" accept="image/jpeg,image/png,image/webp" data-existing-image="{{ $template->asset_path ? route('user.whatsapp.templates.asset', $template) : '' }}" @disabled(old('header_type', $template->header_type) !== 'IMAGE')><small data-asset-label>{{ $template->asset_path ? 'Image saved. Choose a file to replace it.' : 'No image selected.' }}</small></div>
                <p class="error-text" data-asset-error role="alert" hidden></p>
                @if($errors->any())<small>If you selected a file earlier, select the image again before saving.</small>@endif
            </div>
            @include('user.whatsapp.templates.partials.body-editor')
            <div class="template-builder-inline-actions"><a class="button secondary" href="{{ route('user.whatsapp.templates.index') }}">Cancel</a><button class="button" type="submit" data-save-template>{{ $template->exists ? 'Resubmit Template' : 'Submit Template' }} →</button></div>
        </article>
    </div>
    @include('user.whatsapp.partials.preview', ['template' => $template])
</form>
@endsection
@push('scripts')
<script src="{{ asset('js/whatsapp-editor.js') }}?v={{ filemtime(public_path('js/whatsapp-editor.js')) }}" defer></script>
<script src="{{ asset('js/whatsapp-template.js') }}?v={{ filemtime(public_path('js/whatsapp-template.js')) }}" defer></script>
@endpush
