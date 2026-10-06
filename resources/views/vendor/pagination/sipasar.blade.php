@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Halaman">
        @if ($paginator->onFirstPage())
            <span class="btn btn-soft is-disabled" aria-disabled="true">Sebelumnya</span>
        @else
            <a class="btn btn-soft" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
        @endif

        <span class="pager-info">
            Halaman {{ $paginator->currentPage() }}@if (method_exists($paginator, 'lastPage')) dari {{ $paginator->lastPage() }}@endif
        </span>

        @if ($paginator->hasMorePages())
            <a class="btn btn-soft" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
        @else
            <span class="btn btn-soft is-disabled" aria-disabled="true">Berikutnya</span>
        @endif
    </nav>
@endif
