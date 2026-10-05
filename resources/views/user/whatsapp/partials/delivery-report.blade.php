<article class="template-builder-card wa-dlr" id="delivery-report">
    <div class="wa-message-browser__heading">
        <div>
            <h2>Delivery report (DLR)</h2>
            <p>Status pengiriman setiap nomor penerima broadcast.</p>
        </div>
        <a class="button secondary small" href="{{ route(auth()->user()->isAdmin() ? 'admin.whatsapp.campaigns.dlr.export' : 'user.whatsapp.broadcasts.dlr.export', ['broadcast' => $broadcast] + array_filter($dlrFilters, fn ($value) => $value !== '')) }}">Unduh CSV</a>
    </div>
    <div class="wa-dlr__stats" aria-label="Ringkasan status semua penerima">
        @foreach(\App\Models\WhatsappBroadcastRecipient::REPORT_LABELS as $status => $label)
            <div class="wa-dlr__stat wa-dlr__stat--{{ $status }}">
                <span>{{ $label }}</span><strong>{{ number_format($dlrCounts[$status], 0, ',', '.') }}</strong>
            </div>
        @endforeach
    </div>
    <p class="wa-dlr__help">Success berarti pesan dikonfirmasi diterima atau dibaca. Sent menunggu konfirmasi penerimaan. Pending belum memiliki hasil akhir. Status dan alasan failed diperbarui oleh layanan pengiriman setelah integrasi terhubung.</p>
    <form class="wa-message-search" method="GET" action="{{ url()->current() }}#delivery-report" role="search" aria-label="Filter delivery report">
        <div class="template-builder-field">
            <label for="dlr-search">Nomor WhatsApp</label>
            <input id="dlr-search" type="search" name="dlr_search" value="{{ $dlrFilters['dlr_search'] }}" placeholder="Cari nomor lengkap atau sebagian" maxlength="30">
        </div>
        <div class="template-builder-field">
            <label for="dlr-status">Status pengiriman</label>
            <select id="dlr-status" name="dlr_status">
                <option value="">Semua status</option>
                @foreach(\App\Models\WhatsappBroadcastRecipient::REPORT_LABELS as $status => $label)<option value="{{ $status }}" @selected($dlrFilters['dlr_status'] === $status)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <button class="button" type="submit">Tampilkan</button>
        @if($dlrFilters['dlr_search'] !== '' || $dlrFilters['dlr_status'] !== '')<a class="button secondary" href="{{ url()->current() }}#delivery-report">Reset</a>@endif
    </form>
    <p class="wa-message-browser__range" role="status">Menampilkan {{ number_format($dlrRows->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($dlrRows->lastItem() ?? 0, 0, ',', '.') }} dari {{ number_format($dlrRows->total(), 0, ',', '.') }} penerima. CSV mengikuti filter dan mencakup semua halaman.</p>
    <div class="table-scroll wa-dlr__table" tabindex="0" role="region" aria-label="Status pengiriman per nomor">
        <table>
            <thead><tr><th scope="col">Nomor WhatsApp</th><th scope="col">Status</th><th scope="col">Waktu kirim <small>(UTC+7)</small></th><th scope="col">Keterangan failed</th></tr></thead>
            <tbody>
                @forelse($dlrRows as $entry)
                    <tr>
                        <td class="wa-dlr__phone">+{{ $entry->phone_number }}</td>
                        <td><span class="badge wa-dlr-status--{{ $entry->reportStatus() }}">{{ \App\Models\WhatsappBroadcastRecipient::REPORT_LABELS[$entry->reportStatus()] }}</span></td>
                        <td>{{ $entry->sent_at?->timezone('Asia/Bangkok')->format('d M Y, H:i:s') ?? '—' }}</td>
                        <td class="wa-dlr__error">{{ $entry->failureDescription() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">Tidak ada penerima yang cocok. Ubah filter atau kembali ke halaman pertama.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($dlrRows->hasPages())
        <nav class="wa-message-pagination" aria-label="Halaman delivery report">
            @if($dlrRows->onFirstPage())<span class="button secondary" aria-disabled="true">Sebelumnya</span>@else<a class="button secondary" href="{{ $dlrRows->previousPageUrl() }}" rel="prev">Sebelumnya</a>@endif
            <form class="wa-message-pagination__jump" method="GET" action="{{ url()->current() }}#delivery-report">
                @foreach($dlrFilters as $key => $value)@if($value !== '')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
                <label for="dlr-page">Halaman</label>
                <input id="dlr-page" type="number" name="dlr_page" value="{{ $dlrRows->currentPage() }}" min="1" max="{{ $dlrRows->lastPage() }}" required>
                <span>/ {{ number_format($dlrRows->lastPage(), 0, ',', '.') }}</span>
                <button class="button secondary" type="submit">Buka</button>
            </form>
            @if($dlrRows->hasMorePages())<a class="button secondary" href="{{ $dlrRows->nextPageUrl() }}" rel="next">Berikutnya</a>@else<span class="button secondary" aria-disabled="true">Berikutnya</span>@endif
        </nav>
    @endif
    @if(array_sum($dlrCounts) === 0 && !empty($broadcast->recipients))
        <p class="wa-info">Data status per nomor belum tersedia untuk broadcast ini.</p>
    @endif
</article>
