<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PaginationLinks extends Component
{
    public string $pageName;

    public string $iconButtonClasses;

    public int $windowStart;

    public int $windowEnd;

    /**
     * @param  array<int, int>  $perPageOptions
     */
    public function __construct(
        public LengthAwarePaginator $paginator,
        public ?string $perPageModel = null,
        public array $perPageOptions = [6, 12, 24, 48],
        public ?string $searchModel = null,
        public string $searchPlaceholder = 'Search...',
        public ?string $search = null,
    ) {
        $this->pageName = method_exists($paginator, 'getPageName') ? $paginator->getPageName() : 'page';
        $this->iconButtonClasses = 'inline-flex items-center justify-center w-9 h-9 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container-lowest focus:outline-none focus:ring ring-primary/30 transition disabled:opacity-40 disabled:cursor-not-allowed';
        $this->windowStart = max($paginator->currentPage() - 2, 1);
        $this->windowEnd = min($paginator->currentPage() + 2, $paginator->lastPage());
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.pagination-links');
    }
}
