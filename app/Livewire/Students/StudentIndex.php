<?php

namespace App\Livewire\Students;

use App\Enums\RoleName;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Services\UserService;
use App\Support\CurrentSchool;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read LengthAwarePaginator $students
 */
class StudentIndex extends Component
{
    public ?string $search = null;

    public string $sort = 'name';

    public string $direction = 'asc';

    public int $perPage = 15;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    /**
     * Whether the students table has been loaded yet. Kept false through
     * the initial render (triggered via wire:init) so the page paints
     * instantly with a skeleton in place of the table.
     */
    public bool $studentsLoaded = false;

    public function mount(): void
    {
        $this->successMessage = session('success');
    }

    public function loadUsers(): void
    {
        $this->studentsLoaded = true;
    }

    private function currentSchoolId(): ?string
    {
        return app(CurrentSchool::class)->getSchoolId() ?? auth()->user()->school_id;
    }

    public function destroy(string $id, UserService $userService): void
    {
        abort_unless(auth()->user()->can('students.delete'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        $user = $userService->find($id);
        abort_if($user === null, 404);

        try {
            $userService->deleteUser($user);
            $this->successMessage = __('Student deleted successfully.');
        } catch (ValidationException $exception) {
            $this->errorMessage = collect($exception->errors())->flatten()->first();
        }

        unset($this->students);
    }

    /**
     * @param  array<int, string>  $ids
     */
    public function destroySelected(array $ids, UserService $userService): void
    {
        abort_unless(auth()->user()->can('students.delete'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        $deletedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            $user = $userService->find($id);

            if ($user === null) {
                continue;
            }

            try {
                $userService->deleteUser($user);
                $deletedCount++;
            } catch (ValidationException $exception) {
                $errors[] = collect($exception->errors())->flatten()->first();
            }
        }

        if ($deletedCount > 0) {
            $this->successMessage = trans_choice('1 student deleted successfully.|:count students deleted successfully.', $deletedCount, ['count' => $deletedCount]);
        }

        if ($errors !== []) {
            $this->errorMessage = collect($errors)->unique()->join(' ');
        }

        unset($this->students);
    }

    public function updating(string $property): void
    {
        if ($property === 'search') {
            $this->sort = 'name';
            $this->direction = 'asc';
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->direction = 'asc';
        }
    }

    private function studentRoleId(): ?int
    {
        $schoolId = $this->currentSchoolId();

        if ($schoolId === null) {
            return null;
        }

        return resolve(RoleRepositoryInterface::class)
            ->get(['school_id' => $schoolId, 'name' => RoleName::Student->value])
            ->first()?->id;
    }

    #[Computed]
    public function students(): LengthAwarePaginator
    {
        return resolve(UserService::class)->paginate(
            filters: [
                'search' => $this->search,
                'role_id' => $this->studentRoleId(),
                'sort' => $this->sort,
                'direction' => $this->direction,
            ],
            with: ['roles'],
            perPage: $this->perPage,
        );
    }

    public function render()
    {
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);

        return view('livewire.students.student-index', [
            'students' => $this->studentsLoaded ? $this->students : null,
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Students'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
