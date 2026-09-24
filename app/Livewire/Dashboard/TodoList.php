<?php

namespace App\Livewire\Dashboard;

use App\Services\StudentDashboardService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy]
class TodoList extends Component
{
    use WithPagination;

    public ?string $todoCourseFilter = null;

    public ?string $todoTypeFilter = null;

    public int $perPage = 5;

    public function updatedTodoCourseFilter(): void
    {
        $this->resetPage('todoPage');
    }

    public function updatedTodoTypeFilter(): void
    {
        $this->resetPage('todoPage');
    }

    #[Computed]
    public function enrolledCourses()
    {
        return app(StudentDashboardService::class)->enrolledCourses((string) Auth::id());
    }

    #[Computed]
    public function todoItems(): LengthAwarePaginator
    {
        $items = app(StudentDashboardService::class)->todoItems(
            (string) Auth::id(),
            $this->todoCourseFilter,
            $this->todoTypeFilter,
        );

        $page = $this->getPage('todoPage');

        return new LengthAwarePaginator(
            $items->forPage($page, $this->perPage)->values(),
            $items->count(),
            $this->perPage,
            $page,
            ['pageName' => 'todoPage'],
        );
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.todo-list-placeholder');
    }

    public function render()
    {
        return view('livewire.dashboard.todo-list');
    }
}
