@use('App\Support\PersianNumber')

@if ($paginator->hasPages())
    <nav class="pagination" aria-label="صفحه‌بندی">
        @if ($paginator->onFirstPage())
            <span class="pagination__link is-disabled" aria-disabled="true">قبلی</span>
        @else
            <a class="pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">قبلی</a>
        @endif

        <ol class="pagination__pages">
            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                <li>
                    @if ($page === $paginator->currentPage())
                        <span class="pagination__link is-active" aria-current="page">{{ PersianNumber::digits($page) }}</span>
                    @else
                        <a class="pagination__link" href="{{ $url }}">{{ PersianNumber::digits($page) }}</a>
                    @endif
                </li>
            @endforeach
        </ol>

        @if ($paginator->hasMorePages())
            <a class="pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next">بعدی</a>
        @else
            <span class="pagination__link is-disabled" aria-disabled="true">بعدی</span>
        @endif
    </nav>
@endif
