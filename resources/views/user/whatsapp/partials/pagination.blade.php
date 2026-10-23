@if($paginator->hasPages())
<nav class="pagination" aria-label="List pages">
    @if($paginator->onFirstPage())<span class="muted">← Previous</span>@else<a href="{{ $paginator->previousPageUrl() }}">← Previous</a>@endif
    <span>{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
    @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}">Next →</a>@else<span class="muted">Next →</span>@endif
</nav>
@endif
