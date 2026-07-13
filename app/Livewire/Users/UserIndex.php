<?php

namespace App\Livewire\Users;

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
        return view('livewire.users.user-index', [
            'users' => $userService->getAllWithRoles(),
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Users'])
            ->section('app-content');
    }
}
