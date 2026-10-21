@php
    $sent = (int) $counts->get('sent', 0);
    $failed = (int) $counts->get('failed', 0);
    $pending = (int) $counts->get('pending', 0);
    $chartTotal = max((int) $target, (int) $counts->sum());
    $remaining = max(0, $chartTotal - $sent - $failed - $pending);
    $segments = [
        ['label' => 'Terkirim', 'count' => $sent, 'color' => '#16a34a'],
        ['label' => 'Menunggu', 'count' => $pending, 'color' => '#f59e0b'],
    ];
    if ($failed > 0) {
        $segments[] = ['label' => 'Gagal', 'count' => $failed, 'color' => '#ef4444'];
    }
    if ($remaining > 0) {
        $segments[] = ['label' => 'Belum ada hasil', 'count' => $remaining, 'color' => '#cbd5e1'];
    }
    $stops = [];
    $offset = 0;
    foreach ($segments as $segment) {
        $end = $offset + ($chartTotal > 0 ? $segment['count'] / $chartTotal * 100 : 0);
        if ($segment['count'] > 0) {
            $stops[] = $segment['color'].' '.$offset.'% '.$end.'%';
        }
        $offset = $end;
    }
    $background = $chartTotal > 0 ? 'conic-gradient('.implode(', ', $stops).')' : '#e2e8f0';
@endphp
<section class="report-chart" aria-labelledby="report-chart-title">
    <div class="report-chart-heading"><div><h2 id="report-chart-title">Hasil pengiriman</h2><p>Distribusi status penerima broadcast</p></div><span class="report-count">{{ number_format($target, 0, ',', '.') }} target penerima</span></div>
    <div class="report-chart-body">
        <div class="report-pie" style="background: {{ $background }}" role="img" aria-label="Diagram lingkaran hasil pengiriman. {{ collect($segments)->map(fn ($segment) => $segment['label'].': '.$segment['count'])->implode(', ') }}{{ $chartTotal === 0 ? '. Belum ada data.' : '' }}"></div>
        <div class="report-chart-details">
            @if($chartTotal > 0)
                <div class="report-chart-highlight"><strong>{{ number_format($sent / $chartTotal * 100, 1, ',', '.') }}%</strong><span>penerima sudah terkirim</span></div>
            @else
                <div class="report-chart-highlight"><strong>Belum ada data</strong><span>Grafik akan terisi saat data penerima tersedia.</span></div>
            @endif
            <ul class="report-legend" aria-label="Rincian status pengiriman">
                @foreach($segments as $segment)
                    <li><span class="report-legend-label"><span class="report-legend-dot" style="background: {{ $segment['color'] }}" aria-hidden="true"></span>{{ $segment['label'] }}</span><strong>{{ number_format($segment['count'], 0, ',', '.') }}</strong><span class="report-legend-percent">{{ $chartTotal > 0 ? number_format($segment['count'] / $chartTotal * 100, 1, ',', '.') : '0' }}%</span></li>
                @endforeach
            </ul>
            @if($remaining > 0)<p class="report-chart-note">Belum ada hasil: penerima belum memiliki status pengiriman.</p>@endif
        </div>
    </div>
</section>
