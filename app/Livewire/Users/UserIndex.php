<?php

namespace App\Livewire\Users;

use App\Enums\RoleName;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Services\UserService;
use App\Support\CurrentSchool;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read LengthAwarePaginator $users
 * @property-read Collection $availableRoles
 */
class UserIndex extends Component
{
    public ?string $search = null;

    public ?string $filterRole = null;

    public string $sort = 'name';

    public string $direction = 'asc';

    public int $perPage = 15;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    /**
     * Whether the users table has been loaded yet. Kept false through the
     * initial render (triggered via wire:init) so the page paints instantly
     * with a skeleton in place of the table, instead of blocking on the query.
     */
    public bool $usersLoaded = false;

    public function mount(): void
    {
        $this->successMessage = session('success');
    }

    public function loadUsers(): void
    {
        $this->usersLoaded = true;
    }

    public function destroy(string $id, UserService $userService): void
    {
        abort_unless(auth()->user()->can('users.delete'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        $user = $userService->find($id);
        abort_if($user === null, 404);

        try {
            $userService->deleteUser($user);
            $this->successMessage = __('User deleted successfully.');
        } catch (ValidationException $exception) {
            $this->errorMessage = collect($exception->errors())->flatten()->first();
        }

        unset($this->users);
    }

    /**
     * Bulk-delete users selected client-side (checkboxes tracked in Alpine,
     * not synced to the server on every click); the selected ids are only
     * sent over the wire when the admin confirms the delete.
     *
     * @param  array<int, string>  $ids
     */
    public function destroySelected(array $ids, UserService $userService): void
    {
        abort_unless(auth()->user()->can('users.delete'), 403);

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
            $this->successMessage = trans_choice('1 user deleted successfully.|:count users deleted successfully.', $deletedCount, ['count' => $deletedCount]);
        }

        if ($errors !== []) {
            $this->errorMessage = collect($errors)->unique()->join(' ');
        }

        unset($this->users);
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'filterRole'])) {
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

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return resolve(UserService::class)->paginate(
            filters: [
                'search' => $this->search,
                'role_id' => $this->filterRole,
                'sort' => $this->sort,
                'direction' => $this->direction,
            ],
            with: ['roles'],
            perPage: $this->perPage,
        );
    }

    #[Computed]
    public function availableRoles(): Collection
    {
        $schoolId = resolve(CurrentSchool::class)->getSchoolId() ?? auth()->user()->school_id;

        return resolve(RoleRepositoryInterface::class)->get(['school_id' => $schoolId]);
    }

    public function render()
    {
        $isAdminUser = auth()->user()->hasRole(RoleName::Admin);

        return view('livewire.users.user-index', [
            'users' => $this->usersLoaded ? $this->users : null,
        ])
            ->extends($isAdminUser ? 'layouts.admin' : 'layouts.app', ['topbarTitle' => 'Users'])
            ->section($isAdminUser ? 'admin-content' : 'app-content');
    }
}
