<?php

namespace App\Livewire\Admin;

use App\Services\R2StorageService;
use App\Services\StorageMonitoringService;
use Livewire\Component;

class AdminStorageDashboard extends Component
{
    public string $sortBy = 'used_bytes';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('analytics.view'), 403);
    }

    public function sortByColumn(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    public function render(StorageMonitoringService $monitoringService)
    {
        return view('livewire.admin.admin-storage-dashboard', [
            'globalSummary' => $monitoringService->globalSummary(),
            'schoolBreakdown' => $monitoringService->perSchoolBreakdown($this->sortBy, $this->sortDirection),
            'usageTrend' => $monitoringService->usageTrend(30),
            'formatBytes' => fn (int $bytes) => R2StorageService::formatBytes($bytes),
        ])
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
