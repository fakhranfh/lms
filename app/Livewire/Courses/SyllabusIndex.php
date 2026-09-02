<?php

namespace App\Livewire\Courses;

use App\Enums\RoleName;
use App\Enums\SyllabusMaterialSection;
use App\Enums\SyllabusPolicyScope;
use App\Models\Course;
use App\Models\MediaLibraryItem;
use App\Models\Syllabus;
use App\Services\CoursePersonService;
use App\Services\MediaLibraryService;
use App\Services\SyllabusClassPolicyService;
use App\Services\SyllabusEvaluationActivityService;
use App\Services\SyllabusEvaluationService;
use App\Services\SyllabusLearningOutcomeService;
use App\Services\SyllabusRubricCellService;
use App\Services\SyllabusRubricKeyIndicatorService;
use App\Services\SyllabusRubricProficiencyLevelService;
use App\Services\SyllabusService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SyllabusIndex extends Component
{
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

    /** @var array<int, array{scope: string, content: string, order: int}> */
    public array $classPolicies = [];

    #[Validate('nullable|string')]
    public string $submissionAndCollection = '';

    #[Validate('nullable|string')]
    public string $tutorialActivityPlan = '';

    /** @var array<int, array{code: string, description: string, order: int}> */
    public array $learningOutcomes = [];

    /**
     * @var array<int, array{class_type: string, activities: array<int, array{activity: string, weight: string, order: int, learning_outcome_indices: array<int, int>}>}>
     */
    public array $evaluations = [];

    /** @var array<int, array{label: string, score_min: string, score_max: string, order: int}> */
    public array $rubricProficiencyLevels = [];

    /** @var array<int, array{learning_outcome_index: string, code: string, description: string, order: int}> */
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

    /** @var array<string, array<int, string>> */
    public array $selectedMaterialIds = [];

    public string $materialSearch = '';

    public string $activeSection = 'course_description';

    public function mount(CurrentSchool $currentSchool, Course $course): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('syllabus.view') && $course->school_id === $schoolId, 403);

        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);

        foreach (SyllabusMaterialSection::cases() as $section) {
            $this->selectedMaterialIds[$section->value] = [];
        }
    }

    public function loadSyllabus(SyllabusService $syllabusService): void
    {
        $this->syllabusLoaded = true;

        if (auth()->user()->can('syllabus.edit') && ! $syllabusService->findByCourse($this->course->id)) {
            $this->editing = true;
        }
    }

    public function edit(SyllabusService $syllabusService): void
    {
        abort_unless(auth()->user()->can('syllabus.edit'), 403);

        $this->loadFormData($syllabusService);
        $this->syllabusLoaded = true;
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
        $this->resetErrorBag();
    }

    private function loadFormData(SyllabusService $syllabusService): void
    {
        foreach (SyllabusMaterialSection::cases() as $section) {
            $this->selectedMaterialIds[$section->value] = [];
        }

        $syllabus = $syllabusService->findByCourse($this->course->id, [
            'classPolicies',
            'learningOutcomes.rubricKeyIndicators.cells',
            'evaluations.activities.learningOutcomes',
            'rubricProficiencyLevels',
            'materials',
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

        $this->selectedMaterialIds = $syllabus->materials->groupBy('pivot.section')
            ->map(fn ($items) => $items->sortBy('pivot.order')->pluck('id')->map(fn ($id) => (string) $id)->values()->all())
            ->toArray();

        foreach (SyllabusMaterialSection::cases() as $section) {
            $this->selectedMaterialIds[$section->value] ??= [];
        }
    }

    public function addClassPolicy(string $scope = 'general'): void
    {
        $this->classPolicies[] = [
            'scope' => $scope,
            'content' => '',
            'order' => count($this->classPolicies) + 1,
        ];
    }

    public function removeClassPolicy(int $index): void
    {
        unset($this->classPolicies[$index]);
        $this->classPolicies = array_values($this->classPolicies);
    }

    public function addLearningOutcome(): void
    {
        $this->learningOutcomes[] = [
            'code' => '',
            'description' => '',
            'order' => count($this->learningOutcomes) + 1,
        ];
    }

    public function removeLearningOutcome(int $index): void
    {
        unset($this->learningOutcomes[$index]);
        $this->learningOutcomes = array_values($this->learningOutcomes);
    }

    public function addEvaluationGroup(): void
    {
        $this->evaluations[] = [
            'class_type' => '',
            'activities' => [],
        ];
    }

    public function removeEvaluationGroup(int $index): void
    {
        unset($this->evaluations[$index]);
        $this->evaluations = array_values($this->evaluations);
    }

    public function addEvaluationActivity(int $groupIndex): void
    {
        $this->evaluations[$groupIndex]['activities'][] = [
            'activity' => '',
            'weight' => '',
            'order' => count($this->evaluations[$groupIndex]['activities']) + 1,
            'learning_outcome_indices' => [],
        ];
    }

    public function removeEvaluationActivity(int $groupIndex, int $activityIndex): void
    {
        unset($this->evaluations[$groupIndex]['activities'][$activityIndex]);
        $this->evaluations[$groupIndex]['activities'] = array_values($this->evaluations[$groupIndex]['activities']);
    }

    public function addProficiencyLevel(): void
    {
        $this->rubricProficiencyLevels[] = [
            'label' => '',
            'score_min' => '',
            'score_max' => '',
            'order' => count($this->rubricProficiencyLevels) + 1,
        ];
    }

    public function removeProficiencyLevel(int $index): void
    {
        unset($this->rubricProficiencyLevels[$index]);
        $this->rubricProficiencyLevels = array_values($this->rubricProficiencyLevels);
    }

    public function addKeyIndicator(): void
    {
        $this->rubricKeyIndicators[] = [
            'learning_outcome_index' => '',
            'code' => '',
            'description' => '',
            'order' => count($this->rubricKeyIndicators) + 1,
        ];
    }

    public function removeKeyIndicator(int $index): void
    {
        unset($this->rubricKeyIndicators[$index]);
        $this->rubricKeyIndicators = array_values($this->rubricKeyIndicators);

        unset($this->rubricCells[$index]);
        $this->rubricCells = array_values($this->rubricCells);
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
    ): void {
        abort_unless(auth()->user()->can('syllabus.edit'), 403);

        $this->validate();
        $this->runCustomValidation();

        $data = [
            'course_id' => $this->course->id,
            'course_description' => $this->courseDescription ?: null,
            'submission_and_collection' => $this->submissionAndCollection ?: null,
            'tutorial_activity_plan' => $this->tutorialActivityPlan ?: null,
            'teaching_learning_strategies' => $this->teachingLearningStrategies ?: null,
            'textbooks' => $this->textbooks ?: null,
            'competency_map' => $this->competencyMap ?: null,
            'video_overview' => $this->videoOverview ?: null,
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
            $syllabusService,
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
                    'content' => $policy['content'],
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
                    'description' => $lo['description'],
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
                        'description' => $description,
                    ]);
                }
            }

            $syllabusService->replaceMaterials($syllabus->id, $this->selectedMaterialIds);
        });

        $this->editing = false;

        $this->dispatch('syllabus-updated');
    }

    public function render(SyllabusService $syllabusService, CoursePersonService $coursePersonService, MediaLibraryService $mediaLibraryService)
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
            $schoolId = $this->course->school_id;

            return view('livewire.courses.syllabus-form', [
                'pageTitle' => $this->editingSyllabus ? 'Edit Syllabus' : 'Syllabus',
                'course' => $this->course,
                'courseTabs' => CourseTabs::build($this->course, 'syllabus'),
                'syllabus' => $this->editingSyllabus,
                'policyScopes' => SyllabusPolicyScope::cases(),
                'materialSections' => SyllabusMaterialSection::cases(),
                'mediaItems' => Collection::make($mediaLibraryService->list($schoolId, null, $this->materialSearch ?: null)->get()),
                'selectedMediaItemsBySection' => collect($this->selectedMaterialIds)->mapWithKeys(
                    fn ($ids, $section) => [$section => $ids === [] ? new EloquentCollection : MediaLibraryItem::whereIn('id', $ids)->get()]
                ),
            ])
                ->extends('layouts.app', ['topbarTitle' => $this->editingSyllabus ? 'Edit Syllabus' : 'Syllabus'])
                ->section('app-content');
        }

        $syllabus = $syllabusService->findByCourse($this->course->id, [
            'classPolicies',
            'learningOutcomes.rubricKeyIndicators.cells.proficiencyLevel',
            'evaluations.activities.learningOutcomes',
            'rubricProficiencyLevels',
            'materials',
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
