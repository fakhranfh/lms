<?php

namespace App\Livewire\Roles;

use App\Services\RoleService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class RoleIndex extends Component
{
    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->successMessage = session('success');
    }

    public function destroy(int $id, RoleService $roleService): void
    {
        abort_unless(auth()->user()->can('roles.delete'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        try {
            $roleService->delete($id);
            $this->successMessage = __('Role deleted successfully.');
        } catch (ValidationException $exception) {
            $this->errorMessage = collect($exception->errors())->flatten()->first();
        }
    }

    public function render(RoleService $roleService)
    {
        return view('livewire.roles.role-index', [
            'roles' => $roleService->get([], ['permissions']),
        ])
            ->extends('layouts.admin', ['topbarTitle' => 'Roles'])
            ->section('admin-content');
    }
}
