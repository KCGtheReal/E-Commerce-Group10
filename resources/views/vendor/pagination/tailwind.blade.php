@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="w-full">

        {{-- Mobile Pagination: Previous, Page Numbers in the Middle, Next --}}
        <div class="flex items-center justify-between sm:hidden w-full gap-2">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-3 py-2 text-xs font-display uppercase tracking-wider text-neutral-400 dark:text-neutral-500 bg-white dark:bg-neutral-800 border border-brand-border dark:border-neutral-700 rounded-lg cursor-not-allowed opacity-60">
                    &laquo; Prev
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-3 py-2 text-xs font-display uppercase tracking-wider text-brand-black dark:text-neutral-100 bg-white dark:bg-neutral-800 border border-brand-border dark:border-neutral-700 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-700 transition">
                    &laquo; Prev
                </a>
            @endif

            {{-- Numbered Pages in the Middle --}}
            <div class="flex items-center gap-1 font-display text-xs">
                @foreach ($elements as $element)
                    {{-- Three Dots Separator --}}
                    @if (is_string($element))
                        <span class="px-1 text-neutral-400 dark:text-neutral-500 font-bold">{{ $element }}</span>
                    @endif

                    {{-- Page Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="w-8 h-8 inline-flex items-center justify-center font-bold bg-brand-black text-white dark:bg-white dark:text-brand-black rounded-lg shadow-xs">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="w-8 h-8 inline-flex items-center justify-center font-semibold text-brand-black dark:text-neutral-200 bg-white dark:bg-neutral-800 border border-brand-border dark:border-neutral-700 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-700 transition">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-3 py-2 text-xs font-display uppercase tracking-wider text-brand-black dark:text-neutral-100 bg-white dark:bg-neutral-800 border border-brand-border dark:border-neutral-700 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-700 transition">
                    Next &raquo;
                </a>
            @else
                <span class="inline-flex items-center px-3 py-2 text-xs font-display uppercase tracking-wider text-neutral-400 dark:text-neutral-500 bg-white dark:bg-neutral-800 border border-brand-border dark:border-neutral-700 rounded-lg cursor-not-allowed opacity-60">
                    Next &raquo;
                </span>
            @endif
        </div>

        {{-- Desktop Pagination --}}
        <div class="hidden sm:flex items-center justify-between gap-4 w-full">
            <div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 font-display">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-bold text-brand-black dark:text-neutral-100">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-bold text-brand-black dark:text-neutral-100">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('of') !!}
                    <span class="font-bold text-brand-black dark:text-neutral-100">{{ $paginator->total() }}</span>
                    {!! __('products') !!}
                </p>
            </div>

            <div class="flex items-center gap-1.5 font-display text-xs">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center px-3.5 py-2 text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 bg-white dark:bg-neutral-800 border border-brand-border dark:border-neutral-700 rounded-lg cursor-not-allowed opacity-60">
                        &laquo; {{ __('pagination.previous') }}
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold uppercase tracking-wider text-brand-black dark:text-neutral-100 bg-white dark:bg-neutral-800 border border-brand-border dark:border-neutral-700 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-700 transition">
                        &laquo; {{ __('pagination.previous') }}
                    </a>
                @endif

                {{-- Numbered Page Links --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-2 text-neutral-400 dark:text-neutral-500 font-bold">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="w-9 h-9 inline-flex items-center justify-center font-bold bg-brand-black text-white dark:bg-white dark:text-brand-black rounded-lg shadow-xs">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="w-9 h-9 inline-flex items-center justify-center font-semibold text-brand-black dark:text-neutral-200 bg-white dark:bg-neutral-800 border border-brand-border dark:border-neutral-700 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-700 transition">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold uppercase tracking-wider text-brand-black dark:text-neutral-100 bg-white dark:bg-neutral-800 border border-brand-border dark:border-neutral-700 rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-700 transition">
                        {{ __('pagination.next') }} &raquo;
                    </a>
                @else
                    <span class="inline-flex items-center px-3.5 py-2 text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 bg-white dark:bg-neutral-800 border border-brand-border dark:border-neutral-700 rounded-lg cursor-not-allowed opacity-60">
                        {{ __('pagination.next') }} &raquo;
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
