@if($paginator->hasPages())
<nav class="pagination" aria-label="Halaman daftar">
    @if($paginator->onFirstPage())<span class="muted">← Sebelumnya</span>@else<a href="{{ $paginator->previousPageUrl() }}">← Sebelumnya</a>@endif
    <span>{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
    @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}">Berikutnya →</a>@else<span class="muted">Berikutnya →</span>@endif
</nav>
@endif
