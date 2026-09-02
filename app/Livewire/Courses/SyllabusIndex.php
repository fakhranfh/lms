<?php

namespace App\Livewire\Courses;

use App\Enums\MaterialType;
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
use Illuminate\Support\Str;
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

    /** @var array<string, array<int, string>> */
    public array $selectedMaterialIds = [];

    public string $materialSearch = '';

    public string $activeSection = 'course_description';

    public function mount(CurrentSchool $currentSchool, Course $course, SyllabusService $syllabusService, bool $startInEditMode = false): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('syllabus.view') && $course->school_id === $schoolId, 403);

        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);

        foreach (SyllabusMaterialSection::cases() as $section) {
            $this->selectedMaterialIds[$section->value] = [];
        }

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

    /**
     * Dev-only: fills the form with fake data (including attached materials)
     * so the UI can be exercised without manually typing every field.
     */
    public function devAutofill(MediaLibraryService $mediaLibraryService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('syllabus.edit'), 403);

        $this->courseDescription = '<p>This course introduces students to the core concepts, tools, and practices of the subject, '
            .'combining lectures, hands-on exercises, and real-world case studies to build both theoretical understanding and '
            .'practical skill.</p>';

        $policyContent = [
            'f2f_video' => 'Attendance is mandatory for all face-to-face and video conference sessions. Students arriving more than 15 minutes late will be marked absent.',
            'online' => 'Online session materials must be reviewed before the scheduled class. Cameras should remain on during discussions unless prior arrangements are made.',
            'general' => 'Academic honesty is expected at all times. Any form of plagiarism or cheating will result in disciplinary action per institutional policy.',
        ];

        $this->classPolicies = collect(SyllabusPolicyScope::cases())->values()->map(fn ($scope, $index) => [
            'scope' => $scope->value,
            'content' => '<p>'.$policyContent[$scope->value].'</p>',
            'order' => $index + 1,
        ])->all();

        $this->submissionAndCollection = '<p>All assignments must be submitted through the course portal before the stated deadline. '
            .'Late submissions will be penalized 10% per day unless an extension has been approved in advance.</p>';
        $this->tutorialActivityPlan = '<p>Each tutorial session begins with a short recap of the previous lecture, followed by guided '
            .'problem-solving in small groups and a class-wide discussion of solutions.</p>';

        $learningOutcomeContent = [
            'Explain the fundamental concepts and terminology covered in this course.',
            'Apply core techniques to solve practical, real-world problems.',
            'Evaluate different approaches and justify the choice of method for a given scenario.',
        ];

        $this->learningOutcomes = collect(range(1, 3))->map(fn ($index) => [
            'code' => 'LO'.$index,
            'description' => '<p>'.$learningOutcomeContent[$index - 1].'</p>',
            'order' => $index,
        ])->all();

        $this->evaluations = [[
            'class_type' => 'Quiz',
            'activities' => [
                ['activity' => 'Quiz 1', 'weight' => '50', 'order' => 1, 'learning_outcome_indices' => [0]],
                ['activity' => 'Quiz 2', 'weight' => '50', 'order' => 2, 'learning_outcome_indices' => [1, 2]],
            ],
        ]];

        $this->rubricProficiencyLevels = [
            ['label' => 'Excellent', 'score_min' => '80', 'score_max' => '100', 'order' => 1],
            ['label' => 'Good', 'score_min' => '60', 'score_max' => '79', 'order' => 2],
            ['label' => 'Needs Improvement', 'score_min' => '0', 'score_max' => '59', 'order' => 3],
        ];

        $keyIndicatorContent = [
            'Correctly defines and explains key terminology.',
            'Applies the appropriate technique to solve the given problem.',
            'Justifies the chosen approach with sound reasoning.',
        ];

        $this->rubricKeyIndicators = collect(range(1, 3))->map(fn ($index) => [
            'learning_outcome_index' => (string) ($index - 1),
            'code' => '1.'.$index,
            'description' => $keyIndicatorContent[$index - 1],
            'order' => $index,
        ])->all();

        $rubricCellContent = [
            'Consistently meets this indicator with clear, well-organized work.',
            'Mostly meets this indicator with minor gaps.',
            'Rarely meets this indicator; significant gaps remain.',
        ];

        $this->rubricCells = collect(range(0, 2))->mapWithKeys(fn ($kiIndex) => [
            $kiIndex => collect(range(0, 2))->mapWithKeys(fn ($plIndex) => [
                $plIndex => '<p>'.$rubricCellContent[$plIndex].'</p>',
            ])->all(),
        ])->all();

        $this->teachingLearningStrategies = '<p>This course uses a blended approach combining interactive lectures, collaborative '
            .'group work, and self-paced online modules to accommodate different learning styles.</p>';
        $this->textbooks = '<p>Primary textbook to be announced by the instructor at the start of the term. Supplementary readings '
            .'will be provided through the course portal.</p>';
        $this->competencyMap = '<p>This course contributes to the program\'s core competencies in analytical thinking, technical '
            .'proficiency, and effective communication.</p>';
        $this->videoOverview = '<p>A short video introducing the course goals, structure, and instructor will be shared before the '
            .'first session.</p>';

        $this->devAutofillMaterials($mediaLibraryService);

        // Rich-text editors run wire:ignore, so their DOM is silent to property
        // changes; they only refresh when told to via this browser event.
        $this->dispatch('rich-text-set-content', id: 'course-description', value: $this->courseDescription);
        foreach ($this->classPolicies as $index => $policy) {
            $this->dispatch('rich-text-set-content', id: "class-policy-{$index}", value: $policy['content']);
        }
        $this->dispatch('rich-text-set-content', id: 'submission-and-collection', value: $this->submissionAndCollection);
        $this->dispatch('rich-text-set-content', id: 'tutorial-activity-plan', value: $this->tutorialActivityPlan);
        foreach ($this->learningOutcomes as $index => $lo) {
            $this->dispatch('rich-text-set-content', id: "learning-outcome-{$index}", value: $lo['description']);
        }
        foreach ($this->rubricCells as $kiIndex => $row) {
            foreach ($row as $plIndex => $description) {
                $this->dispatch('rich-text-set-content', id: "rubric-cell-{$kiIndex}-{$plIndex}", value: $description);
            }
        }
        $this->dispatch('rich-text-set-content', id: 'teaching-learning-strategies', value: $this->teachingLearningStrategies);
        $this->dispatch('rich-text-set-content', id: 'textbooks', value: $this->textbooks);
        $this->dispatch('rich-text-set-content', id: 'competency-map', value: $this->competencyMap);
        $this->dispatch('rich-text-set-content', id: 'video-overview', value: $this->videoOverview);
    }

    private function devAutofillMaterials(MediaLibraryService $mediaLibraryService): void
    {
        $sections = SyllabusMaterialSection::cases();

        // Every material input gets its own attached file, so existing media
        // is topped up with freshly generated dummy PDFs when there aren't
        // enough items to cover all sections.
        $items = $mediaLibraryService->list($this->course->school_id)->limit(count($sections))->get()->values();

        while ($items->count() < count($sections)) {
            $items->push($this->devGenerateDummyMaterial($mediaLibraryService, $sections[$items->count()]->name));
        }

        foreach ($sections as $index => $section) {
            $item = $items[$index];
            $this->selectedMaterialIds[$section->value][] = (string) $item->id;

            // The picker's Alpine state is seeded once at mount and untouched
            // by further Livewire morphs, so it needs telling explicitly to
            // reflect the material just attached above.
            $this->dispatch('syllabus-material-set', section: $section->value, items: [[
                'id' => (string) $item->id,
                'title' => $item->title,
                'type' => $item->type->value,
            ]]);
        }
    }

    private function devGenerateDummyMaterial(MediaLibraryService $mediaLibraryService, string $label): MediaLibraryItem
    {
        $content = "%PDF-1.4\n"
            .'1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj'."\n"
            .'2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj'."\n"
            .'3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]/Resources<<>>/Contents 4 0 R>>endobj'."\n"
            .'4 0 obj<</Length 44>>stream'."\n"
            .'BT /F1 18 Tf 20 100 Td (Syllabus Material) Tj ET'
            ."\nendstream endobj\n"
            .'trailer<</Size 5/Root 1 0 R>>'."\n"
            .'%%EOF';

        $key = "media/{$this->course->school_id}/dev-generated/syllabus-".Str::uuid().'.pdf';

        return $mediaLibraryService->createFromRawContent(
            $this->course->school_id,
            auth()->id(),
            $key,
            $content,
            'application/pdf',
            [
                'type' => MaterialType::PDF->value,
                'title' => "Material - {$label}",
                'description' => 'Dev-generated dummy PDF material.',
            ],
        );
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

        $this->dispatch('syllabus-updated');

        return $this->redirect(route('syllabus.index', $this->course));
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
