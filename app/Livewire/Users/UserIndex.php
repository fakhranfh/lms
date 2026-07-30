<?php

namespace App\Livewire\Users;

use App\Enums\RoleName;
use App\Services\UserService;
use Livewire\Component;

class UserIndex extends Component
{
    public ?string $successMessage = null;

    public function mount(): void
    {
        $this->successMessage = session('success');
    }

    public function render(UserService $userService)
    {
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);

        return view('livewire.users.user-index', [
            'users' => $userService->getAllWithRoles(),
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Users'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
