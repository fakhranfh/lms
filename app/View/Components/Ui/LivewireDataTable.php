<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class LivewireDataTable extends Component
{
    public int $columnCount;

    public bool $isPaginated;

    /**
     * @param  array<int, array{key: string, label: string, sortable?: bool}>  $columns
     * @param  LengthAwarePaginator<int, mixed>|Collection<int, mixed>|null  $items
     * @param  array<int, int>  $perPageOptions
     */
    public function __construct(
        public array $columns = [],
        public LengthAwarePaginator|Collection|null $items = null,
        public ?string $sort = null,
        public string $direction = 'asc',
        public ?int $perPage = null,
        public array $perPageOptions = [10, 15, 25, 50],
        public string $loadingTarget = 'search,applyFilters,resetFilters,sortBy,perPage',
        public bool $selectable = false,
        public bool $showPagination = true,
    ) {
        $this->columnCount = count($columns) + 1 + ($selectable ? 1 : 0);
        $this->isPaginated = $items instanceof LengthAwarePaginator;
    }

    public function render(): View|Closure|string
    {
        return view('components.ui.livewire-data-table');
    }
}
