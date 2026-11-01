@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="btn-ghost pointer-events-none opacity-40">Newer</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="btn-ghost">Newer</a>
        @endif
        <span class="text-sm text-ink-300">Page {{ $paginator->currentPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="btn-ghost">Older</a>
        @else
            <span class="btn-ghost pointer-events-none opacity-40">Older</span>
        @endif
    </nav>
@endif
