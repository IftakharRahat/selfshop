@if ($paginator->hasPages())
    <nav>
        <ul class="pagination justify-content-center mb-0" style="gap: 4px;">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled">
                    <span class="page-link" style="border-radius: 6px; border: 1px solid #e2e8f0; color: #94a3b8; font-size: 13px; padding: 6px 12px;">&laquo; Previous</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" style="border-radius: 6px; border: 1px solid #e2e8f0; color: #334155; font-size: 13px; padding: 6px 12px; cursor: pointer;">&laquo; Previous</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled"><span class="page-link" style="border-radius: 6px; border: 1px solid #e2e8f0; color: #94a3b8; font-size: 13px; padding: 6px 10px;">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active">
                                <span class="page-link" style="border-radius: 6px; background: #2d2a5d; border-color: #2d2a5d; color: #fff; font-size: 13px; padding: 6px 10px; min-width: 36px; text-align: center;">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}" style="border-radius: 6px; border: 1px solid #e2e8f0; color: #334155; font-size: 13px; padding: 6px 10px; min-width: 36px; text-align: center;">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" style="border-radius: 6px; border: 1px solid #e2e8f0; color: #334155; font-size: 13px; padding: 6px 12px; cursor: pointer;">Next &raquo;</a>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link" style="border-radius: 6px; border: 1px solid #e2e8f0; color: #94a3b8; font-size: 13px; padding: 6px 12px;">Next &raquo;</span>
                </li>
            @endif
        </ul>

        <div class="text-center mt-2" style="font-size: 12px; color: #64748b;">
            Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} results
        </div>
    </nav>
@endif
