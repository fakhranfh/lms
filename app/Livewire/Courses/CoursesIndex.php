<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use Livewire\Component;
use Livewire\WithPagination;

class CoursesIndex extends Component
{
    use WithPagination;

    public ?string $search = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('courses.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Course::where('school_id', auth()->user()->school_id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        $courses = $query->with('creator')->latest()->paginate(10);

        return view('livewire.courses.courses-index', [
            'courses' => $courses,
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Courses'])
            ->section('app-content');
    }
}
