<article class="template-builder-card">
    <h2>Partisi jadwal pengiriman</h2>
    <p>Nomor pengirim dipilih secara acak dari nomor yang aktif untuk setiap pesan.</p>
    <p>Total {{ $broadcast->recipient_count }} penerima. Zona waktu: Asia/Bangkok (UTC+7). Jumlah seluruh partisi harus mencakup semua penerima.</p>
    @if($broadcast->schedules->isNotEmpty())
        <div class="table-scroll"><table><thead><tr><th>Tanggal</th><th>Jumlah pesan</th><th>Status</th></tr></thead><tbody>@foreach($broadcast->schedules as $schedule)<tr><td>{{ $schedule->send_date->format('d M Y') }}</td><td>{{ $schedule->message_count }}</td><td>{{ $schedule->status }}</td></tr>@endforeach</tbody></table></div>
    @endif
    @if($broadcast->status === 'accepted' && !$broadcast->schedules->contains(fn ($schedule) => $schedule->status !== 'pending') && !$broadcast->recipientEntries()->where('delivery_status', '!=', 'pending')->exists())
        @if($senders->isEmpty())
            <p class="wa-info">Tambahkan atau aktifkan <a class="wa-download-link" href="{{ route('admin.whatsapp.senders.index') }}">nomor pengirim</a> terlebih dahulu.</p>
        @else
            <form method="POST" action="{{ route('admin.whatsapp.campaigns.schedules.update', $broadcast) }}" id="campaign-schedule-form">
                @csrf @method('PUT')
                <div id="schedule-rows">
                    @foreach(old('schedules', $broadcast->schedules->isEmpty() ? [['send_date' => '', 'message_count' => $broadcast->recipient_count]] : $broadcast->schedules->map(fn ($schedule) => ['send_date' => $schedule->send_date->format('Y-m-d'), 'message_count' => $schedule->message_count])->all()) as $index => $row)
                        <div class="schedule-row">
                            <div class="template-builder-field"><label>Tanggal kirim<input type="date" name="schedules[{{ $index }}][send_date]" value="{{ $row['send_date'] ?? '' }}" min="{{ now('Asia/Bangkok')->toDateString() }}" required></label></div>
                            <div class="template-builder-field"><label>Jumlah pesan<input type="number" name="schedules[{{ $index }}][message_count]" value="{{ $row['message_count'] ?? '' }}" min="1" max="{{ $broadcast->recipient_count }}" required></label></div>
                            <p><button class="button secondary small" type="button" data-remove-schedule>Hapus partisi</button></p>
                        </div>
                    @endforeach
                </div>
                <p id="schedule-total" aria-live="polite"></p>
                <div class="template-builder-inline-actions"><button class="button secondary" type="button" id="add-schedule">Tambah tanggal / partisi</button><button class="button" type="submit">Simpan Jadwal</button></div>
            </form>
        @endif
    @else
        <p class="wa-info">Jadwal dapat dikonfigurasi setelah campaign diterima dan sebelum pengiriman dimulai.</p>
    @endif
</article>
