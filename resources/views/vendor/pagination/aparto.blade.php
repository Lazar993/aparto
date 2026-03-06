@if ($paginator->hasPages())
    <nav class="aparto-pagination-nav" role="navigation" aria-label="Pagination Navigation">
        <span class="aparto-pagination-summary">
            {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} / {{ $paginator->total() }}
        </span>

        <ul class="aparto-pagination-list pagination">
            @if ($paginator->onFirstPage())
                <li class="aparto-pagination-item is-disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="aparto-pagination-item">
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="aparto-pagination-item is-disabled" aria-disabled="true">
                        <span>{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="aparto-pagination-item is-active" aria-current="page">
                                <span>{{ $page }}</span>
                            </li>
                        @else
                            <li class="aparto-pagination-item">
                                <a href="{{ $url }}" aria-label="@lang('Go to page :page', ['page' => $page])">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="aparto-pagination-item">
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="aparto-pagination-item is-disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
