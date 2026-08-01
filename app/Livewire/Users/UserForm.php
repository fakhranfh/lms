<?php

namespace App\Livewire\Users;

use App\Enums\RoleName;
use App\Models\User;
use App\Services\RoleService;
use App\Services\UserService;
use App\Support\CurrentSchool;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class UserForm extends Component
{
    use WithFileUploads;

    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public $photo = null;

    public ?string $photoPath = null;

    /** @var array<int, int> */
    public array $roles = [];

    public function mount(UserService $userService, ?string $id = null): void
    {
        if ($id) {
            $this->user = $userService->find($id);
            abort_if($this->user === null, 404);

            $this->name = $this->user->name;
            $this->email = $this->user->email;
            $this->photoPath = $this->user->profile_photo_path;
            $this->roles = $this->user->roles->pluck('id')->all();
        }
    }

    public function isEditing(): bool
    {
        return $this->user !== null;
    }

    /**
     * The school this admin is currently operating in. Prefers the
     * request-resolved CurrentSchool over auth()->user()->school_id, since
     * that accessor only reflects school_user membership — School Admins
     * are attached via a separate school_admins pivot and would otherwise
     * resolve to null.
     */
    private function currentSchoolId(): ?string
    {
        return app(CurrentSchool::class)->getSchoolId() ?? auth()->user()->school_id;
    }

    public function cancelPhoto(): void
    {
        $this->photo = null;
        $this->resetErrorBag('photo');
    }

    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('users', 'name')->ignore($this->user?->id)->whereNull('deleted_at'),
            ],
            'email' => [
                'required', 'email', 'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $message = app(UserService::class)->emailConflictMessage($value, $this->currentSchoolId(), $this->user?->id);

                    if ($message !== null) {
                        $fail($message);
                    }
                },
            ],
            'password' => array_filter([
                $this->isEditing() ? 'nullable' : 'required', 'string', 'min:8',
                ($this->password !== '' || ! $this->isEditing()) ? 'confirmed' : null,
            ]),
            'photo' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,gif'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ];
    }

    public function save(UserService $userService): mixed
    {
        abort_unless(auth()->user()->can($this->isEditing() ? 'users.edit' : 'users.create'), 403);

        try {
            $validated = $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('show-error-modal', message: $e->validator->errors()->first());
            throw $e;
        }

        if ($this->isEditing()) {
            $data = ['name' => $validated['name'], 'email' => $validated['email']];

            if (! empty($validated['password'])) {
                $userService->changePassword($this->user, $validated['password']);
            }

            $userService->updateProfile($this->user, $data);

            if ($this->photo) {
                $userService->updateProfilePhoto($this->user, $this->photo);
            }

            $userService->syncRoles($this->user, $validated['roles'] ?? []);

            session()->flash('success', __('User updated successfully.'));
        } else {
            try {
                $userService->createUser([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'school_id' => $this->currentSchoolId(),
                ], $this->photo, $validated['roles'] ?? []);
            } catch (UniqueConstraintViolationException) {
                // The availability check raced with another request creating
                // the same email/name between validation and this insert.
                $this->addError('email', __('This email is already in use.'));
                $this->dispatch('show-error-modal', message: __('This email is already in use.'));

                return null;
            }

            session()->flash('success', __('User created successfully.'));
        }

        return redirect()->route('users.index');
    }

    public function render(RoleService $roleService)
    {
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);

        return view('livewire.users.user-form', [
            'allRoles' => $roleService->get(['school_id' => $this->currentSchoolId()]),
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => $this->isEditing() ? 'Edit User' : 'New User'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
