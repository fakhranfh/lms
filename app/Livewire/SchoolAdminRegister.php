<?php

namespace App\Livewire;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SchoolAdminRegister extends Component
{
    use PasswordValidationRules;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
        ];
    }

    public function save(): void
    {
        $this->validate();

        $user = User::create([
            'school_id' => null,
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        Auth::login($user);

        $this->redirectRoute('get-started.school');
    }

    public function render()
    {
        return view('livewire.school-admin-register')
            ->extends('master', ['body_class' => 'bg-background text-on-background min-h-screen flex flex-col font-body-md'])
            ->section('content');
    }
}
