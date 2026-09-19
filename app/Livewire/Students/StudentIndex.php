<?php

namespace App\Livewire\Students;

use App\Enums\RoleName;
use App\Livewire\Concerns\ManagesUserIndex;
use App\Services\UserService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read LengthAwarePaginator $students
 */
class StudentIndex extends Component
{
    use ManagesUserIndex, WithPagination;

    /**
     * Whether the students table has been loaded yet. Kept false through
     * the initial render (triggered via wire:init) so the page paints
     * instantly with a skeleton in place of the table.
     */
    public bool $studentsLoaded = false;

    protected function managedRole(): RoleName
    {
        return RoleName::Student;
    }

    protected function permissionPrefix(): string
    {
        return 'students';
    }

    protected function configKey(): string
    {
        return 'students';
    }

    protected function entityLabel(): string
    {
        return 'student';
    }

    protected function pdfView(): string
    {
        return 'exports.students-pdf';
    }

    protected function viewName(): string
    {
        return 'livewire.students.student-index';
    }

    protected function viewDataKey(): string
    {
        return 'students';
    }

    protected function topbarTitle(): string
    {
        return 'Students';
    }

    protected function isLoaded(): bool
    {
        return $this->studentsLoaded;
    }

    protected function items(): LengthAwarePaginator
    {
        return $this->students;
    }

    public function loadUsers(): void
    {
        $this->studentsLoaded = true;
    }

    #[On('students-generated')]
    public function handleStudentsGenerated(int $count): void
    {
        $this->handleUsersGenerated($count);

        unset($this->students);
    }

    public function destroy(string $id, UserService $userService): void
    {
        $this->handleDestroy($id, $userService);

        unset($this->students);
    }

    /**
     * @param  array<int, string>  $ids
     */
    public function destroySelected(array $ids, UserService $userService): void
    {
        $this->handleDestroySelected($ids, $userService);

        unset($this->students);
    }

    public function destroyAllMatching(UserService $userService): void
    {
        $this->handleDestroyAllMatching($userService);

        unset($this->students);
    }

    #[Computed]
    public function students(): LengthAwarePaginator
    {
        return $this->paginateUsers();
    }
}
