<?php

namespace App\Livewire\Students;

use App\Enums\RoleName;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Services\UserLoginLinkService;
use App\Services\UserService;
use Illuminate\Support\Str;
use Livewire\Component;

class StudentGenerate extends Component
{
    public bool $embedded = false;

    public int $count = 5;

    public int $loginLinkTtlDays = 2;

    public function mount(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);

        $this->loginLinkTtlDays = (int) ceil(config('students.login_link_ttl_minutes', 2880) / 1440);
    }

    protected function rules(): array
    {
        return [
            'count' => ['required', 'integer', 'min:1', 'max:200'],
            'loginLinkTtlDays' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    private function currentSchoolId(): ?string
    {
        return auth()->user()->school_id;
    }

    public function generate(UserService $userService, UserLoginLinkService $userLoginLinkService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('students.create'), 403);

        $this->validate();

        $schoolId = $this->currentSchoolId();
        $roleId = $schoolId ? app(RoleRepositoryInterface::class)
            ->get(['school_id' => $schoolId, 'name' => RoleName::Student->value])
            ->first()?->id : null;
        $roleIds = $roleId ? [$roleId] : [];

        for ($i = 0; $i < $this->count; $i++) {
            $name = fake()->name();
            $email = Str::uuid().'@'.fake()->safeEmailDomain();

            $student = $userService->createUser([
                'name' => $name,
                'email' => $email,
                'password' => Str::random(24),
                'school_id' => $schoolId,
                'must_change_password' => true,
            ], null, $roleIds);

            $userLoginLinkService->createLink($student, $this->loginLinkTtlDays * 1440);
        }

        $this->dispatch('students-generated', count: $this->count);
    }

    public function render()
    {
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);

        $view = view('livewire.students.student-generate');

        if ($this->embedded) {
            return $view;
        }

        return $view
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Generate Students'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
