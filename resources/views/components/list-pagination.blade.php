@php
    $pageName = $paginator->getPageName();
    $paginator->appends(request()->except($pageName));
    $total = method_exists($paginator, 'total') ? $paginator->total() : null;
    $lastPage = method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null;
    $selectedSize = $paginator->perPage();
    $sizes = collect(\App\Support\PageSize::OPTIONS)->push($selectedSize)->unique()->sort();
@endphp
<div class="spb-pagination d-flex flex-wrap align-items-center justify-content-between gap-3 py-3 no-print" data-server-pagination>
    <label class="d-flex align-items-center gap-2 m-0 small">
        Rows per page
        <select class="form-select form-select-sm w-auto" data-spb-per-page data-page-name="{{ $pageName }}" aria-label="Rows per page">
            @foreach($sizes as $size)
                <option value="{{ $size }}" @selected($selectedSize === $size)>{{ $size }}</option>
            @endforeach
        </select>
    </label>
    <span class="small text-muted" role="status">
        Showing {{ number_format($paginator->firstItem() ?? 0) }}–{{ number_format($paginator->lastItem() ?? 0) }}
        @if($total !== null) of {{ number_format($total) }} results @endif
    </span>
    @if($paginator->hasPages())
        <nav aria-label="Results pages">
            <ul class="pagination pagination-sm flex-wrap mb-0">
                <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                    @if($paginator->onFirstPage())
                        <span class="page-link" aria-disabled="true">Previous</span>
                    @else
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
                    @endif
                </li>
                @if($lastPage !== null)
                    @php $previous = 0; @endphp
                    @foreach(collect([1, ...range(max(1, min($paginator->currentPage(), $lastPage) - 2), min($lastPage, $paginator->currentPage() + 2)), $lastPage])->unique()->sort() as $page)
                        @if($page > $previous + 1)<li class="page-item disabled"><span class="page-link">…</span></li>@endif
                        <li class="page-item {{ $page === $paginator->currentPage() ? 'active' : '' }}">
                            @if($page === $paginator->currentPage())
                                <span class="page-link" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="page-link" href="{{ $paginator->url($page) }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                        @php $previous = $page; @endphp
                    @endforeach
                @endif
                <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                    @if($paginator->hasMorePages())
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
                    @else
                        <span class="page-link" aria-disabled="true">Next</span>
                    @endif
                </li>
            </ul>
        </nav>
    @endif
</div>
