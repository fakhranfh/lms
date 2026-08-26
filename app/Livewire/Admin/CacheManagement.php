<?php

namespace App\Livewire\Admin;

use App\Services\CacheManagementService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CacheManagement extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public ?string $selectedKey = null;

    public ?string $selectedType = null;

    public ?int $selectedTtl = null;

    public string $editValue = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->successMessage = session('success');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function selectKey(string $key, CacheManagementService $cacheManagementService): void
    {
        $this->selectedKey = $key;
        $this->errorMessage = null;
        $this->successMessage = null;

        if (! $cacheManagementService->exists($key)) {
            $this->errorMessage = __('Key no longer exists.');
            $this->selectedKey = null;

            return;
        }

        $this->selectedType = $cacheManagementService->typeOf($key);
        $this->selectedTtl = $cacheManagementService->ttlOf($key);
        $this->editValue = $cacheManagementService->readValue($key, $this->selectedType);
    }

    public function closeKey(): void
    {
        $this->selectedKey = null;
        $this->selectedType = null;
        $this->selectedTtl = null;
        $this->editValue = '';
    }

    public function save(CacheManagementService $cacheManagementService): void
    {
        if (! $this->selectedKey || $this->selectedType !== 'string') {
            $this->errorMessage = __('Only string values can be edited.');

            return;
        }

        $cacheManagementService->updateStringValue($this->selectedKey, $this->editValue);

        $this->successMessage = __('Key updated successfully.');
    }

    public function deleteKey(string $key, CacheManagementService $cacheManagementService): void
    {
        $cacheManagementService->deleteKey($key);

        if ($this->selectedKey === $key) {
            $this->closeKey();
        }

        $this->successMessage = __('Key deleted successfully.');
    }

    public function flushDatabase(CacheManagementService $cacheManagementService): void
    {
        $cacheManagementService->flushDatabase();
        $this->closeKey();
        $this->resetPage();

        $this->successMessage = __('Cache database flushed successfully.');
    }

    public function render(CacheManagementService $cacheManagementService)
    {
        $keys = $cacheManagementService->matchingKeys($this->search);
        $page = $this->getPage();
        $perPage = 25;

        $paginator = new LengthAwarePaginator(
            array_slice($keys, ($page - 1) * $perPage, $perPage),
            count($keys),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );

        return view('livewire.admin.cache-management', [
            'keys' => $paginator,
            'info' => $cacheManagementService->serverInfo(),
        ])
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
