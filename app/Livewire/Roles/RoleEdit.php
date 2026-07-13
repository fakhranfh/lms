<?php

namespace App\Livewire\Roles;

use App\Services\PermissionService;
use App\Services\RoleService;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class RoleEdit extends Component
{
    public Role $role;

    public string $name = '';

    /** @var array<int, int> */
    public array $permissions = [];

    public string $permissionsJson = '[]';

    public function mount(Role $role): void
    {
        $this->role = $role;
        $this->name = $role->name;
        $this->permissions = $role->permissions->pluck('id')->all();
        $this->permissionsJson = json_encode($this->permissions);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$this->role->id],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    public function updated(string $property): void
    {
        if ($property === 'permissionsJson') {
            $this->permissions = json_decode($this->permissionsJson, true) ?? [];
        }
    }

    public function update(RoleService $roleService): mixed
    {
        abort_unless(auth()->user()->can('roles.update'), 403);

        $validated = $this->validate();

        if ($this->role->name === 'admin') {
            $validated['name'] = $this->role->name;
        }

        $roleService->update($this->role->id, $validated);

        session()->flash('success', __('Role updated successfully.'));

        return redirect()->route('roles.index');
    }

    public function render(PermissionService $permissionService)
    {
        return view('livewire.roles.role-edit', [
            'groupedPermissions' => $permissionService->getAllGrouped(),
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Edit Role'])
            ->section('app-content');
    }
}
