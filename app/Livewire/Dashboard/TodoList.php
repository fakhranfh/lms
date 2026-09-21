<?php

namespace App\Livewire\Dashboard;

use App\Services\StudentDashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class TodoList extends Component
{
    public ?string $todoCourseFilter = null;

    public ?string $todoTypeFilter = null;

    #[Computed]
    public function enrolledCourses()
    {
        return app(StudentDashboardService::class)->enrolledCourses((string) Auth::id());
    }

    #[Computed]
    public function todoItems()
    {
        return app(StudentDashboardService::class)->todoItems(
            (string) Auth::id(),
            $this->todoCourseFilter,
            $this->todoTypeFilter,
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
