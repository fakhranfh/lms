<?php

namespace App\Livewire\Courses;

use App\Enums\RoleName;
use App\Livewire\Courses\Concerns\BuildsGradebookViewData;
use App\Livewire\Courses\Concerns\HasGradebookIndexDevTools;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Services\CoursePersonService;
use App\Services\GradebookScoringService;
use App\Support\CourseTabs;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class GradebookIndex extends Component
{
    use BuildsGradebookViewData, HasGradebookIndexDevTools, WithPagination;

    private const DEFAULT_STUDENTS_PER_PAGE = 12;

    public Course $course;

    public bool $isStudent = false;

    public bool $dataLoaded = false;

    public ?string $successMessage = null;

    #[Url(as: 'per_page')]
    public int $perPage = self::DEFAULT_STUDENTS_PER_PAGE;

    public string $studentSearch = '';

    /**
     * @var array<int, string>
     */
    #[Url(as: 'grade')]
    public array $gradeFilter = [];

    public function mount(Course $course): void
    {
        $schoolId = auth()->user()->school_id;
        abort_unless(auth()->user()->can('gradebook.view') && $course->school_id === $schoolId, 403);

        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);
        $this->isLocalEnv = app()->environment('local');
    }

    public function loadData(): void
    {
        $this->dataLoaded = true;
    }

    /**
     * @param  array<int, string>  $grades
     */
    public function applyGradeFilter(array $grades): void
    {
        $this->gradeFilter = array_values(array_intersect(['A', 'B', 'C', 'D', 'E'], $grades));
        $this->resetPage();
    }

    public function render(
        CoursePersonService $coursePersonService,
        GradebookScoringService $gradebookScoringService,
    ) {
        $viewData = [
            'course' => $this->course,
            'isStudent' => $this->isStudent,
            'canManage' => auth()->user()->can('gradebook.manage'),
            'isLocalEnv' => $this->isLocalEnv,
            'successMessage' => $this->successMessage,
            'courseTabs' => CourseTabs::build($this->course, 'gradebook'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
        ];

        if (! $this->dataLoaded) {
            return view('livewire.courses.gradebook-index-placeholder', $viewData)
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        if ($this->isStudent) {
            $result = $gradebookScoringService->computeForUser($this->course, auth()->id());
            $viewData['result'] = $result;
            $viewData['finalGrade'] = $this->letterGrade($result['final']['score']);
            $viewData['finalLastUpdatedLabel'] = $this->lastUpdatedLabel($result['final']['last_updated_at']);
            $viewData['typeRows'] = $this->typeRows($result, null);
        } else {
            $students = $coursePersonService->studentsForCourse($this->course->id);

            $search = trim($this->studentSearch);

            if ($search !== '') {
                $students = $students->filter(
                    fn ($coursePerson) => str_contains(strtolower($coursePerson->user->name), strtolower($search))
                )->values();
            }

            $viewData['gradeFilter'] = $this->gradeFilter;
            $viewData['studentRows'] = $this->paginateStudentRows($students, $gradebookScoringService);
        }

        return view('livewire.courses.gradebook-index', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }

    /**
     * @param  Collection<int, CoursePerson>  $students
     */
    private function paginateStudentRows(Collection $students, GradebookScoringService $gradebookScoringService): LengthAwarePaginator
    {
        $rows = $students->map(function (CoursePerson $coursePerson) use ($gradebookScoringService) {
            $result = $gradebookScoringService->computeForUser($this->course, $coursePerson->user_id);

            return [
                'user' => $coursePerson->user,
                'final' => $result['final'],
                'grade' => $this->letterGrade($result['final']['score']),
            ];
        })->values();

        if ($this->gradeFilter !== []) {
            $rows = $rows->filter(fn (array $row) => in_array($row['grade'], $this->gradeFilter, true))->values();
        }

        $page = $this->getPage();

        return new LengthAwarePaginator(
            $rows->forPage($page, $this->perPage)->values(),
            $rows->count(),
            $this->perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        );
    }
}
