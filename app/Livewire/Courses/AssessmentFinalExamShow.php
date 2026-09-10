<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\MaterialType;
use App\Enums\ProctorReviewDecision;
use App\Enums\RoleName;
use App\Livewire\Concerns\WithRichTextEditor;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\ExamReferenceFile;
use App\Services\AssessmentAnswerService;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentScoreService;
use App\Services\CoursePersonService;
use App\Services\ExamReferenceFileService;
use App\Services\FinalExamService;
use App\Services\ProctorSessionService;
use App\Services\R2StorageService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithPagination;

class AssessmentFinalExamShow extends Component
{
    use WithPagination, WithRichTextEditor;

    public Course $course;

    public Assessment $assessment;

    public bool $isStudent = false;

    public string $answerText = '';

    public int $perPage = 12;

    public int $questionsPerPage = 5;

    public string $studentSearch = '';

    public string $submissionFilter = '';

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public ?string $referenceFileError = null;

    public function mount(CurrentSchool $currentSchool, CoursePersonService $coursePersonService, ?Course $course = null, ?Assessment $assessment = null): void
    {
        abort_if($assessment === null, 404);

        $course ??= $assessment->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.view') && $course->school_id === $schoolId, 403);
        abort_unless($assessment->course_id === $course->id, 404);
        abort_unless($assessment->type === AssessmentType::TheoryFinalExam, 404);

        $this->isStudent = auth()->user()->hasRole(RoleName::Student);

        if ($this->isStudent) {
            abort_unless($coursePersonService->isEnrolledAsStudent($course->id, auth()->id()), 403);
        }

        $this->course = $course;
        $this->assessment = $assessment;
        $this->successMessage = session('successMessage');
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['perPage', 'studentSearch', 'submissionFilter'], true)) {
            $this->resetPage();
        }

        if ($property === 'questionsPerPage') {
            $this->resetPage('questionsPage');
        }
    }

    /**
     * @param  array{attempt: mixed, score: mixed}  $row
     */
    private function submissionStatus(array $row): string
    {
        if (! $row['attempt']) {
            return 'not_submitted';
        }

        return $row['score'] ? 'graded' : 'submitted';
    }

    public function submit(AssessmentAttemptService $assessmentAttemptService, AssessmentAnswerService $assessmentAnswerService): bool
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $this->validate([
            'answerText' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                if (trim(strip_tags($value)) === '') {
                    $fail(__('Answer cannot be empty.'));
                }
            }],
        ]);

        $previousAttempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $latest = $previousAttempts->last();

        if ($latest && $latest->score) {
            $this->errorMessage = __('This final exam has already been graded and can no longer be resubmitted.');

            return false;
        }

        if ($this->assessment->end_date && now()->greaterThan($this->assessment->end_date)) {
            $this->errorMessage = __('The submission window for this final exam has closed.');

            return false;
        }

        $attempt = $assessmentAttemptService->create([
            'assessment_id' => $this->assessment->id,
            'user_id' => auth()->id(),
            'submitted_by' => auth()->id(),
            'attempt_number' => $latest ? $latest->attempt_number + 1 : 1,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $assessmentAnswerService->create([
            'assessment_attempt_id' => $attempt->id,
            'answer_text' => HtmlSanitizer::forum($this->promoteRichTextAttachments($this->answerText)),
        ]);

        $this->answerText = '';
        $this->successMessage = __('Your submission has been recorded.');

        return true;
    }

    public function clearSuccessMessage(): void
    {
        $this->successMessage = null;
    }

    private function assertOpenBook(FinalExamService $finalExamService): void
    {
        $finalExam = $finalExamService->findByAssessment($this->assessment->id);
        abort_unless($finalExam?->exam_type === FinalExamType::OpenBook, 403);
    }

    private function assertNotInProgress(AssessmentAttemptService $assessmentAttemptService): void
    {
        $latest = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id())->last();

        abort_if($latest !== null && $latest->submitted_at === null, 403, 'Reference files cannot be changed while the exam is in progress.');
    }

    /**
     * @return array<string, string>
     */
    private function referenceExtensionToTypeMap(): array
    {
        $map = [];
        foreach ([MaterialType::Image, MaterialType::PDF] as $type) {
            foreach ($type->allowedExtensions() as $extension) {
                $map[$extension] = $type->value;
            }
        }

        return $map;
    }

    /**
     * Renderless: this is fired for every file the student picks, often
     * several in flight at once. A normal render here would morph the DOM
     * mid-upload, which can visibly disturb the Alpine-rendered progress
     * grid (tiles flickering) while requests are still in flight.
     *
     * @return array{url?: string, key?: string, error?: string}
     */
    #[Renderless]
    public function generateReferenceFileUploadUrl(string $filename, string $materialType, FinalExamService $finalExamService, ExamReferenceFileService $examReferenceFileService, AssessmentAttemptService $assessmentAttemptService): array
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);
        $this->assertOpenBook($finalExamService);
        $this->assertNotInProgress($assessmentAttemptService);

        if (! in_array($materialType, [MaterialType::Image->value, MaterialType::PDF->value], true)) {
            return ['error' => 'Only image and PDF files are allowed for exam materials.'];
        }

        try {
            return $examReferenceFileService->generatePresignedUploadUrl($this->assessment->id, auth()->id(), $filename, $materialType);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @return array{id: string, title: string, type: string, icon: string, url: string|null, extension: string|null}
     */
    private function toReferenceFilePayload(ExamReferenceFile $file): array
    {
        return [
            'id' => (string) $file->id,
            'title' => $file->title,
            'type' => $file->type->value,
            'icon' => $file->type->icon(),
            'url' => $file->file_url,
            'extension' => $file->file_path ? strtolower(pathinfo($file->file_path, PATHINFO_EXTENSION)) : null,
        ];
    }

    /**
     * Renderless — see generateReferenceFileUploadUrl().
     *
     * @param  array<string, mixed>  $data
     * @return array{error?: string, file?: array{id: string, title: string, type: string, icon: string, url: string|null, extension: string|null}}
     */
    #[Renderless]
    public function finalizeReferenceFileUpload(array $data, FinalExamService $finalExamService, ExamReferenceFileService $examReferenceFileService, AssessmentAttemptService $assessmentAttemptService): array
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);
        $this->assertOpenBook($finalExamService);
        $this->assertNotInProgress($assessmentAttemptService);

        try {
            $file = $examReferenceFileService->finalizeUpload($this->assessment->id, auth()->id(), $data);
            $this->referenceFileError = null;

            return ['file' => $this->toReferenceFilePayload($file)];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Renderless — see generateReferenceFileUploadUrl(). The deleted tile is
     * already removed client-side once this resolves, so a render here
     * would only risk morphing the rest of the upload grid mid-interaction.
     */
    #[Renderless]
    public function deleteReferenceFile(string $id, ExamReferenceFileService $examReferenceFileService, AssessmentAttemptService $assessmentAttemptService): void
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);
        $this->assertNotInProgress($assessmentAttemptService);

        $examReferenceFileService->delete($id, auth()->id());
    }

    /**
     * Renderless — see generateReferenceFileUploadUrl(). The grid is cleared
     * client-side once this resolves.
     */
    #[Renderless]
    public function deleteAllReferenceFiles(ExamReferenceFileService $examReferenceFileService, AssessmentAttemptService $assessmentAttemptService): void
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);
        $this->assertNotInProgress($assessmentAttemptService);

        $examReferenceFileService->deleteAllForAssessmentAndUser($this->assessment->id, auth()->id());
    }

    /**
     * Dev-only convenience for re-testing the exam flow without a database
     * reset: wipes a student's attempt(s) for this final exam, including
     * their R2 proctor recordings/screenshots, so they show as not
     * submitted again. FK cascadeOnDelete on assessment_attempts takes
     * care of scores, answers, question answers/scores, and proctor
     * sessions/events/snapshots.
     */
    public function resetStudentExam(string $userId, AssessmentAttemptService $assessmentAttemptService, R2StorageService $r2StorageService): void
    {
        abort_unless(app()->isLocal(), 404);
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $userId);

        foreach ($attempts as $attempt) {
            $attempt->loadMissing('proctorSession.snapshots');

            $attempt->proctorSession?->snapshots->each(
                fn ($snapshot) => $r2StorageService->delete($snapshot->file_url)
            );

            $assessmentAttemptService->delete($attempt->id);
        }

        $this->successMessage = __('Exam attempt reset for this student.');
    }

    public function render(
        CoursePersonService $coursePersonService,
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentAnswerService $assessmentAnswerService,
        AssessmentScoreService $assessmentScoreService,
        FinalExamService $finalExamService,
        ProctorSessionService $proctorSessionService,
        ExamReferenceFileService $examReferenceFileService,
    ) {
        $isExpired = $this->assessment->end_date && $this->assessment->end_date->isPast();
        $finalExam = $finalExamService->findByAssessment($this->assessment->id);
        $isProctored = $finalExam && in_array($finalExam->exam_type->value, ['open_book', 'closed_book']);
        $isOpenBook = $finalExam?->exam_type === FinalExamType::OpenBook;

        $viewData = [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'finalExam' => $finalExam,
            'isProctored' => $isProctored,
            'isOpenBook' => $isOpenBook,
            'isStudent' => $this->isStudent,
            'canGrade' => auth()->user()->can('assessment.grade'),
            'canSubmit' => auth()->user()->can('assessment.submit'),
            'canEdit' => auth()->user()->can('assessment.edit'),
            'isLocal' => app()->isLocal(),
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
            'isExpired' => $isExpired,
            'referenceFiles' => [],
            'referenceExtensionTypeMap' => [],
            'referenceAcceptedExtensions' => '',
            'referenceFileError' => $this->referenceFileError,
        ];

        if (! $this->isStudent) {
            $questions = $this->assessment->questions->loadMissing('options');
            $questionsPage = $this->getPage('questionsPage');

            $viewData['paginatedQuestions'] = new LengthAwarePaginator(
                $questions->forPage($questionsPage, $this->questionsPerPage)->values(),
                $questions->count(),
                $this->questionsPerPage,
                $questionsPage,
                ['path' => request()->url(), 'pageName' => 'questionsPage']
            );
        }

        if ($this->isStudent) {
            $allAttempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
            $latest = $allAttempts->last();

            $isInProgress = $latest !== null && $latest->submitted_at === null;

            $isTakeHome = $finalExam?->exam_type === FinalExamType::TakeHome;
            $attemptLimit = $isTakeHome ? null : $this->assessment->attempt_limit;
            $attemptsUsed = $allAttempts->count();
            $canResubmit = ! $latest?->score && (! $this->assessment->end_date || now()->lessThanOrEqualTo($this->assessment->end_date));
            if ($attemptLimit && $attemptsUsed >= $attemptLimit && ! $isInProgress) {
                $canResubmit = false;
            }

            $latestScore = $latest ? $assessmentScoreService->findByAttempt($latest->id) : null;
            $latestAnswer = $latest ? $assessmentAnswerService->findByAttempt($latest->id) : null;
            $latestProctorSession = ($isProctored && $latest) ? $proctorSessionService->findByAttempt($latest->id) : null;

            $viewData['latestAttempt'] = $latest;
            $viewData['latestScore'] = $latestScore;
            $viewData['latestAnswer'] = $latestAnswer;
            $viewData['latestProctorSession'] = $latestProctorSession;
            $viewData['pendingProctorReview'] = $latestProctorSession !== null && $latestProctorSession->reviewed_at === null && ! $isInProgress;
            $viewData['isDisqualified'] = $latestProctorSession?->review_decision === ProctorReviewDecision::Disqualified;
            $viewData['canResubmit'] = $canResubmit;
            $viewData['isInProgress'] = $isInProgress;
            $viewData['attemptLimit'] = $attemptLimit ? (string) $attemptLimit : 'Unlimited';
            $viewData['attemptsUsed'] = $attemptsUsed;

            if ($isOpenBook) {
                $extensionTypeMap = $this->referenceExtensionToTypeMap();

                $viewData['referenceFiles'] = $examReferenceFileService->forAssessmentAndUser($this->assessment->id, auth()->id())
                    ->map(fn (ExamReferenceFile $file) => $this->toReferenceFilePayload($file))
                    ->values()
                    ->all();
                $viewData['referenceExtensionTypeMap'] = $extensionTypeMap;
                $viewData['referenceAcceptedExtensions'] = implode(',', array_map(fn (string $ext) => ".{$ext}", array_keys($extensionTypeMap)));
            }
        } else {
            $students = $coursePersonService->studentsForCourse($this->course->id);

            $search = trim($this->studentSearch);
            if ($search !== '') {
                $students = $students->filter(
                    fn ($coursePerson) => str_contains(strtolower($coursePerson->user->name), strtolower($search))
                )->values();
            }

            $rows = $students->map(function ($coursePerson) use ($assessmentAttemptService, $assessmentAnswerService, $assessmentScoreService, $proctorSessionService, $isProctored) {
                $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $coursePerson->user_id);
                $latest = $attempts->last();
                $score = $latest ? $assessmentScoreService->findByAttempt($latest->id) : null;
                $answer = $latest ? $assessmentAnswerService->findByAttempt($latest->id) : null;
                $proctorSession = ($isProctored && $latest)
                    ? $proctorSessionService->findByAttempt($latest->id)
                    : null;

                $isInProgress = $latest !== null && $latest->submitted_at === null;
                $pendingProctorReview = $proctorSession !== null && $proctorSession->reviewed_at === null && ! $isInProgress;

                return [
                    'user' => $coursePerson->user,
                    'attempt' => $latest,
                    'answer' => $answer,
                    'score' => $score,
                    'pendingProctorReview' => $pendingProctorReview,
                ];
            })->values();

            if ($this->submissionFilter !== '') {
                $rows = $rows->filter(fn (array $row) => $this->submissionStatus($row) === $this->submissionFilter)->values();
            }

            $page = $this->getPage();

            $viewData['studentRows'] = new LengthAwarePaginator(
                $rows->forPage($page, $this->perPage)->values(),
                $rows->count(),
                $this->perPage,
                $page,
                ['path' => request()->url(), 'pageName' => 'page']
            );
        }

        return view('livewire.courses.assessment-final-exam-show', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
