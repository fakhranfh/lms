<?php

namespace App\Livewire\Roles;

use App\Enums\RoleName;
use App\Services\PermissionService;
use App\Services\RoleService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query->where('school_id', $this->role->school_id))
                    ->ignore($this->role->id),
            ],
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

        try {
            $validated = $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('show-error-modal', message: $e->validator->errors()->first());
            throw $e;
        }

        if (in_array($this->role->name, [RoleName::Admin->value, RoleName::SchoolAdmin->value], true)) {
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
            ->extends('layouts.admin', ['topbarTitle' => 'Edit Role'])
            ->section('admin-content');
    }
}
