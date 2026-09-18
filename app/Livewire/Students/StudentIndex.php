<?php

namespace App\Livewire\Students;

use App\Enums\RoleName;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Services\UserLoginLinkService;
use App\Services\UserService;
use App\Support\CurrentSchool;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read LengthAwarePaginator $students
 */
class StudentIndex extends Component
{
    use WithPagination;

    public ?string $search = null;

    public string $sort = 'name';

    public string $direction = 'asc';

    public int $perPage = 15;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public ?string $regeneratedLoginUrl = null;

    /**
     * Whether the students table has been loaded yet. Kept false through
     * the initial render (triggered via wire:init) so the page paints
     * instantly with a skeleton in place of the table.
     */
    public bool $studentsLoaded = false;

    /**
     * Whether the bulk-delete selection has been expanded to every student
     * matching the current filters, not just those checked on this page.
     */
    public bool $selectAllMatching = false;

    /**
     * Total number of students matching the current filters, kept in sync
     * on every render so the "select all matching" prompt can compare it
     * against the current page's selection count.
     */
    public int $matchingCount = 0;

    /**
     * IDs of the students on the currently rendered page, kept in sync on
     * every render via @entangle so the "select all on page" checkbox
     * reactively recomputes after pagination — reading the DOM directly
     * for this doesn't trigger Alpine's reactivity when Livewire morphs in
     * a new page of rows without any of the entangled properties changing.
     *
     * @var array<int, string>
     */
    public array $pageIds = [];

    public function mount(): void
    {
        $this->successMessage = session('success');
    }

    public function loadUsers(): void
    {
        $this->studentsLoaded = true;
    }

    #[On('students-generated')]
    public function handleStudentsGenerated(int $count): void
    {
        $this->successMessage = trans_choice('1 student generated successfully.|:count students generated successfully.', $count, ['count' => $count]);
        $this->errorMessage = null;

        unset($this->students);
    }

    public function regenerateLoginLink(string $id, UserService $userService, UserLoginLinkService $userLoginLinkService): void
    {
        abort_unless(auth()->user()->can('students.edit'), 403);

        $student = $userService->find($id);
        abort_if($student === null, 404);

        $link = $userLoginLinkService->createLink($student, config('students.login_link_ttl_minutes', 2880));
        $this->regeneratedLoginUrl = $userLoginLinkService->buildLoginUrl($link);
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

        $this->deleteMany($ids, $userService);

        $this->selectAllMatching = false;

        unset($this->students);
    }

    /**
     * Deletes every student matching the current filters, not just those
     * on the currently visible page — backs the "select all matching"
     * bulk-delete option.
     */
    public function destroyAllMatching(UserService $userService): void
    {
        abort_unless(auth()->user()->can('students.delete'), 403);

        $this->deleteMany($userService->idsMatching($this->matchingFilters()), $userService);

        $this->selectAllMatching = false;

        unset($this->students);
    }

    /**
     * IDs of every student matching the current filters, not just those on
     * the currently visible page — called from the client when a single
     * checkbox is unchecked while "select all matching" is active, so the
     * selection can fall back to "all of these except that one" instead of
     * losing the rest of the selection entirely.
     *
     * @return array<int, string>
     */
    public function matchingIds(UserService $userService): array
    {
        return $userService->idsMatching($this->matchingFilters());
    }

    /**
     * @return array<string, mixed>
     */
    private function matchingFilters(): array
    {
        return [
            'search' => $this->search,
            'role_id' => $this->studentRoleId(),
        ];
    }

    /**
     * @param  array<int, string>  $ids
     */
    private function deleteMany(array $ids, UserService $userService): void
    {
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
    }

    public function updating(string $property): void
    {
        if ($property === 'search') {
            $this->sort = 'name';
            $this->direction = 'asc';
            $this->selectAllMatching = false;
            $this->resetPage();
        }

        if ($property === 'perPage') {
            $this->resetPage();
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

        $students = $this->studentsLoaded ? $this->students : null;
        $this->matchingCount = $students?->total() ?? 0;
        $this->pageIds = $students?->pluck('id')->values()->all() ?? [];

        return view('livewire.students.student-index', [
            'students' => $students,
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Students'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
