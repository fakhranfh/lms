<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Services\CoursePersonService;
use App\Services\GradebookScoringService;
use App\Support\CourseTabs;
use Livewire\Component;

class GradebookGradeBands extends Component
{
    public Course $course;

    public bool $dataLoaded = false;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public function mount(Course $course): void
    {
        $schoolId = auth()->user()->school_id;
        abort_unless(auth()->user()->can('gradebook.manage') && $course->school_id === $schoolId, 403);

        $this->course = $course;
    }

    public function loadData(): void
    {
        $this->dataLoaded = true;
    }

    public function clearSuccessMessage(): void
    {
        $this->successMessage = null;
    }

    /**
     * @param  array{a: mixed, b: mixed, c: mixed, d: mixed}  $bands  Minimum percentage for grades A-D, validated client-side before this is ever called
     */
    public function save(array $bands, CoursePersonService $coursePersonService, GradebookScoringService $gradebookScoringService): void
    {
        abort_unless(auth()->user()->can('gradebook.manage'), 403);

        $a = (int) $bands['a'];
        $b = (int) $bands['b'];
        $c = (int) $bands['c'];
        $d = (int) $bands['d'];

        foreach ([$a, $b, $c, $d] as $min) {
            if ($min < 0 || $min > 100) {
                $this->errorMessage = __('Every grade threshold must be between 0 and 100.');

                return;
            }
        }

        if (! ($a > $b && $b > $c && $c > $d)) {
            $this->errorMessage = __('Grade thresholds must strictly decrease from A to D.');

            return;
        }

        $this->course->update([
            'grade_band_a_min' => $a,
            'grade_band_b_min' => $b,
            'grade_band_c_min' => $c,
            'grade_band_d_min' => $d,
        ]);

        $studentIds = $coursePersonService->studentsForCourse($this->course->id)->pluck('user_id');

        foreach ($studentIds as $studentId) {
            $gradebookScoringService->recomputeForUser($this->course, $studentId);
        }

        $this->errorMessage = null;
        $this->successMessage = __('Grade ranges updated and letter grades recalculated for every student.');
    }

    public function render()
    {
        $viewData = [
            'course' => $this->course,
            'courseTabs' => CourseTabs::build($this->course, 'gradebook'),
        ];

        if (! $this->dataLoaded) {
            return view('livewire.courses.gradebook-grade-bands-placeholder', $viewData)
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        return view('livewire.courses.gradebook-grade-bands', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
