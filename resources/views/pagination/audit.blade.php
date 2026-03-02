@if ($paginator->hasPages())
    <nav aria-label="Audit log pagination">
        <ul class="pagination mb-0">
            <li class="page-item @if ($paginator->onFirstPage()) disabled @endif">
                <a class="page-link" href="{{ $paginator->previousPageUrl() ?? '#' }}" rel="prev">Previous</a>
            </li>
            <li class="page-item @if (! $paginator->hasMorePages()) disabled @endif">
                <a class="page-link" href="{{ $paginator->nextPageUrl() ?? '#' }}" rel="next">Next</a>
            </li>
        </ul>
    </nav>
@endif
