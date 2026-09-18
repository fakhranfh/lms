<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LivewireDataTable extends Component
{
    public int $columnCount;

    /**
     * @param  array<int, array{key: string, label: string, sortable?: bool}>  $columns
     * @param  array<int, int>  $perPageOptions
     */
    public function __construct(
        public array $columns = [],
        public ?LengthAwarePaginator $items = null,
        public ?string $sort = null,
        public string $direction = 'asc',
        public ?int $perPage = null,
        public array $perPageOptions = [10, 15, 25, 50],
        public string $loadingTarget = 'search,applyFilters,resetFilters,sortBy,perPage',
        public bool $selectable = false,
    ) {
        $this->columnCount = count($columns) + 1 + ($selectable ? 1 : 0);
    }

    public function render(): View|Closure|string
    {
        return view('components.ui.livewire-data-table');
    }
}
