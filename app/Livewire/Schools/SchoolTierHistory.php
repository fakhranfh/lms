<?php

namespace App\Livewire\Schools;

use App\Models\School;
use App\Models\TierChange;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SchoolTierHistory extends Component
{
    public string $schoolId;

    public function mount(string $schoolId): void
    {
        $this->schoolId = $schoolId;

        abort_unless(auth()->user()->can('manage_schools') || auth()->user()->hasRole('admin'), 403);

        if (! School::find($schoolId)) {
            abort(404, 'School not found');
        }
    }

    #[Computed]
    public function school(): School
    {
        return School::findOrFail($this->schoolId);
    }

    #[Computed]
    public function tierChanges(): Collection
    {
        return TierChange::whereHas('schoolTier', fn ($q) => $q->where('school_id', $this->schoolId))
            ->with(['fromTier', 'toTier'])
            ->orderByDesc('changed_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.schools.school-tier-history')
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
