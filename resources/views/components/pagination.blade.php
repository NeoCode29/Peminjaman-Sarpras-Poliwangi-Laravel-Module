@if ($paginator->hasPages())
    <nav class="data-pagination" role="navigation" aria-label="Paginasi">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            {{-- Info jumlah data --}}
            <p style="margin:0;font-size:0.85rem;color:var(--text-muted);">
                Menampilkan
                <strong>{{ $paginator->firstItem() ?? 0 }}</strong>
                -
                <strong>{{ $paginator->lastItem() ?? 0 }}</strong>
                dari
                <strong>{{ $paginator->total() }}</strong>
                data
            </p>

            {{-- Links pagination --}}
            <ul style="display:flex;align-items:center;gap:4px;list-style:none;padding:0;margin:0;">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li>
                        <span style="min-width:34px;height:34px;padding:0 10px;border-radius:999px;border:1px solid var(--border-default);background:var(--surface-base);color:var(--interactive-disabled-text);font-size:0.85rem;display:inline-flex;align-items:center;justify-content:center;cursor:not-allowed;">
                            &laquo;
                        </span>
                    </li>
                @else
                    <li>
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                           style="min-width:34px;height:34px;padding:0 10px;border-radius:999px;border:1px solid var(--border-default);background:var(--surface-card);color:var(--text-main);font-size:0.85rem;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">
                            &laquo;
                        </a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- Dots --}}
                    @if (is_string($element))
                        <li>
                            <span style="min-width:34px;height:34px;padding:0 10px;border-radius:999px;border:1px solid transparent;color:var(--text-muted);font-size:0.85rem;display:inline-flex;align-items:center;justify-content:center;">
                                {{ $element }}
                            </span>
                        </li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li>
                                    <span style="min-width:34px;height:34px;padding:0 10px;border-radius:999px;border:1px solid var(--brand-primary);background:var(--brand-primary);color:var(--brand-on-primary);font-size:0.85rem;font-weight:600;display:inline-flex;align-items:center;justify-content:center;">
                                        {{ $page }}
                                    </span>
                                </li>
                            @else
                                <li>
                                    <a href="{{ $url }}"
                                       style="min-width:34px;height:34px;padding:0 10px;border-radius:999px;border:1px solid var(--border-default);background:var(--surface-card);color:var(--text-main);font-size:0.85rem;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">
                                        {{ $page }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li>
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                           style="min-width:34px;height:34px;padding:0 10px;border-radius:999px;border:1px solid var(--border-default);background:var(--surface-card);color:var(--text-main);font-size:0.85rem;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">
                            &raquo;
                        </a>
                    </li>
                @else
                    <li>
                        <span style="min-width:34px;height:34px;padding:0 10px;border-radius:999px;border:1px solid var(--border-default);background:var(--surface-base);color:var(--interactive-disabled-text);font-size:0.85rem;display:inline-flex;align-items:center;justify-content:center;cursor:not-allowed;">
                            &raquo;
                        </span>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
@endif
