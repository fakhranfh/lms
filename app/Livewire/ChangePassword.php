<?php

namespace App\Livewire;

use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ChangePassword extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $updated = false;

    #[Computed]
    public function passwordStrength(): int
    {
        $password = $this->password;
        $strength = 0;

        if (strlen($password) >= 8) {
            $strength++;
        }
        if (strlen($password) >= 12) {
            $strength++;
        }
        if (preg_match('/[a-z]/', $password)) {
            $strength++;
        }
        if (preg_match('/[A-Z]/', $password)) {
            $strength++;
        }
        if (preg_match('/[0-9]/', $password)) {
            $strength++;
        }
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $strength++;
        }

        return $strength;
    }

    public function updatePassword(UpdatesUserPasswords $updater): void
    {
        $this->updated = false;

        try {
            $updater->update(auth()->user(), [
                'current_password' => $this->current_password,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->updated = true;
    }

    public function render()
    {
        return view('livewire.change-password')
            ->extends('layouts.app', ['topbarTitle' => 'Change Password'])
            ->section('app-content');
    }
}
