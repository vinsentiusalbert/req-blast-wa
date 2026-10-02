<article class="template-builder-card">
    <h2>Contoh pesan per penerima</h2>
    <p>Menampilkan hingga 20 penerima pertama sesuai variabel CSV.</p>
    @foreach($broadcast->recipientEntries()->limit(20)->get() as $entry)
        <div class="wa-info"><strong>+{{ $entry->phone_number }}</strong>
            @if($broadcast->template->header_type === 'TEXT')<p>{{ $entry->renderMessage($broadcast->template->header_text ?? '') }}</p>@endif
            <p style="white-space: pre-wrap">{{ $entry->renderMessage($broadcast->template->body) }}</p>
        </div>
    @endforeach
</article>
