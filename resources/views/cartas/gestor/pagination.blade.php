@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginação" class="cpe-custom-pagination">
        <div>
            @if ($paginator->onFirstPage())
                <span class="cpe-page-btn cpe-page-btn--disabled" aria-disabled="true">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M15.833 10H4.167m0 0L10 15.833M4.167 10L10 4.167" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Anterior</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="cpe-page-btn" rel="prev">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M15.833 10H4.167m0 0L10 15.833M4.167 10L10 4.167" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Anterior</span>
                </a>
            @endif
        </div>

        <div class="cpe-page-numbers">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="cpe-page-num cpe-page-num--dots" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="cpe-page-num cpe-page-num--active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="cpe-page-num">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        <div>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="cpe-page-btn" rel="next">
                    <span>Próximo</span>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M4.167 10h11.666m0 0L10 4.167M15.833 10L10 15.833" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            @else
                <span class="cpe-page-btn cpe-page-btn--disabled" aria-disabled="true">
                    <span>Próximo</span>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M4.167 10h11.666m0 0L10 4.167M15.833 10L10 15.833" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
