<?php

namespace App\Livewire;

use App\Enums\RoleName;
use App\Services\R2StorageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Dashboard extends Component
{
    public array $storageQuota = [];

    public bool $isStudent = false;

    /**
     * Dashboard sections are loaded lazily via wire:init so the initial
     * page render is a cheap skeleton instead of blocking on the queries.
     */
    public bool $studentDataLoaded = false;

    public function mount(): void
    {
        $user = Auth::user();
        $school = $user?->school();

        $this->isStudent = (bool) $user?->hasRole(RoleName::Student);

        if ($school && $user->hasRole(['Admin', 'School Admin', 'Teacher'])) {
            $this->storageQuota = Cache::remember(
                "school-storage-quota:{$school->id}",
                now()->addMinutes(5),
                fn () => app(R2StorageService::class)->checkSchoolQuota($school->id)
            );
        }
    }

    public function loadStudentData(): void
    {
        $this->studentDataLoaded = true;
    }

    public function render()
    {
        return view('livewire.dashboard')
            ->extends('layouts.app', ['topbarTitle' => 'Dashboard'])
            ->section('app-content');
    }
}
