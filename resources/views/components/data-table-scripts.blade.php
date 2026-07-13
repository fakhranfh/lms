@push('scripts')
<script>
// Per-table state: current page, per-page size, sort column/direction
window.__dataTableState = window.__dataTableState || {};

function getTableState(tableId) {
    if (!window.__dataTableState[tableId]) {
        window.__dataTableState[tableId] = { page: 1, perPage: 10, sort: null, direction: 'desc' };
    }
    return window.__dataTableState[tableId];
}

// Render skeleton (animate-pulse) placeholder rows while data is loading
function renderSkeletonRows(tbody, columnCount, rows = 5) {
    tbody.innerHTML = '';
    for (let i = 0; i < rows; i++) {
        const row = document.createElement('tr');
        let cells = '';
        for (let c = 0; c < columnCount; c++) {
            cells += `<td class="px-6 py-3"><div class="h-4 bg-gray-200 dark:bg-gray-600 rounded animate-pulse"></div></td>`;
        }
        row.innerHTML = cells;
        tbody.appendChild(row);
    }
}

// Load data for table
function loadTableData(tableId, listUrl, renderCallback) {
    const tbody = document.querySelector(`#${tableId} tbody`);
    const table = document.getElementById(tableId);
    if (!tbody || !table) {
        console.error(`Table with id ${tableId} not found`);
        return;
    }

    const columnCount = table.querySelectorAll('thead th').length || 1;
    renderSkeletonRows(tbody, columnCount);

    fetch(listUrl, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            tbody.innerHTML = '';
            if (data.data && data.data.length > 0) {
                data.data.forEach(item => {
                    const row = renderCallback(item);
                    tbody.appendChild(row);
                });
            } else {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `<td colspan="100%" class="px-6 py-4 text-center text-gray-500">{{ __('No data available') }}</td>`;
                tbody.appendChild(emptyRow);
            }

            if (data.meta) {
                renderPaginationControls(tableId, data.meta);
                updateSortIndicators(tableId);
            }
        })
        .catch(e => {
            console.error(`Failed to load data for ${tableId}:`, e);
            const errorRow = document.createElement('tr');
            errorRow.innerHTML = `<td colspan="100%" class="px-6 py-4 text-center text-red-500">{{ __('Failed to load data') }}</td>`;
            tbody.appendChild(errorRow);
        });
}

// Build URL with filter query params collected from data-filter-table inputs,
// plus sort/direction/page/per_page from the table's current state
function buildFilterUrl(tableId) {
    const table = document.getElementById(tableId);
    const baseUrl = table ? table.dataset.listUrl : '#';
    const state = getTableState(tableId);
    const params = new URLSearchParams();

    document.querySelectorAll(`[data-filter-table="${tableId}"]`).forEach(input => {
        const value = input.value.trim();
        if (value) {
            params.set(input.dataset.filterKey, value);
        }
    });

    if (state.sort) {
        params.set('sort', state.sort);
        params.set('direction', state.direction);
    }
    params.set('per_page', state.perPage);
    params.set('page', state.page);

    const qs = params.toString();
    return baseUrl + (qs ? '?' + qs : '');
}

function applyFilters(tableId) {
    getTableState(tableId).page = 1;
    const functionName = 'load' + tableId.charAt(0).toUpperCase() + tableId.slice(1);
    if (typeof window[functionName] === 'function') {
        window[functionName]();
    }
}

function resetFilters(tableId) {
    document.querySelectorAll(`[data-filter-table="${tableId}"]`).forEach(input => {
        input.value = '';
    });
    applyFilters(tableId);
}

function updateSortIndicators(tableId) {
    const state = getTableState(tableId);
    document.querySelectorAll(`[data-sort-table="${tableId}"]`).forEach(th => {
        const indicator = th.querySelector('.sort-indicator');
        if (!indicator) {
            return;
        }
        if (th.dataset.sortKey === state.sort) {
            indicator.textContent = state.direction === 'asc' ? '▲' : '▼';
        } else {
            indicator.textContent = '';
        }
    });
}

function renderPaginationControls(tableId, meta) {
    const state = getTableState(tableId);
    state.page = meta.current_page;
    state.perPage = meta.per_page;

    const perPageSelect = document.getElementById(`${tableId}-per-page`);
    if (perPageSelect) {
        perPageSelect.value = String(meta.per_page);
    }

    const summary = document.getElementById(`${tableId}-summary`);
    if (summary) {
        summary.textContent = meta.total > 0
            ? `{{ __('Showing') }} ${meta.from}-${meta.to} {{ __('of') }} ${meta.total}`
            : '';
    }

    const container = document.getElementById(`${tableId}-pagination`);
    if (!container) {
        return;
    }
    container.innerHTML = '';

    const makeButton = (label, page, disabled, active = false) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = label;
        btn.disabled = disabled;
        btn.className = 'px-3 py-1 text-sm rounded-md border ' +
            (active
                ? 'bg-blue-600 border-blue-600 text-white'
                : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600') +
            (disabled ? ' opacity-50 cursor-not-allowed' : '');
        if (!disabled) {
            btn.addEventListener('click', () => {
                getTableState(tableId).page = page;
                const functionName = 'load' + tableId.charAt(0).toUpperCase() + tableId.slice(1);
                if (typeof window[functionName] === 'function') {
                    window[functionName]();
                }
            });
        }
        return btn;
    };

    container.appendChild(makeButton('{{ __('Prev') }}', meta.current_page - 1, meta.current_page <= 1));

    for (let page = 1; page <= meta.last_page; page++) {
        container.appendChild(makeButton(String(page), page, false, page === meta.current_page));
    }

    container.appendChild(makeButton('{{ __('Next') }}', meta.current_page + 1, meta.current_page >= meta.last_page));
}

// Generic delete function
window.deleteItem = function(url) {
    showConfirmModal(url);
};

// Initialize table on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Wire up sortable headers and per-page selects
    document.querySelectorAll('[data-sort-table]').forEach(th => {
        th.addEventListener('click', () => {
            const tableId = th.dataset.sortTable;
            const key = th.dataset.sortKey;
            const state = getTableState(tableId);

            if (state.sort === key) {
                state.direction = state.direction === 'asc' ? 'desc' : 'asc';
            } else {
                state.sort = key;
                state.direction = 'asc';
            }
            state.page = 1;

            const functionName = 'load' + tableId.charAt(0).toUpperCase() + tableId.slice(1);
            if (typeof window[functionName] === 'function') {
                window[functionName]();
            }
        });
    });

    document.querySelectorAll('[data-perpage-table]').forEach(select => {
        select.addEventListener('change', () => {
            const tableId = select.dataset.perpageTable;
            const state = getTableState(tableId);
            state.perPage = parseInt(select.value, 10) || 10;
            state.page = 1;

            const functionName = 'load' + tableId.charAt(0).toUpperCase() + tableId.slice(1);
            if (typeof window[functionName] === 'function') {
                window[functionName]();
            }
        });
    });

    // Find all tables with data-list-url and load their data
    const tables = document.querySelectorAll('[data-list-url]');
    tables.forEach(table => {
        const tableId = table.id;
        const listUrl = table.dataset.listUrl;

        if (tableId && listUrl) {
            // Call page-specific function if it exists
            const functionName = 'load' + tableId.charAt(0).toUpperCase() + tableId.slice(1);
            if (typeof window[functionName] === 'function') {
                window[functionName]();
            } else {
                console.warn('[DataTable] Function not found:', functionName);
            }
        }
    });
});
</script>
@endpush
