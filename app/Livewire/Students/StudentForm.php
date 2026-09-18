<?php

namespace App\Livewire\Students;

use App\Enums\RoleName;
use App\Models\User;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Services\UserLoginLinkService;
use App\Services\UserService;
use App\Support\CurrentSchool;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class StudentForm extends Component
{
    use WithFileUploads;

    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public $photo = null;

    public ?string $photoPath = null;

    public int $loginLinkTtlDays = 2;

    public ?string $loginUrl = null;

    public function mount(UserService $userService, ?string $id = null): void
    {
        if ($id) {
            $this->user = $userService->find($id);
            abort_if($this->user === null || ! $this->user->hasRole(RoleName::Student), 404);

            $this->name = $this->user->name;
            $this->email = $this->user->email;
            $this->photoPath = $this->user->profile_photo_path;
        }

        $this->loginLinkTtlDays = (int) ceil(config('students.login_link_ttl_minutes', 2880) / 1440);
    }

    public function isEditing(): bool
    {
        return $this->user !== null;
    }

    public function savedPhotoSrc(): string
    {
        if ($this->photoPath) {
            return $this->photoPath;
        }

        $initial = strtoupper(substr($this->name ?: 'S', 0, 1));

        return 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Crect fill=%22%231E3A8A%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%22 y=%2250%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22white%22 font-size=%2250%22 font-weight=%22bold%22%3E'.$initial.'%3C/text%3E%3C/svg%3E';
    }

    private function currentSchoolId(): ?string
    {
        return app(CurrentSchool::class)->getSchoolId() ?? auth()->user()->school_id;
    }

    public function cancelPhoto(): void
    {
        $this->photo = null;
        $this->resetErrorBag('photo');
    }

    public function canAutofill(): bool
    {
        return app()->environment(['local', 'testing']) && ! $this->isEditing();
    }

    public function autofill(): void
    {
        abort_unless($this->canAutofill(), 403);

        $password = Str::password(12);

        $this->name = fake()->name();
        $this->email = Str::uuid().'@'.fake()->safeEmailDomain();
        $this->password = $password;
        $this->password_confirmation = $password;

        $this->dispatch('student-autofilled', name: $this->name, email: $this->email, password: $password);
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
            'loginLinkTtlDays' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    private function studentRoleId(string $schoolId): ?int
    {
        return app(RoleRepositoryInterface::class)
            ->get(['school_id' => $schoolId, 'name' => RoleName::Student->value])
            ->first()?->id;
    }

    public function regenerateLoginLink(UserLoginLinkService $userLoginLinkService): void
    {
        abort_unless(auth()->user()->can('students.edit'), 403);
        abort_unless($this->isEditing(), 404);

        $this->validate(['loginLinkTtlDays' => $this->rules()['loginLinkTtlDays']]);

        $link = $userLoginLinkService->createLink($this->user, $this->loginLinkTtlDays * 1440);
        $this->loginUrl = $userLoginLinkService->buildLoginUrl($link);
    }

    public function save(UserService $userService, UserLoginLinkService $userLoginLinkService): mixed
    {
        abort_unless(auth()->user()->can($this->isEditing() ? 'students.edit' : 'students.create'), 403);

        try {
            $validated = $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('show-error-modal', message: $e->validator->errors()->first());
            throw $e;
        }

        $schoolId = $this->currentSchoolId();
        $roleId = $schoolId ? $this->studentRoleId($schoolId) : null;
        $roleIds = $roleId ? [$roleId] : [];

        if ($this->isEditing()) {
            $data = ['name' => $validated['name'], 'email' => $validated['email']];

            if (! empty($validated['password'])) {
                $userService->changePassword($this->user, $validated['password']);
            }

            $userService->updateProfile($this->user, $data);

            if ($this->photo) {
                $userService->updateProfilePhoto($this->user, $this->photo);
            }

            $userService->syncRoles($this->user, $roleIds);

            session()->flash('success', __('Student updated successfully.'));

            return redirect()->route('students.index');
        }

        $trashedUser = $schoolId ? $userService->findTrashedInSchool($validated['email'], $schoolId) : null;

        try {
            if ($trashedUser) {
                $student = $userService->restoreUser($trashedUser, $validated, $this->photo, $roleIds);
            } else {
                $student = $userService->createUser([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'school_id' => $schoolId,
                ], $this->photo, $roleIds);
            }
        } catch (UniqueConstraintViolationException) {
            // The availability check raced with another request creating
            // the same email/name between validation and this insert.
            $this->addError('email', __('This email is already in use.'));
            $this->dispatch('show-error-modal', message: __('This email is already in use.'));

            return null;
        }

        $userService->updateProfile($student, ['must_change_password' => true]);
        $link = $userLoginLinkService->createLink($student, $validated['loginLinkTtlDays'] * 1440);
        $this->loginUrl = $userLoginLinkService->buildLoginUrl($link);

        // Stay on the form (rather than redirecting to the index) so the
        // one-time login link can be shown to the admin/teacher to copy.
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'photo']);

        return null;
    }

    public function render()
    {
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);

        return view('livewire.students.student-form')
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => $this->isEditing() ? 'Edit Student' : 'New Student'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
