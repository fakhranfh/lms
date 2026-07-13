<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class UserRoles extends Component
{
    public User $user;

    /** @var array<int, int> */
    public array $roles = [];

    public function mount(int $id, UserService $userService): void
    {
        $this->user = $userService->find($id);
        $this->roles = $this->user->roles->pluck('id')->all();
    }

    protected function rules(): array
    {
        return [
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ];
    }

    public function updateRoles(UserService $userService): mixed
    {
        abort_unless(auth()->user()->can('users.assign-roles'), 403);

        $validated = $this->validate();

        try {
            $userService->syncRoles($this->user, $validated['roles'] ?? []);
        } catch (ValidationException $exception) {
            $this->addError('roles', collect($exception->errors())->flatten()->first());

            return null;
        }

        session()->flash('success', __('Roles updated successfully.'));

        return redirect()->route('users.index');
    }

    public function render(RoleService $roleService)
    {
        return view('livewire.users.user-roles', [
            'allRoles' => $roleService->getAll(),
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Assign Roles'])
            ->section('app-content');
    }
}
