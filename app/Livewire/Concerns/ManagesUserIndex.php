<?php

namespace App\Livewire\Concerns;

use App\Enums\RoleName;
use App\Exports\UserExport;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Services\UserLoginLinkService;
use App\Services\UserService;
use App\Support\CurrentSchool;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared listing/export/bulk-delete logic for user-management index pages
 * that only differ by role (e.g. students, teachers). The host component
 * still owns its own `#[Computed]` accessor, `#[On]` listener and loaded
 * flag under their role-specific names, since Livewire attributes and the
 * Blade views bind to those names directly.
 */
trait ManagesUserIndex
{
    public ?string $search = null;

    public string $sort = 'name';

    public string $direction = 'asc';

    public int $perPage = 15;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public ?string $regeneratedLoginUrl = null;

    /**
     * Whether the bulk-delete selection has been expanded to every user
     * matching the current filters, not just those checked on this page.
     */
    public bool $selectAllMatching = false;

    /**
     * Total number of users matching the current filters, kept in sync on
     * every render so the "select all matching" prompt can compare it
     * against the current page's selection count.
     */
    public int $matchingCount = 0;

    /**
     * IDs of the users on the currently rendered page, kept in sync on
     * every render via @entangle so the "select all on page" checkbox
     * reactively recomputes after pagination — reading the DOM directly
     * for this doesn't trigger Alpine's reactivity when Livewire morphs in
     * a new page of rows without any of the entangled properties changing.
     *
     * @var array<int, string>
     */
    public array $pageIds = [];

    /**
     * The role this index manages, e.g. RoleName::Student.
     */
    abstract protected function managedRole(): RoleName;

    /**
     * Permission/route name prefix for this role, e.g. "students".
     */
    abstract protected function permissionPrefix(): string;

    /**
     * Config key holding the login link TTL in minutes, e.g. "students".
     */
    abstract protected function configKey(): string;

    /**
     * Lowercase singular label used in messages, e.g. "student".
     */
    abstract protected function entityLabel(): string;

    /**
     * Blade view used to render the PDF export.
     */
    abstract protected function pdfView(): string;

    /**
     * Blade view used to render the index page.
     */
    abstract protected function viewName(): string;

    /**
     * View data key the index Blade view expects the paginator under,
     * e.g. "students".
     */
    abstract protected function viewDataKey(): string;

    /**
     * Page title shown in the admin/app layout topbar, e.g. "Students".
     */
    abstract protected function topbarTitle(): string;

    /**
     * Whether the table has been loaded yet (kept false through the
     * initial render, triggered via wire:init, so the page paints
     * instantly with a skeleton in place of the table).
     */
    abstract protected function isLoaded(): bool;

    /**
     * The current page of users, backed by the host component's own
     * `#[Computed]` accessor.
     */
    abstract protected function items(): LengthAwarePaginator;

    public function mountManagesUserIndex(): void
    {
        $this->successMessage = session('success');
        $this->errorMessage = session('error');
    }

    protected function handleUsersGenerated(int $count): void
    {
        $label = $this->entityLabel();

        $this->successMessage = trans_choice(
            "1 {$label} generated successfully.|:count {$label}s generated successfully.",
            $count,
            ['count' => $count],
        );
        $this->errorMessage = null;
    }

    public function regenerateLoginLink(string $id, UserService $userService, UserLoginLinkService $userLoginLinkService): void
    {
        abort_unless(auth()->user()->can("{$this->permissionPrefix()}.edit"), 403);

        $user = $userService->find($id);
        abort_if($user === null, 404);

        $link = $userLoginLinkService->createLink($user, config("{$this->configKey()}.login_link_ttl_minutes", 2880));
        $this->regeneratedLoginUrl = $userLoginLinkService->buildLoginUrl($link);
    }

    public function exportExcel(UserService $userService, UserLoginLinkService $userLoginLinkService)
    {
        abort_unless(auth()->user()->can("{$this->permissionPrefix()}.view"), 403);

        return Excel::download(
            new UserExport($this->exportRows($userService, $userLoginLinkService)),
            "{$this->permissionPrefix()}-".now()->format('Y-m-d').'.xlsx',
        );
    }

    public function exportPdf(UserService $userService, UserLoginLinkService $userLoginLinkService): StreamedResponse
    {
        abort_unless(auth()->user()->can("{$this->permissionPrefix()}.view"), 403);

        $filename = "{$this->permissionPrefix()}-".now()->format('Y-m-d').'.pdf';
        $output = Pdf::loadView($this->pdfView(), [
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
        $ttlMinutes = config("{$this->configKey()}.login_link_ttl_minutes", 2880);

        return collect($ids)
            ->map(fn (string $id) => $userService->find($id))
            ->filter()
            ->map(function ($user) use ($userLoginLinkService, $ttlMinutes) {
                $link = $userLoginLinkService->createLink($user, $ttlMinutes);

                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'login_url' => $userLoginLinkService->buildLoginUrl($link),
                ];
            })
            ->values();
    }

    private function currentSchoolId(): ?string
    {
        return app(CurrentSchool::class)->getSchoolId() ?? auth()->user()->school_id;
    }

    protected function handleDestroy(string $id, UserService $userService): void
    {
        abort_unless(auth()->user()->can("{$this->permissionPrefix()}.delete"), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        $user = $userService->find($id);
        abort_if($user === null, 404);

        try {
            $userService->deleteUser($user);
            $this->successMessage = __(ucfirst($this->entityLabel()).' deleted successfully.');
        } catch (ValidationException $exception) {
            $this->errorMessage = collect($exception->errors())->flatten()->first();
        }
    }

    /**
     * @param  array<int, string>  $ids
     */
    protected function handleDestroySelected(array $ids, UserService $userService): void
    {
        abort_unless(auth()->user()->can("{$this->permissionPrefix()}.delete"), 403);

        $this->deleteMany($ids, $userService);

        $this->selectAllMatching = false;
    }

    /**
     * Deletes every user matching the current filters, not just those on
     * the currently visible page — backs the "select all matching"
     * bulk-delete option.
     */
    protected function handleDestroyAllMatching(UserService $userService): void
    {
        abort_unless(auth()->user()->can("{$this->permissionPrefix()}.delete"), 403);

        $this->deleteMany($userService->idsMatching($this->matchingFilters()), $userService);

        $this->selectAllMatching = false;
    }

    /**
     * IDs of every user matching the current filters, not just those on
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
            'role_id' => $this->roleId(),
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
        $label = $this->entityLabel();

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
            $this->successMessage = trans_choice(
                "1 {$label} deleted successfully.|:count {$label}s deleted successfully.",
                $deletedCount,
                ['count' => $deletedCount],
            );
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

    private function roleId(): ?int
    {
        $schoolId = $this->currentSchoolId();

        if ($schoolId === null) {
            return null;
        }

        return resolve(RoleRepositoryInterface::class)
            ->get(['school_id' => $schoolId, 'name' => $this->managedRole()->value])
            ->first()?->id;
    }

    private function paginateUsers(): LengthAwarePaginator
    {
        return resolve(UserService::class)->paginate(
            filters: [
                'search' => $this->search,
                'role_id' => $this->roleId(),
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

        $items = $this->isLoaded() ? $this->items() : null;
        $this->matchingCount = $items?->total() ?? 0;
        $this->pageIds = $items?->pluck('id')->values()->all() ?? [];

        return view($this->viewName(), [
            $this->viewDataKey() => $items,
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => $this->topbarTitle()])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
