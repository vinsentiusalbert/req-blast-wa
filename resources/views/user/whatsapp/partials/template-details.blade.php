<article class="template-builder-card">
    <h2>Data template</h2>
    <div class="template-builder-field">
        <label for="template-name">Nama template</label>
        <input id="template-name" name="name" value="{{ $template->name }}" disabled>
    </div>
    <div class="template-builder-field">
        <label for="template-language">Bahasa</label>
        <select id="template-language" name="language" disabled>
            @foreach(\App\Models\WhatsappTemplate::LANGUAGES as $value => $label)
                <option value="{{ $value }}" @selected($template->language === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="template-builder-field">
        <label for="template-header-type">Tipe header</label>
        <select id="template-header-type" name="header_type" disabled>
            @foreach(\App\Models\WhatsappTemplate::HEADER_TYPES as $value => $label)
                <option value="{{ $value }}" @selected($template->header_type === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @if($template->header_type === 'TEXT')
        <div class="template-builder-field">
            <label for="template-header-text">Teks header</label>
            <input id="template-header-text" name="header_text" value="{{ $template->header_text }}" disabled>
        </div>
    @elseif($template->header_type === 'IMAGE')
        <div class="template-builder-field">
            <label for="template-asset">Gambar header</label>
            <input id="template-asset" value="{{ $template->asset_path ? basename($template->asset_path) : 'Tidak ada gambar tersimpan' }}" disabled>
            @if($template->asset_path)
                <img class="wa-detail-image" src="{{ route(auth()->user()->isAdmin() ? 'admin.whatsapp.templates.asset' : 'user.whatsapp.templates.asset', $template) }}" alt="Gambar header template">
            @endif
        </div>
    @endif
    <div class="template-builder-field">
        <label for="template-body">Isi pesan</label>
        <textarea id="template-body" name="body" class="wa-recipients" rows="7" disabled>{{ $template->body }}</textarea>
    </div>
</article>
