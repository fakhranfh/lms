<?php

namespace App\View\Components\Users;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class Table extends Component
{
    public bool $hasSelectionPersistence;

    public ?string $selectionShowUrl;

    public ?string $selectionUpdateUrl;

    public ?string $selectionUpdateManyUrl;

    public ?string $selectionClearUrl;

    public function __construct(
        public ?LengthAwarePaginator $items,
        public bool $loaded,
        public string $sort,
        public string $direction,
        public string $entity,
        public string $permissionPrefix,
    ) {
        $this->hasSelectionPersistence = Route::has("{$this->permissionPrefix}.selection.show");

        $this->selectionShowUrl = $this->hasSelectionPersistence ? route("{$this->permissionPrefix}.selection.show") : null;
        $this->selectionUpdateUrl = $this->hasSelectionPersistence ? route("{$this->permissionPrefix}.selection.update") : null;
        $this->selectionUpdateManyUrl = $this->hasSelectionPersistence ? route("{$this->permissionPrefix}.selection.update-many") : null;
        $this->selectionClearUrl = $this->hasSelectionPersistence ? route("{$this->permissionPrefix}.selection.clear") : null;
    }

    public function render(): View|Closure|string
    {
        return view('components.users.table');
    }
}
