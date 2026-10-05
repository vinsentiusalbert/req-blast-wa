<article class="template-builder-card">
    <h2>Informasi broadcast</h2>
    <div class="template-builder-field">
        <label for="broadcast-name">Nama broadcast</label>
        <input id="broadcast-name" name="name" value="{{ $broadcast->name }}" disabled>
    </div>
    <div class="template-builder-field">
        <label for="broadcast-template">Template pesan</label>
        <select id="broadcast-template" name="whatsapp_template_id" disabled>
            <option value="{{ $broadcast->whatsapp_template_id }}" selected>{{ $broadcast->template->name }} · {{ \App\Models\WhatsappTemplate::LANGUAGES[$broadcast->template->language] }}</option>
        </select>
    </div>
    <div class="wa-info">
        <strong>Jadwal pengiriman (UTC+7 / WIB)</strong>
        @if($broadcast->sending_starts_at && $broadcast->sending_ends_at)
            <p>Mulai {{ $broadcast->sending_starts_at->timezone(\App\Models\WhatsappBroadcast::SENDING_TIMEZONE)->format('d M Y, H:i') }}<br>Sampai {{ $broadcast->sending_ends_at->timezone(\App\Models\WhatsappBroadcast::SENDING_TIMEZONE)->format('d M Y, H:i') }}</p>
        @else
            <p>Jadwal belum ditentukan.</p>
        @endif
    </div>
</article>
