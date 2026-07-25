<?php

namespace App\Livewire;

use App\Services\R2StorageService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public array $storageQuota = [];

    public function mount(): void
    {
        $user = Auth::user();
        $school = $user?->school;

        if ($school && $user->hasRole(['Admin', 'School Admin', 'Instructor'])) {
            $this->storageQuota = app(R2StorageService::class)->checkSchoolQuota($school->id);
        }
    }

    public function render()
    {
        return view('livewire.dashboard')
            ->extends('layouts.app', ['topbarTitle' => 'Dashboard'])
            ->section('app-content');
    }
}
