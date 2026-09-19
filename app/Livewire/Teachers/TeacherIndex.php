<?php

namespace App\Livewire\Teachers;

use App\Enums\RoleName;
use App\Exports\TeachersExport;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Services\UserLoginLinkService;
use App\Services\UserService;
use App\Support\CurrentSchool;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property-read LengthAwarePaginator $teachers
 */
class TeacherIndex extends Component
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
     * Whether the teachers table has been loaded yet. Kept false through
     * the initial render (triggered via wire:init) so the page paints
     * instantly with a skeleton in place of the table.
     */
    public bool $teachersLoaded = false;

    /**
     * Whether the bulk-delete selection has been expanded to every teacher
     * matching the current filters, not just those checked on this page.
     */
    public bool $selectAllMatching = false;

    /**
     * Total number of teachers matching the current filters, kept in sync
     * on every render so the "select all matching" prompt can compare it
     * against the current page's selection count.
     */
    public int $matchingCount = 0;

    /**
     * IDs of the teachers on the currently rendered page, kept in sync on
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
        $this->errorMessage = session('error');
    }

    public function loadUsers(): void
    {
        $this->teachersLoaded = true;
    }

    #[On('teachers-generated')]
    public function handleTeachersGenerated(int $count): void
    {
        $this->successMessage = trans_choice('1 teacher generated successfully.|:count teachers generated successfully.', $count, ['count' => $count]);
        $this->errorMessage = null;

        unset($this->teachers);
    }

    public function regenerateLoginLink(string $id, UserService $userService, UserLoginLinkService $userLoginLinkService): void
    {
        abort_unless(auth()->user()->can('teachers.edit'), 403);

        $teacher = $userService->find($id);
        abort_if($teacher === null, 404);

        $link = $userLoginLinkService->createLink($teacher, config('students.login_link_ttl_minutes', 2880));
        $this->regeneratedLoginUrl = $userLoginLinkService->buildLoginUrl($link);
    }

    public function exportExcel(UserService $userService, UserLoginLinkService $userLoginLinkService)
    {
        abort_unless(auth()->user()->can('teachers.view'), 403);

        return Excel::download(
            new TeachersExport($this->exportRows($userService, $userLoginLinkService)),
            'teachers-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    public function exportPdf(UserService $userService, UserLoginLinkService $userLoginLinkService): StreamedResponse
    {
        abort_unless(auth()->user()->can('teachers.view'), 403);

        $filename = 'teachers-'.now()->format('Y-m-d').'.pdf';
        $output = Pdf::loadView('exports.teachers-pdf', [
            'rows' => $this->exportRows($userService, $userLoginLinkService),
        ])->output();

        return Response::streamDownload(function () use ($output) {
            echo $output;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @return Collection<int, array{name: string, email: string, login_url: string}>
     */
    private function exportRows(UserService $userService, UserLoginLinkService $userLoginLinkService): Collection
    {
        $ids = $userService->idsMatching($this->matchingFilters());
        $ttlMinutes = config('students.login_link_ttl_minutes', 2880);

        return collect($ids)
            ->map(fn (string $id) => $userService->find($id))
            ->filter()
            ->map(function ($teacher) use ($userLoginLinkService, $ttlMinutes) {
                $link = $userLoginLinkService->createLink($teacher, $ttlMinutes);

                return [
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'login_url' => $userLoginLinkService->buildLoginUrl($link),
                ];
            })
            ->values();
    }

    private function currentSchoolId(): ?string
    {
        return app(CurrentSchool::class)->getSchoolId() ?? auth()->user()->school_id;
    }

    public function destroy(string $id, UserService $userService): void
    {
        abort_unless(auth()->user()->can('teachers.delete'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        $user = $userService->find($id);
        abort_if($user === null, 404);

        try {
            $userService->deleteUser($user);
            $this->successMessage = __('Teacher deleted successfully.');
        } catch (ValidationException $exception) {
            $this->errorMessage = collect($exception->errors())->flatten()->first();
        }

        unset($this->teachers);
    }

    /**
     * @param  array<int, string>  $ids
     */
    public function destroySelected(array $ids, UserService $userService): void
    {
        abort_unless(auth()->user()->can('teachers.delete'), 403);

        $this->deleteMany($ids, $userService);

        $this->selectAllMatching = false;

        unset($this->teachers);
    }

    /**
     * Deletes every teacher matching the current filters, not just those
     * on the currently visible page — backs the "select all matching"
     * bulk-delete option.
     */
    public function destroyAllMatching(UserService $userService): void
    {
        abort_unless(auth()->user()->can('teachers.delete'), 403);

        $this->deleteMany($userService->idsMatching($this->matchingFilters()), $userService);

        $this->selectAllMatching = false;

        unset($this->teachers);
    }

    /**
     * IDs of every teacher matching the current filters, not just those on
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
            'role_id' => $this->teacherRoleId(),
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
            $this->successMessage = trans_choice('1 teacher deleted successfully.|:count teachers deleted successfully.', $deletedCount, ['count' => $deletedCount]);
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

    private function teacherRoleId(): ?int
    {
        $schoolId = $this->currentSchoolId();

        if ($schoolId === null) {
            return null;
        }

        return resolve(RoleRepositoryInterface::class)
            ->get(['school_id' => $schoolId, 'name' => RoleName::Teacher->value])
            ->first()?->id;
    }

    #[Computed]
    public function teachers(): LengthAwarePaginator
    {
        return resolve(UserService::class)->paginate(
            filters: [
                'search' => $this->search,
                'role_id' => $this->teacherRoleId(),
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

        $teachers = $this->teachersLoaded ? $this->teachers : null;
        $this->matchingCount = $teachers?->total() ?? 0;
        $this->pageIds = $teachers?->pluck('id')->values()->all() ?? [];

        return view('livewire.teachers.teacher-index', [
            'teachers' => $teachers,
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Teachers'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
