<?php

namespace App\Livewire\Students;

use App\Services\UserService;
use Livewire\Component;

class ForcePasswordChange extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function save(UserService $userService): mixed
    {
        $validated = $this->validate();

        $user = auth()->user();

        $userService->changePassword($user, $validated['password']);
        $userService->updateProfile($user, ['must_change_password' => false]);

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.students.force-password-change');
    }
}
