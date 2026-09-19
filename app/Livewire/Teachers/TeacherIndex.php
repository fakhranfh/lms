<?php

namespace App\Livewire\Teachers;

use App\Enums\RoleName;
use App\Livewire\Concerns\ManagesUserIndex;
use App\Services\UserService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read LengthAwarePaginator $teachers
 */
class TeacherIndex extends Component
{
    use ManagesUserIndex, WithPagination;

    /**
     * Whether the teachers table has been loaded yet. Kept false through
     * the initial render (triggered via wire:init) so the page paints
     * instantly with a skeleton in place of the table.
     */
    public bool $teachersLoaded = false;

    protected function managedRole(): RoleName
    {
        return RoleName::Teacher;
    }

    protected function permissionPrefix(): string
    {
        return 'teachers';
    }

    protected function configKey(): string
    {
        return 'teachers';
    }

    protected function entityLabel(): string
    {
        return 'teacher';
    }

    protected function pdfView(): string
    {
        return 'exports.teachers-pdf';
    }

    protected function viewName(): string
    {
        return 'livewire.teachers.teacher-index';
    }

    protected function viewDataKey(): string
    {
        return 'teachers';
    }

    protected function topbarTitle(): string
    {
        return 'Teachers';
    }

    protected function isLoaded(): bool
    {
        return $this->teachersLoaded;
    }

    protected function items(): LengthAwarePaginator
    {
        return $this->teachers;
    }

    public function loadUsers(): void
    {
        $this->teachersLoaded = true;
    }

    #[On('teachers-generated')]
    public function handleTeachersGenerated(int $count): void
    {
        $this->handleUsersGenerated($count);

        unset($this->teachers);
    }

    public function destroy(string $id, UserService $userService): void
    {
        $this->handleDestroy($id, $userService);

        unset($this->teachers);
    }

    /**
     * @param  array<int, string>  $ids
     */
    public function destroySelected(array $ids, UserService $userService): void
    {
        $this->handleDestroySelected($ids, $userService);

        unset($this->teachers);
    }

    public function destroyAllMatching(UserService $userService): void
    {
        $this->handleDestroyAllMatching($userService);

        unset($this->teachers);
    }

    #[Computed]
    public function teachers(): LengthAwarePaginator
    {
        return $this->paginateUsers();
    }
}
