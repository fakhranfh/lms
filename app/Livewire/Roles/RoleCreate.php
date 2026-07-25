<?php

namespace App\Livewire\Roles;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Services\PermissionService;
use App\Services\RoleService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class RoleCreate extends Component
{
    public string $name = '';

    /** @var array<int, int> */
    public array $permissions = [];

    public string $permissionsJson = '[]';

    protected function rules(): array
    {
        return (new StoreRoleRequest)->rules();
    }

    public function updated(string $property): void
    {
        if ($property === 'permissionsJson') {
            $this->permissions = json_decode($this->permissionsJson, true) ?? [];
        }
    }

    public function store(RoleService $roleService): mixed
    {
        abort_unless(auth()->user()->can('roles.create'), 403);

        try {
            $validated = $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('show-error-modal', message: $e->validator->errors()->first());
            throw $e;
        }
        $validated['school_id'] = auth()->user()->school_id;

        $roleService->create($validated);

        session()->flash('success', __('Role created successfully.'));

        return redirect()->route('roles.index');
    }

    public function render(PermissionService $permissionService)
    {
        return view('livewire.roles.role-create', [
            'groupedPermissions' => $permissionService->getAllGrouped(),
        ])
            ->extends('layouts.admin', ['topbarTitle' => 'New Role'])
            ->section('admin-content');
    }
}
