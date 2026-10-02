<div class="section-label"><h2>Ringkasan template WhatsApp</h2><span>{{ auth()->user()->isAdmin() ? 'SELURUH PENGGUNA' : 'DATA ANDA' }}</span></div>
<div class="stats">
    <article class="card stat-card"><div class="stat-heading"><span>Total template</span></div><strong class="stat">{{ number_format($totalTemplates, 0, ',', '.') }}</strong><small>Seluruh template pesan</small></article>
    @foreach(\App\Models\WhatsappTemplate::APPROVAL_LABELS as $status => $label)
        <article class="card stat-card"><div class="stat-heading"><span>Template {{ strtolower($label) }}</span></div><strong class="stat">{{ number_format($templateCounts[$status], 0, ',', '.') }}</strong><small>{{ $status === 'approved' ? 'Siap digunakan untuk broadcast' : ($status === 'pending' ? 'Menunggu review admin' : 'Perlu diperbaiki sebelum diajukan ulang') }}</small></article>
    @endforeach
</div>
<div class="section-label"><h2>Ringkasan broadcast WhatsApp</h2><span>BERDASARKAN STATUS</span></div>
<div class="stats">
    <article class="card stat-card"><div class="stat-heading"><span>Total broadcast</span></div><strong class="stat">{{ number_format($totalBroadcasts, 0, ',', '.') }}</strong><small>Seluruh broadcast tersimpan</small></article>
    @foreach($broadcastCounts as $status => $count)
        <article class="card stat-card"><div class="stat-heading"><span>Broadcast {{ ['draft' => 'draft', 'pending' => 'menunggu', 'queued' => 'dalam antrean', 'scheduled' => 'terjadwal', 'sending' => 'sedang dikirim', 'sent' => 'terkirim', 'completed' => 'selesai', 'failed' => 'gagal', 'cancelled' => 'dibatalkan'][$status] ?? str_replace('_', ' ', $status) }}</span></div><strong class="stat">{{ number_format($count, 0, ',', '.') }}</strong><small>{{ $status === 'draft' ? 'Belum dikirim' : 'Jumlah broadcast dengan status ini' }}</small></article>
    @endforeach
</div>
