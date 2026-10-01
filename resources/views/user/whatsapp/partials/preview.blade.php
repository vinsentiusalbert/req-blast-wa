<aside class="template-builder-preview" aria-label="Preview pesan WhatsApp">
    <article class="template-builder-preview__card">
        <h2>Template Preview</h2>
        <div class="wa-preview-account"><span class="wa-preview-avatar" aria-hidden="true"><i class="fa-brands fa-whatsapp"></i></span><div><small>WhatsApp</small></div></div>
        <div class="template-builder-phone">
            <div class="template-builder-phone__message">
                <img class="template-builder-phone__image" data-preview-image @if($template?->asset_path) src="{{ route(auth()->user()->isAdmin() ? 'admin.whatsapp.templates.asset' : 'user.whatsapp.templates.asset', $template) }}" @endif alt="Gambar header template" @if(!$template?->asset_path) hidden @endif>
                <strong class="template-builder-phone__header-text" data-preview-header @if($template?->header_type !== 'TEXT') hidden @endif>{{ $template?->header_text }}</strong>
                <div class="template-builder-phone__body" data-preview-body>{{ $template?->body ?: 'Isi pesan Anda akan tampil di sini.' }}</div>
                <span class="wa-preview-time" aria-hidden="true">09.41 ✓✓</span>
            </div>
        </div>
        <div class="template-builder-preview__foot"><strong>Preview pesan</strong><p>Gambaran tampilan pesan untuk penerima. Tampilan akhir dapat berbeda menurut perangkat.</p></div>
    </article>
</aside>
