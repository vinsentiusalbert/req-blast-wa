<aside class="template-builder-preview" aria-label="WhatsApp message preview">
    <article class="template-builder-preview__card">
        <h2>Template Preview</h2>
        <div class="wa-preview-account"><span class="wa-preview-avatar" aria-hidden="true"><i class="fa-brands fa-whatsapp"></i></span><div><small>WhatsApp</small></div></div>
        <div class="template-builder-phone">
            <div class="template-builder-phone__message">
                <img class="template-builder-phone__image" data-preview-image @if($template?->asset_path) src="{{ route(auth()->user()->isAdmin() ? 'admin.whatsapp.templates.asset' : 'user.whatsapp.templates.asset', $template) }}" @endif alt="Template header image" @if(!$template?->asset_path) hidden @endif>
                <strong class="template-builder-phone__header-text" data-preview-header @if($template?->header_type !== 'TEXT') hidden @endif>{{ \App\Models\WhatsappTemplate::headerContent($template?->header_text) }}</strong>
                <div class="template-builder-phone__body" data-preview-body>{{ $template?->body ?: 'Your message will appear here.' }}</div>
                <span class="wa-preview-time" aria-hidden="true">09.41 ✓✓</span>
            </div>
        </div>
        <div class="template-builder-preview__foot"><strong>Message preview</strong><p>A preview of the message recipients will see. The final appearance may vary by device.</p></div>
    </article>
</aside>
@once
@push('scripts')
<script src="{{ asset('js/whatsapp-text.js') }}?v={{ filemtime(public_path('js/whatsapp-text.js')) }}" defer></script>
<script src="{{ asset('js/whatsapp-preview.js') }}?v={{ filemtime(public_path('js/whatsapp-preview.js')) }}" defer></script>
@endpush
@endonce
