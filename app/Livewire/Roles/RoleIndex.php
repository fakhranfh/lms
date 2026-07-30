<?php

namespace App\Livewire\Roles;

use App\Enums\RoleName;
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
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);

        return view('livewire.roles.role-index', [
            'roles' => $roleService->get(['school_id' => auth()->user()->school_id], ['permissions']),
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Roles'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
