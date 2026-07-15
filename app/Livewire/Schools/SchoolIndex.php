<?php

namespace App\Livewire\Schools;

use App\Models\PricingTier;
use App\Services\SchoolService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SchoolIndex extends Component
{
    public ?string $search = null;

    public ?string $filterTier = null;

    public string $sort = 'name';

    public string $direction = 'asc';

    public int $perPage = 15;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('manage_schools') || auth()->user()->hasRole('admin'), 403);
    }

    public function updating(string $property): void
    {
        if ($property !== 'sort' && $property !== 'direction') {
            if (in_array($property, ['search', 'filterTier'])) {
                // Reset sort and direction when filters change
                $this->sort = 'name';
                $this->direction = 'asc';
            }
        }
    }

    #[On('sortChanged')]
    public function updateSort(string $sort, string $direction, int $perPage): void
    {
        $this->sort = $sort;
        $this->direction = $direction;
        $this->perPage = $perPage;
    }

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->direction = 'asc';
        }
    }

    #[Computed]
    public function schools(SchoolService $schoolService): LengthAwarePaginator
    {
        return $schoolService->paginate(
            filters: [
                'search' => $this->search,
                'tier_id' => $this->filterTier,
                'sort' => $this->sort,
                'direction' => $this->direction,
            ],
            with: ['tier'],
            perPage: $this->perPage,
        );
    }

    #[Computed]
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
