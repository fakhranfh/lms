<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Shared responsive grid wrapper for small lists of entity rows (avatar +
 * name), e.g. group members or unassigned students. Not a `<table>` — for
 * tabular data with sorting/pagination, use x-ui.livewire-data-table.
 */
class EntityGrid extends Component
{
    public string $gridClasses;

    public function __construct(public int $cols = 2)
    {
        $this->gridClasses = 'grid grid-cols-1 sm:grid-cols-2 gap-space-md'.($cols >= 3 ? ' md:grid-cols-3' : '');
    }

    public function render(): View|Closure|string
    {
        return view('components.ui.entity-grid');
    }
}
