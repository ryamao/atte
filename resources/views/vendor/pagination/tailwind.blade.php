@if ($paginator->hasPages())
<nav class="pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
    <div class="pagination__inner">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
        <span class="pagination__left" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
            <span aria-hidden="true">
                <svg class="pagination__left-svg" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </span>
        </span>
        @else
        <a class="pagination__left" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">
            <svg fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
        </a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
        {{-- "Three Dots" Separator --}}
        @if (is_string($element))
        <span class="pagination__dots" aria-disabled="true">
            <span>{{ $element }}</span>
        </span>
        @endif

        {{-- Array Of Links --}}
        @if (is_array($element))
        @foreach ($element as $page => $url)
        @if ($page == $paginator->currentPage())
        <span class="pagination__current-page" aria-current="page">
            <span>{{ $page }}</span>
        </span>
        @else
        <a class="pagination__page-link" href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
            {{ $page }}
        </a>
        @endif
        @endforeach
        @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
        <a class="pagination__right" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">
            <svg class="pagination__right-svg" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
        </a>
        @else
        <span class="pagination__right" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
            <span aria-hidden="true">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </span>
        </span>
        @endif
    </div>
</nav>
@endif