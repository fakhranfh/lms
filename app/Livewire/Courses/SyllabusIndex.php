<?php

namespace App\Livewire\Courses;

use App\Enums\RoleName;
use App\Enums\SyllabusPolicyScope;
use App\Livewire\Concerns\WithRichTextEditor;
use App\Livewire\Courses\Concerns\HasSyllabusIndexDevTools;
use App\Models\Course;
use App\Models\Syllabus;
use App\Services\CoursePersonService;
use App\Services\SyllabusClassPolicyService;
use App\Services\SyllabusEvaluationActivityService;
use App\Services\SyllabusEvaluationService;
use App\Services\SyllabusLearningOutcomeService;
use App\Services\SyllabusRubricCellService;
use App\Services\SyllabusRubricKeyIndicatorService;
use App\Services\SyllabusRubricProficiencyLevelService;
use App\Services\SyllabusService;
use App\Support\CourseTabs;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SyllabusIndex extends Component
{
    use HasSyllabusIndexDevTools;
    use WithRichTextEditor;

    public Course $course;

    public bool $isStudent = false;

    /**
     * Syllabus is queried lazily via wire:init (loadSyllabus), so the initial
     * page render is a cheap skeleton instead of blocking on the query.
     */
    public bool $syllabusLoaded = false;

    public bool $editing = false;

    public ?Syllabus $editingSyllabus = null;

    #[Validate('nullable|string')]
    public string $courseDescription = '';

    /**
     * A slot is set to null (not spliced out) when its row is removed
     * client-side, so element types stay nullable until pruneRemovedRows()
     * filters them out just before validation/persistence.
     *
     * @var array<int, array{scope: string, content: string, order: int}|null>
     */
    public array $classPolicies = [];

    #[Validate('nullable|string')]
    public string $submissionAndCollection = '';

    #[Validate('nullable|string')]
    public string $tutorialActivityPlan = '';

    /** @var array<int, array{code: string, description: string, order: int}|null> */
    public array $learningOutcomes = [];

    /**
     * @var array<int, array{class_type: string, activities: array<int, array{activity: string, weight: string, order: int, learning_outcome_indices: array<int, int>}|null>}|null>
     */
    public array $evaluations = [];

    /** @var array<int, array{label: string, score_min: string, score_max: string, order: int}|null> */
    public array $rubricProficiencyLevels = [];

    /** @var array<int, array{learning_outcome_index: string, code: string, description: string, order: int}|null> */
    public array $rubricKeyIndicators = [];

    /** @var array<int, array<int, string>> */
    public array $rubricCells = [];

    #[Validate('nullable|string')]
    public string $teachingLearningStrategies = '';

    #[Validate('nullable|string')]
    public string $textbooks = '';

    #[Validate('nullable|string')]
    public string $competencyMap = '';

    #[Validate('nullable|string')]
    public string $videoOverview = '';

    public string $activeSection = 'course_description';

    public function mount(Course $course, SyllabusService $syllabusService, bool $startInEditMode = false): void
    {
        $schoolId = auth()->user()->school_id;
        abort_unless(auth()->user()->can('syllabus.view') && $course->school_id === $schoolId, 403);

        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);

        if ($startInEditMode) {
            abort_unless(auth()->user()->can('syllabus.edit'), 403);

            $this->loadFormData($syllabusService);
            $this->syllabusLoaded = true;
            $this->editing = true;
        }
    }

    /**
     * Preview and edit live on separate routes, so a course with no syllabus
     * yet sends editors straight to the edit page instead of an empty state.
     */
    public function loadSyllabus(SyllabusService $syllabusService)
    {
        $this->syllabusLoaded = true;

        if (auth()->user()->can('syllabus.edit') && ! $syllabusService->findByCourse($this->course->id)) {
            return $this->redirect(route('syllabus.edit', $this->course));
        }
    }

    private function loadFormData(SyllabusService $syllabusService): void
    {
        $syllabus = $syllabusService->findByCourse($this->course->id, [
            'classPolicies',
            'learningOutcomes.rubricKeyIndicators.cells',
            'evaluations.activities.learningOutcomes',
            'rubricProficiencyLevels',
        ]);

        $this->editingSyllabus = $syllabus;

        if (! $syllabus) {
            return;
        }

        $this->courseDescription = $syllabus->course_description ?? '';
        $this->submissionAndCollection = $syllabus->submission_and_collection ?? '';
        $this->tutorialActivityPlan = $syllabus->tutorial_activity_plan ?? '';
        $this->teachingLearningStrategies = $syllabus->teaching_learning_strategies ?? '';
        $this->textbooks = $syllabus->textbooks ?? '';
        $this->competencyMap = $syllabus->competency_map ?? '';
        $this->videoOverview = $syllabus->video_overview ?? '';

        $this->classPolicies = $syllabus->classPolicies->map(fn ($policy) => [
            'scope' => $policy->scope->value,
            'content' => $policy->content,
            'order' => $policy->order,
        ])->values()->all();

        $learningOutcomeIndexById = [];
        $this->learningOutcomes = $syllabus->learningOutcomes->values()->map(function ($lo, $index) use (&$learningOutcomeIndexById) {
            $learningOutcomeIndexById[$lo->id] = $index;

            return [
                'code' => $lo->code,
                'description' => $lo->description,
                'order' => $lo->order,
            ];
        })->all();

        $this->evaluations = $syllabus->evaluations->map(fn ($evaluation) => [
            'class_type' => $evaluation->class_type,
            'activities' => $evaluation->activities->map(fn ($activity) => [
                'activity' => $activity->activity,
                'weight' => (string) $activity->weight,
                'order' => $activity->order,
                'learning_outcome_indices' => $activity->learningOutcomes
                    ->map(fn ($lo) => $learningOutcomeIndexById[$lo->id] ?? null)
                    ->filter(fn ($index) => $index !== null)
                    ->values()
                    ->all(),
            ])->values()->all(),
        ])->values()->all();

        $proficiencyLevelIndexById = [];
        $this->rubricProficiencyLevels = $syllabus->rubricProficiencyLevels->values()->map(function ($level, $index) use (&$proficiencyLevelIndexById) {
            $proficiencyLevelIndexById[$level->id] = $index;

            return [
                'label' => $level->label,
                'score_min' => (string) $level->score_min,
                'score_max' => (string) $level->score_max,
                'order' => $level->order,
            ];
        })->all();

        $keyIndicatorIndexById = [];
        $keyIndicatorIndex = 0;
        foreach ($syllabus->learningOutcomes as $loIndex => $lo) {
            foreach ($lo->rubricKeyIndicators as $ki) {
                $keyIndicatorIndexById[$ki->id] = $keyIndicatorIndex;
                $this->rubricKeyIndicators[$keyIndicatorIndex] = [
                    'learning_outcome_index' => (string) $loIndex,
                    'code' => $ki->code,
                    'description' => $ki->description,
                    'order' => $ki->order,
                ];

                foreach ($ki->cells as $cell) {
                    $plIndex = $proficiencyLevelIndexById[$cell->rubric_proficiency_level_id] ?? null;
                    if ($plIndex !== null) {
                        $this->rubricCells[$keyIndicatorIndex][$plIndex] = $cell->description ?? '';
                    }
                }

                $keyIndicatorIndex++;
            }
        }
    }

    /**
     * Rows are removed purely client-side (see resources/js/syllabus-form.js):
     * the row's slot is set to null and its DOM node deleted, with no
     * request round trip. Reindexing survivors here instead of in the
     * browser would desync their already-bound wire:model/index-baked
     * handlers, so the null holes are only cleaned up now, right before
     * validation and persistence.
     */
    private function pruneRemovedRows(): void
    {
        $this->classPolicies = $this->withoutNullRows($this->classPolicies);
        $this->learningOutcomes = $this->withoutNullRows($this->learningOutcomes);
        $this->rubricProficiencyLevels = $this->withoutNullRows($this->rubricProficiencyLevels);

        $evaluations = [];
        foreach ($this->evaluations as $group) {
            if ($group === null) {
                continue;
            }

            $group['activities'] = $this->withoutNullRows($group['activities']);
            $evaluations[] = $group;
        }
        $this->evaluations = $evaluations;

        $keyIndicators = [];
        $cells = [];
        foreach ($this->rubricKeyIndicators as $index => $keyIndicator) {
            if ($keyIndicator === null) {
                continue;
            }

            $keyIndicators[] = $keyIndicator;
            $cells[] = $this->rubricCells[$index] ?? [];
        }
        $this->rubricKeyIndicators = $keyIndicators;
        $this->rubricCells = $cells;
    }

    /**
     * @param  array<int, array<string, mixed>|null>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function withoutNullRows(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            if ($row !== null) {
                $result[] = $row;
            }
        }

        return $result;
    }

    protected function richTextAttachmentFolder(): string
    {
        return 'syllabus';
    }

    private function promoteRichText(?string $html): ?string
    {
        if (! $html) {
            return null;
        }

        return HtmlSanitizer::forum($this->promoteRichTextAttachments($html));
    }

    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'classPolicies.*.scope' => 'required|in:f2f_video,online,general',
            'classPolicies.*.content' => 'required|string',
            'learningOutcomes.*.code' => 'required|string|max:20',
            'learningOutcomes.*.description' => 'required|string',
            'evaluations.*.class_type' => 'required|string|max:50',
            'evaluations.*.activities.*.activity' => 'required|string|max:255',
            'evaluations.*.activities.*.weight' => 'required|numeric|min:0|max:100',
            'rubricProficiencyLevels.*.label' => 'required|string|max:50',
            'rubricProficiencyLevels.*.score_min' => 'required|integer|min:0',
            'rubricProficiencyLevels.*.score_max' => 'required|integer|min:0',
            'rubricKeyIndicators.*.code' => 'required|string|max:20',
            'rubricKeyIndicators.*.description' => 'required|string',
        ];
    }

    private function runCustomValidation(): void
    {
        $errors = [];

        $codes = collect($this->learningOutcomes)->map(fn ($lo) => trim($lo['code']))->filter();
        if ($codes->count() !== $codes->unique()->count()) {
            $errors['learningOutcomes'] = __('Learning outcome codes must be unique.');
        }

        foreach ($this->evaluations as $groupIndex => $group) {
            $sum = collect($group['activities'])->sum(fn ($activity) => (float) $activity['weight']);
            if (abs($sum - 100.0) > 0.02) {
                $errors["evaluations.{$groupIndex}.activities"] = __('Weights for this evaluation group must total 100%.');
            }
        }

        $loCount = count($this->learningOutcomes);
        foreach ($this->rubricKeyIndicators as $index => $keyIndicator) {
            $loIndex = $keyIndicator['learning_outcome_index'];
            if ($loIndex === '' || ! ctype_digit((string) $loIndex) || (int) $loIndex >= $loCount) {
                $errors["rubricKeyIndicators.{$index}.learning_outcome_index"] = __('Select a valid learning outcome.');
            }
        }

        $keyIndicatorCount = count($this->rubricKeyIndicators);
        $proficiencyLevelCount = count($this->rubricProficiencyLevels);
        foreach ($this->rubricCells as $keyIndicatorIndex => $row) {
            if ((int) $keyIndicatorIndex >= $keyIndicatorCount) {
                $errors["rubricCells.{$keyIndicatorIndex}"] = __('Invalid rubric key indicator reference.');

                continue;
            }

            foreach ($row as $proficiencyLevelIndex => $description) {
                if ((int) $proficiencyLevelIndex >= $proficiencyLevelCount) {
                    $errors["rubricCells.{$keyIndicatorIndex}.{$proficiencyLevelIndex}"] = __('Invalid proficiency level reference.');
                }
            }
        }

        if ($errors !== []) {
            foreach ($errors as $key => $message) {
                $this->addError($key, $message);
            }

            throw ValidationException::withMessages($errors);
        }
    }

    public function save(
        SyllabusService $syllabusService,
        SyllabusClassPolicyService $classPolicyService,
        SyllabusLearningOutcomeService $learningOutcomeService,
        SyllabusEvaluationService $evaluationService,
        SyllabusEvaluationActivityService $evaluationActivityService,
        SyllabusRubricKeyIndicatorService $keyIndicatorService,
        SyllabusRubricProficiencyLevelService $proficiencyLevelService,
        SyllabusRubricCellService $rubricCellService,
    ) {
        abort_unless(auth()->user()->can('syllabus.edit'), 403);

        $this->pruneRemovedRows();
        $this->validate();
        $this->runCustomValidation();

        $data = [
            'course_id' => $this->course->id,
            'course_description' => $this->promoteRichText($this->courseDescription),
            'submission_and_collection' => $this->promoteRichText($this->submissionAndCollection),
            'tutorial_activity_plan' => $this->promoteRichText($this->tutorialActivityPlan),
            'teaching_learning_strategies' => $this->promoteRichText($this->teachingLearningStrategies),
            'textbooks' => $this->promoteRichText($this->textbooks),
            'competency_map' => $this->promoteRichText($this->competencyMap),
            'video_overview' => $this->promoteRichText($this->videoOverview),
        ];

        if ($this->editingSyllabus) {
            $syllabusService->update($this->editingSyllabus->id, $data);
            $syllabus = $this->editingSyllabus->fresh();
        } else {
            $syllabus = $syllabusService->create($data);
            $this->editingSyllabus = $syllabus;
        }

        DB::transaction(function () use (
            $syllabus,
            $classPolicyService,
            $learningOutcomeService,
            $evaluationService,
            $evaluationActivityService,
            $keyIndicatorService,
            $proficiencyLevelService,
            $rubricCellService,
        ) {
            foreach ($syllabus->classPolicies()->get() as $existing) {
                $classPolicyService->delete($existing->id);
            }
            foreach (array_values($this->classPolicies) as $index => $policy) {
                $classPolicyService->create([
                    'syllabus_id' => $syllabus->id,
                    'scope' => $policy['scope'],
                    'content' => $this->promoteRichText($policy['content']),
                    'order' => $index + 1,
                ]);
            }

            foreach ($syllabus->learningOutcomes()->get() as $existing) {
                $learningOutcomeService->delete($existing->id);
            }
            $learningOutcomeIdsByIndex = [];
            foreach (array_values($this->learningOutcomes) as $index => $lo) {
                $created = $learningOutcomeService->create([
                    'syllabus_id' => $syllabus->id,
                    'code' => $lo['code'],
                    'description' => $this->promoteRichText($lo['description']),
                    'order' => $index + 1,
                ]);
                $learningOutcomeIdsByIndex[$index] = $created->id;
            }

            foreach ($syllabus->rubricProficiencyLevels()->get() as $existing) {
                $proficiencyLevelService->delete($existing->id);
            }
            $proficiencyLevelIdsByIndex = [];
            foreach (array_values($this->rubricProficiencyLevels) as $index => $level) {
                $created = $proficiencyLevelService->create([
                    'syllabus_id' => $syllabus->id,
                    'label' => $level['label'],
                    'score_min' => (int) $level['score_min'],
                    'score_max' => (int) $level['score_max'],
                    'order' => $index + 1,
                ]);
                $proficiencyLevelIdsByIndex[$index] = $created->id;
            }

            foreach ($syllabus->evaluations()->get() as $existing) {
                $evaluationService->delete($existing->id);
            }
            foreach (array_values($this->evaluations) as $groupIndex => $group) {
                $evaluation = $evaluationService->create([
                    'syllabus_id' => $syllabus->id,
                    'class_type' => $group['class_type'],
                    'order' => $groupIndex + 1,
                ]);

                foreach (array_values($group['activities']) as $activityIndex => $activity) {
                    $createdActivity = $evaluationActivityService->create([
                        'syllabus_evaluation_id' => $evaluation->id,
                        'activity' => $activity['activity'],
                        'weight' => $activity['weight'],
                        'order' => $activityIndex + 1,
                    ]);

                    $resolvedLoIds = collect($activity['learning_outcome_indices'])
                        ->map(fn ($index) => $learningOutcomeIdsByIndex[(int) $index] ?? null)
                        ->filter()
                        ->values()
                        ->all();

                    $evaluationActivityService->syncLearningOutcomes($createdActivity->id, $resolvedLoIds);
                }
            }

            $keyIndicatorIdsByIndex = [];
            foreach (array_values($this->rubricKeyIndicators) as $index => $keyIndicator) {
                $learningOutcomeId = $learningOutcomeIdsByIndex[(int) $keyIndicator['learning_outcome_index']] ?? null;

                if ($learningOutcomeId === null) {
                    continue;
                }

                $created = $keyIndicatorService->create([
                    'learning_outcome_id' => $learningOutcomeId,
                    'code' => $keyIndicator['code'],
                    'description' => $keyIndicator['description'],
                    'order' => $index + 1,
                ]);
                $keyIndicatorIdsByIndex[$index] = $created->id;
            }

            foreach ($this->rubricCells as $keyIndicatorIndex => $row) {
                $keyIndicatorId = $keyIndicatorIdsByIndex[(int) $keyIndicatorIndex] ?? null;

                if ($keyIndicatorId === null) {
                    continue;
                }

                foreach ($row as $proficiencyLevelIndex => $description) {
                    $proficiencyLevelId = $proficiencyLevelIdsByIndex[(int) $proficiencyLevelIndex] ?? null;

                    if ($proficiencyLevelId === null || trim((string) $description) === '') {
                        continue;
                    }

                    $rubricCellService->create([
                        'rubric_key_indicator_id' => $keyIndicatorId,
                        'rubric_proficiency_level_id' => $proficiencyLevelId,
                        'description' => $this->promoteRichText($description),
                    ]);
                }
            }
        });

        $this->dispatch('syllabus-updated');

        return $this->redirect(route('syllabus.index', $this->course));
    }

    public function render(SyllabusService $syllabusService, CoursePersonService $coursePersonService)
    {
        if (! $this->syllabusLoaded) {
            return view('livewire.courses.syllabus-index-placeholder', [
                'course' => $this->course,
                'isStudent' => $this->isStudent,
                'courseTabs' => CourseTabs::build($this->course, 'syllabus'),
                'teacher' => $this->isStudent
                    ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                    : null,
            ])
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        if ($this->editing) {
            return view('livewire.courses.syllabus-form', [
                'pageTitle' => $this->editingSyllabus ? 'Edit Syllabus' : 'Syllabus',
                'course' => $this->course,
                'courseTabs' => CourseTabs::build($this->course, 'syllabus'),
                'syllabus' => $this->editingSyllabus,
                'policyScopes' => SyllabusPolicyScope::cases(),
            ])
                ->extends('layouts.app', ['topbarTitle' => $this->editingSyllabus ? 'Edit Syllabus' : 'Syllabus'])
                ->section('app-content');
        }

        $syllabus = $syllabusService->findByCourse($this->course->id, [
            'classPolicies',
            'learningOutcomes.rubricKeyIndicators.cells.proficiencyLevel',
            'evaluations.activities.learningOutcomes',
            'rubricProficiencyLevels',
        ]);

        $classPoliciesByScope = collect(SyllabusPolicyScope::cases())->mapWithKeys(
            fn (SyllabusPolicyScope $scope) => [$scope->value => $syllabus?->classPolicies->where('scope', $scope) ?? collect()]
        );

        $evaluationsWithTotals = ($syllabus !== null ? $syllabus->evaluations : collect())->map(fn ($evaluation) => [
            'evaluation' => $evaluation,
            'totalWeight' => (float) $evaluation->activities->sum('weight'),
        ]);

        $viewData = [
            'course' => $this->course,
            'syllabus' => $syllabus,
            'canEdit' => auth()->user()->can('syllabus.edit'),
            'courseTabs' => CourseTabs::build($this->course, 'syllabus'),
            'classPoliciesByScope' => $classPoliciesByScope,
            'evaluationsWithTotals' => $evaluationsWithTotals,
            'teacher' => null,
        ];

        if ($this->isStudent) {
            $viewData['teacher'] = $coursePersonService->teachersForCourse($this->course->id)->first()?->user;
        }

        return view($this->isStudent ? 'livewire.courses.syllabus-index-student' : 'livewire.courses.syllabus-index', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
