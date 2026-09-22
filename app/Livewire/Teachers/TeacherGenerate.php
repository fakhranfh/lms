<?php

namespace App\Livewire\Teachers;

use App\Enums\RoleName;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Services\UserLoginLinkService;
use App\Services\UserService;
use Illuminate\Support\Str;
use Livewire\Component;

class TeacherGenerate extends Component
{
    public bool $embedded = false;

    public int $count = 5;

    public int $loginLinkTtlDays = 2;

    public function mount(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);

        $this->loginLinkTtlDays = (int) ceil(config('teachers.login_link_ttl_minutes', 2880) / 1440);
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
        abort_unless(auth()->user()->can('teachers.create'), 403);

        $this->validate();

        $schoolId = $this->currentSchoolId();
        $roleId = $schoolId ? app(RoleRepositoryInterface::class)
            ->get(['school_id' => $schoolId, 'name' => RoleName::Teacher->value])
            ->first()?->id : null;
        $roleIds = $roleId ? [$roleId] : [];

        for ($i = 0; $i < $this->count; $i++) {
            $name = fake()->name();
            $email = Str::uuid().'@'.fake()->safeEmailDomain();

            $teacher = $userService->createUser([
                'name' => $name,
                'email' => $email,
                'password' => Str::random(24),
                'school_id' => $schoolId,
                'must_change_password' => true,
            ], null, $roleIds);

            $userLoginLinkService->createLink($teacher, $this->loginLinkTtlDays * 1440);
        }

        $this->dispatch('teachers-generated', count: $this->count);
    }

    public function render()
    {
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);

        $view = view('livewire.teachers.teacher-generate');

        if ($this->embedded) {
            return $view;
        }

        return $view
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Generate Teachers'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
