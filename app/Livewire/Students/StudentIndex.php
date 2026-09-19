<?php

namespace App\Livewire\Students;

use App\Enums\RoleName;
use App\Exports\StudentsExport;
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

    protected function exportClass(): string
    {
        return StudentsExport::class;
    }

    protected function pdfView(): string
    {
        return 'exports.students-pdf';
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
