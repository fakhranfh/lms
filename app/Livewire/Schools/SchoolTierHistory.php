<?php

namespace App\Livewire\Schools;

use App\Models\TierChange;
use App\Services\SchoolService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SchoolTierHistory extends Component
{
    public string $schoolId;

    public function mount(string $school, SchoolService $schoolService): void
    {
        $this->schoolId = $school;

        abort_unless(auth()->user()->can('manage_schools') || auth()->user()->hasRole('admin'), 403);

        if (! $schoolService->find($school)) {
            abort(404, 'School not found');
        }
    }

    #[Computed]
    public function school()
    {
        return resolve(SchoolService::class)->find($this->schoolId);
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
