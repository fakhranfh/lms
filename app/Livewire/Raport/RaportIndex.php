<?php

namespace App\Livewire\Raport;

use App\Enums\RoleName;
use App\Livewire\Raport\Concerns\BuildsRaportViewData;
use App\Models\Course;
use App\Services\CoursePersonService;
use App\Services\CourseService;
use App\Services\GradebookScoringService;
use App\Support\CurrentSchool;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class RaportIndex extends Component
{
    use BuildsRaportViewData, WithPagination;

    private const DEFAULT_ROWS_PER_PAGE = 12;

    public bool $isStudent = false;

    public bool $dataLoaded = false;

    #[Url(as: 'per_page')]
    public int $perPage = self::DEFAULT_ROWS_PER_PAGE;

    public string $studentSearch = '';

    /**
     * @var array<int, string>
     */
    #[Url(as: 'grade')]
    public array $gradeFilter = [];

    public function mount(CurrentSchool $currentSchool): void
    {
        abort_unless(auth()->user()->can('raport.view'), 403);

        $this->isStudent = auth()->user()->hasRole(RoleName::Student);
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
        CurrentSchool $currentSchool,
        CourseService $courseService,
        CoursePersonService $coursePersonService,
        GradebookScoringService $gradebookScoringService,
    ) {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;

        $viewData = [
            'isStudent' => $this->isStudent,
        ];

        if (! $this->dataLoaded) {
            return view('livewire.raport.raport-index-placeholder', $viewData)
                ->extends('layouts.app', ['topbarTitle' => 'Raport'])
                ->section('app-content');
        }

        if ($this->isStudent) {
            $courses = $courseService->get(['enrolled_user_id' => auth()->id(), 'school_id' => $schoolId]);

            $viewData['courseCards'] = $courses->map(function (Course $course) use ($gradebookScoringService) {
                $result = $gradebookScoringService->computeForUser($course, auth()->id());

                return [
                    'course' => $course,
                    'finalScore' => $result['final']['score'],
                    'finalGrade' => $this->letterGrade($course, $result['final']['score']),
                    'finalLastUpdatedLabel' => $this->lastUpdatedLabel($result['final']['last_updated_at']),
                    'typeRows' => $this->typeRows($result),
                ];
            })->values();
        } else {
            $courses = $courseService->get(['teaching_user_id' => auth()->id(), 'school_id' => $schoolId]);

            $viewData['gradeFilter'] = $this->gradeFilter;
            $viewData['studentRows'] = $this->paginateStudentRows($courses, $coursePersonService, $gradebookScoringService);
        }

        return view('livewire.raport.raport-index', $viewData)
            ->extends('layouts.app', ['topbarTitle' => 'Raport'])
            ->section('app-content');
    }

    /**
     * Builds one row per unique student across every course this teacher
     * teaches, combining that student's final score across all of them into
     * a single average — the Raport covers a student's whole record, not
     * one course at a time, so students enrolled in more than one of this
     * teacher's courses are no longer listed once per course.
     *
     * @param  Collection<int, Course>  $courses
     */
    private function paginateStudentRows(
        Collection $courses,
        CoursePersonService $coursePersonService,
        GradebookScoringService $gradebookScoringService,
    ): LengthAwarePaginator {
        $studentsById = collect();

        foreach ($courses as $course) {
            foreach ($coursePersonService->studentsForCourse($course->id) as $coursePerson) {
                $result = $gradebookScoringService->computeForUser($course, $coursePerson->user_id);

                $entry = $studentsById->get($coursePerson->user_id, [
                    'user' => $coursePerson->user,
                    'scores' => [],
                ]);

                if ($result['final']['score'] !== null) {
                    $entry['scores'][] = $result['final']['score'];
                }

                $studentsById->put($coursePerson->user_id, $entry);
            }
        }

        $rows = $studentsById->map(function (array $entry) {
            $averageScore = count($entry['scores']) > 0
                ? round(array_sum($entry['scores']) / count($entry['scores']), 2)
                : null;

            return [
                'user' => $entry['user'],
                'score' => $averageScore,
                'grade' => $this->aggregateGrade($averageScore),
            ];
        })->values();

        $search = trim($this->studentSearch);

        if ($search !== '') {
            $rows = $rows->filter(fn (array $row) => str_contains(strtolower($row['user']->name), strtolower($search)))->values();
        }

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

    /**
     * Grade bands here are fixed (90/80/70/60) rather than a Course's own
     * grade_band_* columns, since this row averages a student's final score
     * across every course they're enrolled in — courses that may each have
     * different custom bands.
     */
    private function aggregateGrade(?float $averageScore): ?string
    {
        if ($averageScore === null) {
            return null;
        }

        return match (true) {
            $averageScore >= 90 => 'A',
            $averageScore >= 80 => 'B',
            $averageScore >= 70 => 'C',
            $averageScore >= 60 => 'D',
            default => 'E',
        };
    }
}
