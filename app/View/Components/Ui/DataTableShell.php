<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Thin shared chrome for tables too custom for x-ui.livewire-data-table
 * (drag-reorder, per-cell radio groups/modals, an ancestor-scoped Alpine
 * selection). Provides only the surface container, the generic skeleton,
 * and the wire:loading swap — the caller owns the full <thead> (via the
 * `head` slot) and <tbody> (via the default slot, including any
 * attributes like x-ref) uncontested.
 */
class DataTableShell extends Component
{
    public string $containerClass;

    public function __construct(
        public int $columnCount = 5,
        public ?string $loadingTarget = null,
        public bool $bare = false,
    ) {
        $this->containerClass = $bare ? '' : 'bg-surface border border-outline-variant rounded-lg overflow-hidden';
    }

    public function render(): View|Closure|string
    {
        return view('components.ui.data-table-shell');
    }
}
