<article class="template-builder-card">
    <h2>Daftar penerima</h2>
    <p>{{ number_format($broadcast->recipient_count, 0, ',', '.') }} nomor unik tersimpan. Unduh CSV untuk melihat seluruh daftar penerima.</p>
    <a class="button secondary" href="{{ route(auth()->user()->isAdmin() ? 'admin.whatsapp.campaigns.recipients.export' : 'user.whatsapp.broadcasts.recipients.export', $broadcast) }}">Unduh Daftar Penerima (CSV)</a>
</article>
