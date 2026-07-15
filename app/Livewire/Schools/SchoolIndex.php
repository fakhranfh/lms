<?php

namespace App\Livewire\Schools;

use App\Models\PricingTier;
use App\Models\School;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class SchoolIndex extends Component
{
    use WithPagination;

    public ?string $search = null;

    public ?string $filterTier = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('manage_schools') || auth()->user()->hasRole('admin'), 403);
    }

    #[Computed]
    public function schools(): LengthAwarePaginator
    {
        $query = School::with('tier');

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('domain', 'like', "%{$this->search}%");
        }

        if ($this->filterTier) {
            $query->where('tier_id', $this->filterTier);
        }

        return $query->paginate(15);
    }

    #[Computed(cache: true)]
    public function availableTiers(): Collection
    {
        return PricingTier::where('is_active', true)->get();
    }

    public function render()
    {
        return view('livewire.schools.school-index', [
            'schools' => $this->schools,
        ])
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
