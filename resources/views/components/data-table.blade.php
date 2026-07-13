@props(['title' => null, 'tableId' => 'dataTable', 'listUrl' => '#', 'filters' => []])

@if (!empty($filters))
    <div class="mb-4 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4" id="{{ $tableId }}-filters">
        <div class="flex flex-wrap gap-4 items-end">
            @foreach ($filters as $filter)
                @if ($filter['type'] === 'text')
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            {{ __($filter['label']) }}
                        </label>
                        <input
                            type="text"
                            data-filter-table="{{ $tableId }}"
                            data-filter-key="{{ $filter['key'] }}"
                            placeholder="{{ __('Search') }} {{ __($filter['label']) }}..."
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                @elseif ($filter['type'] === 'enum')
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            {{ __($filter['label']) }}
                        </label>
                        <select
                            data-filter-table="{{ $tableId }}"
                            data-filter-key="{{ $filter['key'] }}"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($filter['options'] as $value => $label)
                                <option value="{{ $value }}">{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif ($filter['type'] === 'datetime')
                    <div class="min-w-[160px]">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            {{ __($filter['label']) }} {{ __('From') }}
                        </label>
                        <input
                            type="date"
                            data-filter-table="{{ $tableId }}"
                            data-filter-key="{{ $filter['key'] }}_from"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="min-w-[160px]">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            {{ __($filter['label']) }} {{ __('To') }}
                        </label>
                        <input
                            type="date"
                            data-filter-table="{{ $tableId }}"
                            data-filter-key="{{ $filter['key'] }}_to"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                @endif
            @endforeach
            <div class="flex gap-2">
                <button onclick="applyFilters('{{ $tableId }}')"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">
                    {{ __('Filter') }}
                </button>
                <button onclick="resetFilters('{{ $tableId }}')"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-white text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                    {{ __('Reset') }}
                </button>
            </div>
        </div>
    </div>
@endif

<!-- Data Table Card -->
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ $title ?? __('Data') }}
        </h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-gray-700 dark:text-gray-300 display" id="{{ $tableId }}" data-list-url="{{ $listUrl }}">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    {{ $headers }}
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <!-- Data will be loaded here -->
            </tbody>
        </table>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-3 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <label for="{{ $tableId }}-per-page">{{ __('Rows per page') }}</label>
            <select id="{{ $tableId }}-per-page" data-perpage-table="{{ $tableId }}"
                class="border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span id="{{ $tableId }}-summary"></span>
        </div>
        <div id="{{ $tableId }}-pagination" class="flex items-center gap-1"></div>
    </div>
</div>
