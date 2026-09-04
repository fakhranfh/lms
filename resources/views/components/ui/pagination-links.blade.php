{{--
    Molecule: self-contained pagination controls (search, first, previous,
    numbered window, next, last, per-page) for a Livewire-paginated
    collection. Stays visible as the surrounding content's loading/skeleton
    state toggles, so it reads as the list's header/footer chrome rather
    than part of the content being loaded. Renders nothing when there's
    only one page and no search or per-page selector was requested.
--}}
@if ($paginator->hasPages() || $perPageModel || $searchModel)
    <div {{ $attributes->merge(['class' => 'space-y-space-md']) }}>
        @if ($searchModel)
            <div class="flex items-center gap-space-sm">
                <x-ui.search-input :wireModel="$searchModel" :placeholder="$searchPlaceholder" compact class="w-56" />

                @if (trim((string) $search) !== '')
                    <button
                        type="button"
                        wire:click="$set('{{ $searchModel }}', '')"
                        class="text-body-sm text-primary font-medium hover:underline flex-shrink-0"
                    >
                        {{ __('Clear filter') }}
                    </button>
                @endif
            </div>
        @endif

        <div class="flex items-center justify-between gap-space-md flex-wrap">
            <p class="text-body-sm text-on-surface-variant">
                {{ __('Showing') }}
                <span class="font-medium text-on-surface">{{ $paginator->firstItem() }}</span>
                {{ __('to') }}
                <span class="font-medium text-on-surface">{{ $paginator->lastItem() }}</span>
                {{ __('of') }}
                <span class="font-medium text-on-surface">{{ $paginator->total() }}</span>
                {{ __('results') }}
            </p>

            <div class="flex items-center gap-space-md flex-wrap">
                @if ($paginator->hasPages())
                    <div class="flex items-center gap-space-xs">
                        <button
                            type="button"
                            wire:click="gotoPage(1, '{{ $pageName }}')"
                            @disabled($paginator->onFirstPage())
                            class="{{ $iconButtonClasses }}"
                            aria-label="{{ __('First page') }}"
                        >
                            <span class="material-symbols-outlined text-[18px]">first_page</span>
                        </button>
                        <button
                            type="button"
                            wire:click="previousPage('{{ $pageName }}')"
                            @disabled($paginator->onFirstPage())
                            class="{{ $iconButtonClasses }}"
                            aria-label="{{ __('pagination.previous') }}"
                        >
                            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                        </button>

                        @foreach ($paginator->getUrlRange($windowStart, $windowEnd) as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10 border border-primary text-primary font-bold text-body-sm">
                                    {{ $page }}
                                </span>
                            @else
                                <button
                                    type="button"
                                    wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-outline-variant text-on-surface text-body-sm hover:bg-surface-container-lowest transition"
                                    aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                >
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach

                        <button
                            type="button"
                            wire:click="nextPage('{{ $pageName }}')"
                            @disabled(! $paginator->hasMorePages())
                            class="{{ $iconButtonClasses }}"
                            aria-label="{{ __('pagination.next') }}"
                        >
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </button>
                        <button
                            type="button"
                            wire:click="gotoPage({{ $paginator->lastPage() }}, '{{ $pageName }}')"
                            @disabled(! $paginator->hasMorePages())
                            class="{{ $iconButtonClasses }}"
                            aria-label="{{ __('Last page') }}"
                        >
                            <span class="material-symbols-outlined text-[18px]">last_page</span>
                        </button>
                    </div>
                @endif

                @if ($perPageModel)
                    <div class="flex items-center gap-space-sm">
                        <label class="text-body-sm text-on-surface-variant" for="{{ $perPageModel }}-select">{{ __('Per page') }}</label>
                        <select
                            id="{{ $perPageModel }}-select"
                            wire:model.live="{{ $perPageModel }}"
                            class="h-9 px-space-sm rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-sm text-body-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none"
                        >
                            @foreach ($perPageOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
