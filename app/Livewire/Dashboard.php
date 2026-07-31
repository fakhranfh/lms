<?php

namespace App\Livewire;

use App\Services\R2StorageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Dashboard extends Component
{
    public array $storageQuota = [];

    public function mount(): void
    {
        $user = Auth::user();
        $school = $user?->school();

        if ($school && $user->hasRole(['Admin', 'School Admin', 'Teacher'])) {
            $this->storageQuota = Cache::remember(
                "school-storage-quota:{$school->id}",
                now()->addMinutes(5),
                fn () => app(R2StorageService::class)->checkSchoolQuota($school->id)
            );
        }
    }

    public function render()
    {
        return view('livewire.dashboard')
            ->extends('layouts.app', ['topbarTitle' => 'Dashboard'])
            ->section('app-content');
    }
}
