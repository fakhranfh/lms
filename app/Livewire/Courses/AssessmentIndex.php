<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\DeliveryMode;
use App\Enums\FinalExamType;
use App\Enums\ProctorReviewDecision;
use App\Enums\QuizScoringMethod;
use App\Enums\RoleName;
use App\Livewire\Concerns\WithDevMaterialAttachments;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\ForumComment;
use App\Models\ForumThread;
use App\Models\GroupMember;
use App\Models\Session;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuestionAttemptScoringService;
use App\Services\AssessmentQuestionOptionService;
use App\Services\AssessmentQuestionService;
use App\Services\AssessmentService;
use App\Services\AttendanceDerivationService;
use App\Services\AttendanceScoringService;
use App\Services\CoursePersonService;
use App\Services\FinalExamService;
use App\Services\ForumCommentService;
use App\Services\ForumDiscussionScoringService;
use App\Services\ForumThreadService;
use App\Services\GroupMemberService;
use App\Services\GroupService;
use App\Services\MediaLibraryService;
use App\Services\ProctorSessionService;
use App\Services\QuizService;
use App\Services\R2StorageService;
use App\Services\SessionService;
use App\Support\AssessmentTypeLabel;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AssessmentIndex extends Component
{
    use WithDevMaterialAttachments;

    /**
     * Points distribution for the 3 dev-generated questions, summing to 100
     * to satisfy AssessmentForm's total-points validation.
     */
    private const GENERATED_QUESTION_POINTS = [30, 30, 40];

    public Course $course;

    public bool $isStudent = false;

    public bool $assessmentsLoaded = false;

    public ?string $errorMessage = null;

    public array $expandedSections = [];

    public string $generateCount = '5';

    public function mount(CurrentSchool $currentSchool, Course $course): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.view') && $course->school_id === $schoolId, 403);

        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);
    }

    public function loadAssessments(): void
    {
        $this->assessmentsLoaded = true;
    }

    public function toggleSection(string $sectionKey): void
    {
        if (isset($this->expandedSections[$sectionKey])) {
            unset($this->expandedSections[$sectionKey]);
        } else {
            $this->expandedSections[$sectionKey] = true;
        }
    }

    public function deleteAssessment(string $assessmentId, AssessmentService $assessmentService): void
    {
        abort_unless(auth()->user()->can('assessment.delete'), 403);

        $this->errorMessage = null;

        $error = $this->deletableError($assessmentId, $assessmentService);

        if ($error) {
            $this->errorMessage = $error;

            return;
        }

        $assessmentService->delete($assessmentId);
    }

    /**
     * @param  array<int, string>  $assessmentIds
     */
    public function deleteSelected(array $assessmentIds, AssessmentService $assessmentService): void
    {
        abort_unless(auth()->user()->can('assessment.delete'), 403);

        $this->errorMessage = null;

        if (empty($assessmentIds)) {
            return;
        }

        $skipped = 0;

        foreach ($assessmentIds as $assessmentId) {
            if ($this->deletableError($assessmentId, $assessmentService)) {
                $skipped++;

                continue;
            }

            $assessmentService->delete($assessmentId);
        }

        if ($skipped > 0) {
            $this->errorMessage = __('Some selected assessments could not be deleted because they are auto-provisioned or already have submissions.');
        }
    }

    public function publishAssessment(string $assessmentId, AssessmentService $assessmentService): void
    {
        $this->setAssessmentStatus($assessmentId, AssessmentStatus::Published, $assessmentService);
    }

    public function unpublishAssessment(string $assessmentId, AssessmentService $assessmentService): void
    {
        $this->setAssessmentStatus($assessmentId, AssessmentStatus::Draft, $assessmentService);
    }

    private function setAssessmentStatus(string $assessmentId, AssessmentStatus $status, AssessmentService $assessmentService): void
    {
        abort_unless(auth()->user()->can('assessment.edit'), 403);

        $this->errorMessage = null;

        $assessment = $assessmentService->find($assessmentId);

        if (! $assessment || $assessment->course_id !== $this->course->id) {
            $this->errorMessage = __('Assessment not found.');

            return;
        }

        if (! in_array($assessment->type, [AssessmentType::TheoryPersonalAssignment, AssessmentType::TheoryTeamAssignment, AssessmentType::TheoryQuiz, AssessmentType::TheoryFinalExam], true)) {
            $this->errorMessage = __('Only personal assignments, team assignments, quizzes, and final exams can be published or unpublished.');

            return;
        }

        $assessmentService->update($assessmentId, ['status' => $status]);
    }

    public function moveAssessment(string $assessmentId, string $direction, AssessmentService $assessmentService): void
    {
        abort_unless(auth()->user()->can('assessment.edit'), 403);

        if (! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        $assessmentService->moveOrder($assessmentId, $direction);
    }

    /**
     * Persists the drag-and-drop reordering of a type group's assessments.
     *
     * @param  array<int, string>  $orderedIds
     */
    public function reorderAssessments(string $type, array $orderedIds, AssessmentService $assessmentService): void
    {
        abort_unless(auth()->user()->can('assessment.edit'), 403);

        $assessmentService->reorder($this->course->id, $type, $orderedIds);
    }

    private function deletableError(string $assessmentId, AssessmentService $assessmentService): ?string
    {
        $assessment = $assessmentService->find($assessmentId, ['attempts']);

        if (! $assessment || $assessment->course_id !== $this->course->id) {
            return __('Assessment not found.');
        }

        if ($assessment->type === AssessmentType::Attendance) {
            return __('The Attendance assessment is auto-provisioned and cannot be deleted.');
        }

        if ($assessment->type === AssessmentType::ForumDiscussion) {
            return __('The Forum Discussion assessment is auto-provisioned and cannot be deleted.');
        }

        if ($assessment->attempts->isNotEmpty()) {
            return __('This assessment already has submissions and cannot be deleted.');
        }

        return null;
    }

    /**
     * Dev-only: bulk-creates draft personal assignments, each seeded with
     * the same number of questions as devAutofill (see AssessmentForm) so
     * generated rows are realistic, but with its own wording so the two
     * dev tools don't produce identical-looking content.
     */
    public function generatePersonalAssignments(AssessmentService $assessmentService, AssessmentQuestionService $assessmentQuestionService, MediaLibraryService $mediaLibraryService, R2StorageService $r2StorageService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('assessment.create'), 403);

        $this->errorMessage = null;

        $this->validate([
            'generateCount' => 'required|integer|min:1|max:50',
        ]);

        $count = (int) $this->generateCount;

        $questionContent = [
            'Summarize the key takeaway from this week\'s reading and explain why it matters for the course topic.',
            'Identify a potential limitation of the method covered this week and propose how it could be addressed.',
            'Walk through how you would apply this week\'s technique to a problem outside the examples shown in class.',
        ];

        $materialIds = $this->devMaterialIds($this->course->school_id, $mediaLibraryService, $r2StorageService);

        for ($i = 0; $i < $count; $i++) {
            $startDate = now()->addWeeks($i);
            $endDate = $startDate->clone()->addWeek();

            $assessment = $assessmentService->create([
                'course_id' => $this->course->id,
                'session_id' => null,
                'type' => AssessmentType::TheoryPersonalAssignment,
                'title' => AssessmentTypeLabel::forType(AssessmentType::TheoryPersonalAssignment).' - Week '.random_int(1, 14).' Practice',
                'weight' => AssessmentType::TheoryPersonalAssignment->defaultWeight(),
                'assigned_to' => AssessmentAssignedTo::Individual,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => AssessmentStatus::Draft,
            ]);

            foreach ($questionContent as $index => $description) {
                $question = $assessmentQuestionService->create([
                    'assessment_id' => $assessment->id,
                    'description' => '<p>'.$description.'</p>',
                    'points' => self::GENERATED_QUESTION_POINTS[$index],
                    'order' => $index + 1,
                ]);

                $materialSync = [];
                foreach (array_values($materialIds) as $order => $materialId) {
                    $materialSync[$materialId] = ['order' => $order + 1];
                }
                $question->files()->sync($materialSync);
            }
        }
    }

    /**
     * Dev-only: bulk-creates draft team assignments, each backed by a freshly
     * generated group (built from the course's enrolled students) so the
     * assignment has somewhere to attach submissions during testing.
     */
    public function generateTeamAssignments(AssessmentService $assessmentService, AssessmentQuestionService $assessmentQuestionService, MediaLibraryService $mediaLibraryService, R2StorageService $r2StorageService, CoursePersonService $coursePersonService, GroupService $groupService, GroupMemberService $groupMemberService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('assessment.create'), 403);

        $this->errorMessage = null;

        $this->validate([
            'generateCount' => 'required|integer|min:1|max:50',
        ]);

        $count = (int) $this->generateCount;

        $questionContent = [
            'As a team, outline your division of labor for this week\'s project milestone and justify your choices.',
            'Discuss as a group how this week\'s concept could be combined with a topic from an earlier week.',
            'Present a joint critique of the reference solution shown in class, noting where your team would diverge.',
        ];

        $students = $coursePersonService->studentsForCourse($this->course->id)->pluck('user_id')->values();

        if ($students->isEmpty()) {
            $this->errorMessage = __('This course has no enrolled students to form groups with.');

            return;
        }

        $materialIds = $this->devMaterialIds($this->course->school_id, $mediaLibraryService, $r2StorageService);

        for ($i = 0; $i < $count; $i++) {
            $startDate = now()->addWeeks($i);
            $endDate = $startDate->clone()->addWeek();

            $group = $groupService->create([
                'course_id' => $this->course->id,
                'name' => 'Dev Team '.random_int(100, 999),
                'created_by' => auth()->id(),
                'target_size' => min(4, $students->count()),
            ]);

            foreach ($students->random(min(4, $students->count()))->values() as $studentId) {
                $groupMemberService->create([
                    'group_id' => $group->id,
                    'user_id' => $studentId,
                    'joined_at' => now(),
                ]);
            }

            $assessment = $assessmentService->create([
                'course_id' => $this->course->id,
                'session_id' => null,
                'type' => AssessmentType::TheoryTeamAssignment,
                'title' => AssessmentTypeLabel::forType(AssessmentType::TheoryTeamAssignment).' - Week '.random_int(1, 14).' Project',
                'weight' => AssessmentType::TheoryTeamAssignment->defaultWeight(),
                'assigned_to' => AssessmentAssignedTo::Group,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => AssessmentStatus::Draft,
            ]);

            foreach ($questionContent as $index => $description) {
                $question = $assessmentQuestionService->create([
                    'assessment_id' => $assessment->id,
                    'description' => '<p>'.$description.'</p>',
                    'points' => self::GENERATED_QUESTION_POINTS[$index],
                    'order' => $index + 1,
                ]);

                $materialSync = [];
                foreach (array_values($materialIds) as $order => $materialId) {
                    $materialSync[$materialId] = ['order' => $order + 1];
                }
                $question->files()->sync($materialSync);
            }
        }
    }

    /**
     * Dev-only: bulk-creates draft quizzes, cycling through the course's
     * existing sessions (a quiz always needs one), each seeded with 3
     * multiple-choice questions worth the same GENERATED_QUESTION_POINTS
     * distribution used by the other dev generators.
     */
    public function generateQuizzes(AssessmentService $assessmentService, QuizService $quizService, AssessmentQuestionService $assessmentQuestionService, AssessmentQuestionOptionService $assessmentQuestionOptionService, SessionService $sessionService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('assessment.create'), 403);

        $this->errorMessage = null;

        $this->validate([
            'generateCount' => 'required|integer|min:1|max:50',
        ]);

        $count = (int) $this->generateCount;

        $sessions = $sessionService->forCourse($this->course->id);

        if ($sessions->isEmpty()) {
            $this->errorMessage = __('This course has no sessions to attach quizzes to.');

            return;
        }

        $questionContent = [
            ['description' => 'What is the primary purpose of the concept covered this week?', 'options' => ['The correct answer', 'A common misconception', 'An unrelated distractor']],
            ['description' => 'Which of the following best describes the technique discussed in class?', 'options' => ['The correct technique', 'A similar but incorrect technique', 'An unrelated technique']],
            ['description' => 'Given the example from the lecture, what would be the expected outcome?', 'options' => ['The correct outcome', 'A plausible but wrong outcome', 'An unrelated outcome']],
        ];

        // Quiz questions are not individually weighted (see AssessmentQuizForm::equalPoints()).
        $equalPoints = [33.34, 33.33, 33.33];

        for ($i = 0; $i < $count; $i++) {
            $session = $sessions[$i % $sessions->count()];

            $assessment = $assessmentService->create([
                'course_id' => $this->course->id,
                'session_id' => $session->id,
                'type' => AssessmentType::TheoryQuiz,
                'title' => AssessmentTypeLabel::forType(AssessmentType::TheoryQuiz).' - Week '.random_int(1, 14).' Quiz',
                'weight' => AssessmentType::TheoryQuiz->defaultWeight(),
                'start_date' => $session->date_start,
                'end_date' => $session->date_end,
                'status' => AssessmentStatus::Draft,
            ]);

            $quiz = $quizService->create([
                'assessment_id' => $assessment->id,
                'start_date' => $session->date_start,
                'due_date' => $session->date_end,
                'total_question' => count($questionContent),
                'total_attempts' => 3,
                'scoring_method' => QuizScoringMethod::Highest,
                'time_limit_per_attempt' => 30,
            ]);

            foreach ($questionContent as $index => $questionData) {
                $question = $assessmentQuestionService->create([
                    'assessment_id' => $assessment->id,
                    'description' => '<p>'.$questionData['description'].'</p>',
                    'points' => $equalPoints[$index],
                    'question_type' => AssessmentQuestionType::MultipleChoice,
                    'order' => $index + 1,
                ]);

                foreach ($questionData['options'] as $optionIndex => $label) {
                    $assessmentQuestionOptionService->create([
                        'assessment_question_id' => $question->id,
                        'label' => $label,
                        'is_correct' => $optionIndex === 0,
                        'order' => $optionIndex + 1,
                    ]);
                }
            }
        }
    }

    /**
     * Dev-only: bulk-creates draft final exams of one exam type. Open/closed
     * book get the full 15 multiple-choice + 5 essay set; take_home gets the
     * single essay question that shape enforces (see
     * AssessmentFinalExamForm::persist()'s take_home validation). Content is
     * deliberately different wording from AssessmentFinalExamForm::devAutofill()
     * so the two dev tools don't produce identical-looking exams. Doesn't
     * require a period since the Period field was removed from the form/UI.
     */
    public function generateFinalExam(string $examType, AssessmentService $assessmentService, AssessmentQuestionService $assessmentQuestionService, AssessmentQuestionOptionService $assessmentQuestionOptionService, FinalExamService $finalExamService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('assessment.create'), 403);
        abort_unless(in_array($examType, ['open_book', 'closed_book', 'take_home'], true), 422);

        $this->errorMessage = null;

        $this->validate([
            'generateCount' => 'required|integer|min:1|max:50',
        ]);

        $this->generateFinalExamOfType($examType, (int) $this->generateCount, $assessmentService, $assessmentQuestionService, $assessmentQuestionOptionService, $finalExamService);
    }

    /**
     * Dev-only: runs generateFinalExam's three exam types back to back in a
     * single click, instead of clicking each "Generate {Type}" button
     * separately.
     */
    public function generateAllFinalExamTypes(AssessmentService $assessmentService, AssessmentQuestionService $assessmentQuestionService, AssessmentQuestionOptionService $assessmentQuestionOptionService, FinalExamService $finalExamService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('assessment.create'), 403);

        $this->errorMessage = null;

        $this->validate([
            'generateCount' => 'required|integer|min:1|max:50',
        ]);

        $count = (int) $this->generateCount;

        foreach (['open_book', 'closed_book', 'take_home'] as $examType) {
            $this->generateFinalExamOfType($examType, $count, $assessmentService, $assessmentQuestionService, $assessmentQuestionOptionService, $finalExamService);
        }
    }

    private function generateFinalExamOfType(string $examType, int $count, AssessmentService $assessmentService, AssessmentQuestionService $assessmentQuestionService, AssessmentQuestionOptionService $assessmentQuestionOptionService, FinalExamService $finalExamService): void
    {
        $instructions = match ($examType) {
            'open_book' => '<p>You may consult your notes, textbooks, and any written or digital materials while answering. Collaboration with other students is not permitted.</p>',
            'take_home' => '<p>Submit your response before the exam window closes. Cite any external sources you reference in your answer.</p>',
            default => '<p>Complete this exam individually within the allotted time window.</p>',
        };

        if ($examType === 'take_home') {
            $essayPrompt = 'Design a rate-limiting strategy for a public-facing API. Describe the approach you would use, how you would communicate limits to clients, and how you would handle abuse.';

            for ($i = 0; $i < $count; $i++) {
                $startDate = now()->addWeeks($i);
                $endDate = $startDate->clone()->addWeek();

                DB::transaction(function () use ($assessmentService, $assessmentQuestionService, $finalExamService, $instructions, $essayPrompt, $startDate, $endDate): void {
                    $assessment = $assessmentService->create([
                        'course_id' => $this->course->id,
                        'session_id' => null,
                        'type' => AssessmentType::TheoryFinalExam,
                        'title' => AssessmentTypeLabel::forType(AssessmentType::TheoryFinalExam).' - Set '.random_int(1, 99),
                        'weight' => AssessmentType::TheoryFinalExam->defaultWeight(),
                        'assigned_to' => AssessmentAssignedTo::Individual,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'status' => AssessmentStatus::Draft,
                    ]);

                    $finalExamService->create([
                        'assessment_id' => $assessment->id,
                        'exam_type' => FinalExamType::TakeHome,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'instructions' => $instructions,
                    ]);

                    $assessmentQuestionService->create([
                        'assessment_id' => $assessment->id,
                        'description' => '<p>'.$essayPrompt.'</p>',
                        'points' => 100,
                        'question_type' => AssessmentQuestionType::Essay,
                        'order' => 1,
                    ]);
                });
            }

            return;
        }

        $mcContent = [
            ['description' => 'A stack data structure follows which access order?', 'options' => ['Last-in, first-out', 'First-in, first-out', 'Random access', 'Priority-based']],
            ['description' => 'Which SQL clause is used to filter grouped rows?', 'options' => ['HAVING', 'WHERE', 'GROUP BY', 'ORDER BY']],
            ['description' => 'What does REST stand for in the context of web APIs?', 'options' => ['Representational State Transfer', 'Remote State Transmission', 'Reliable Endpoint Service Transfer', 'Resource State Translation']],
            ['description' => 'Which of these is a non-relational (NoSQL) database?', 'options' => ['MongoDB', 'PostgreSQL', 'MySQL', 'SQLite']],
            ['description' => 'What is the purpose of a foreign key in a relational database?', 'options' => ['Enforcing a link between two tables', 'Speeding up full-table scans', 'Encrypting column values', 'Compressing row storage']],
            ['description' => 'Which design pattern restricts a class to a single instance?', 'options' => ['Singleton', 'Factory', 'Observer', 'Decorator']],
            ['description' => 'What is the main advantage of using a CDN?', 'options' => ['Serving static assets closer to the user', 'Encrypting database backups', 'Reducing server-side CPU usage', 'Automating deployments']],
            ['description' => 'Which HTTP method is idempotent and used to update a full resource?', 'options' => ['PUT', 'POST', 'PATCH', 'CONNECT']],
            ['description' => 'What does the acronym API stand for?', 'options' => ['Application Programming Interface', 'Automated Process Integration', 'Application Process Instance', 'Advanced Programming Interface']],
            ['description' => 'Which term describes breaking a large problem into smaller, independent subproblems?', 'options' => ['Decomposition', 'Aggregation', 'Normalization', 'Serialization']],
            ['description' => 'What is the purpose of a git branch?', 'options' => ['Isolating a line of development from the main codebase', 'Compressing repository history', 'Encrypting commit messages', 'Merging two remote repositories automatically']],
            ['description' => 'Which of the following best defines "latency" in a networked system?', 'options' => ['The time it takes for a request to travel and receive a response', 'The maximum number of concurrent users a server can handle', 'The total storage capacity of a server', 'The number of requests processed per second']],
            ['description' => 'What is the main goal of input validation in a web application?', 'options' => ['Preventing malformed or malicious data from being processed', 'Improving page load times', 'Reducing database storage size', 'Simplifying the user interface']],
            ['description' => 'Which concurrency primitive is used to prevent two threads from accessing a critical section simultaneously?', 'options' => ['Mutex/lock', 'Callback', 'Promise', 'Iterator']],
            ['description' => 'What is the primary purpose of a code review?', 'options' => ['Catching defects and sharing knowledge before code is merged', 'Automatically formatting code', 'Generating documentation', 'Measuring test coverage']],
        ];

        $essayContent = [
            'Describe a real-world scenario where you would choose a NoSQL database over a relational one, and justify your reasoning.',
            'Explain the difference between synchronous and asynchronous processing, with an example of when each is appropriate.',
            'Outline the steps involved in deploying a web application to production, including any safeguards you would put in place.',
            'Compare and contrast unit tests, integration tests, and end-to-end tests, and describe when each is most valuable.',
            'Describe how you would approach securing a public-facing API against common attacks.',
        ];

        for ($i = 0; $i < $count; $i++) {
            $startDate = now()->addWeeks($i);
            $endDate = $startDate->clone()->addWeek();

            DB::transaction(function () use ($assessmentService, $assessmentQuestionService, $assessmentQuestionOptionService, $finalExamService, $mcContent, $essayContent, $examType, $instructions, $startDate, $endDate): void {
                $assessment = $assessmentService->create([
                    'course_id' => $this->course->id,
                    'session_id' => null,
                    'type' => AssessmentType::TheoryFinalExam,
                    'title' => AssessmentTypeLabel::forType(AssessmentType::TheoryFinalExam).' - Set '.random_int(1, 99),
                    'weight' => AssessmentType::TheoryFinalExam->defaultWeight(),
                    'assigned_to' => AssessmentAssignedTo::Individual,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => AssessmentStatus::Draft,
                ]);

                $finalExamService->create([
                    'assessment_id' => $assessment->id,
                    'exam_type' => FinalExamType::from($examType),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'instructions' => $instructions,
                ]);

                $order = 0;

                foreach ($mcContent as $questionData) {
                    $order++;

                    $question = $assessmentQuestionService->create([
                        'assessment_id' => $assessment->id,
                        'description' => '<p>'.$questionData['description'].'</p>',
                        'points' => 0,
                        'question_type' => AssessmentQuestionType::MultipleChoice,
                        'order' => $order,
                    ]);

                    foreach ($questionData['options'] as $optionIndex => $label) {
                        $assessmentQuestionOptionService->create([
                            'assessment_question_id' => $question->id,
                            'label' => $label,
                            'is_correct' => $optionIndex === 0,
                            'order' => $optionIndex + 1,
                        ]);
                    }
                }

                foreach ($essayContent as $description) {
                    $order++;

                    $assessmentQuestionService->create([
                        'assessment_id' => $assessment->id,
                        'description' => '<p>'.$description.'</p>',
                        'points' => 20,
                        'question_type' => AssessmentQuestionType::Essay,
                        'order' => $order,
                    ]);
                }
            });
        }
    }

    public function render(AssessmentService $assessmentService, AssessmentAttemptService $assessmentAttemptService, CoursePersonService $coursePersonService, GroupMemberService $groupMemberService, AssessmentQuestionAttemptScoringService $assessmentQuestionAttemptScoringService, AttendanceScoringService $attendanceScoringService, AttendanceDerivationService $attendanceDerivationService, ForumDiscussionScoringService $forumDiscussionScoringService, ProctorSessionService $proctorSessionService, ForumThreadService $forumThreadService, ForumCommentService $forumCommentService)
    {
        if (! $this->assessmentsLoaded) {
            return view('livewire.courses.assessment-index-placeholder', [
                'course' => $this->course,
                'courseTabs' => CourseTabs::build($this->course, 'assessment'),
                'teacher' => $this->isStudent
                    ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                    : null,
            ])
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        $assessments = $assessmentService->get(['course_id' => $this->course->id], ['attempts.score', 'finalExam']);

        if ($this->isStudent) {
            $assessments = $assessments->reject(fn (Assessment $assessment) => in_array($assessment->type, [AssessmentType::TheoryPersonalAssignment, AssessmentType::TheoryTeamAssignment, AssessmentType::TheoryQuiz, AssessmentType::TheoryFinalExam], true)
                && $assessment->status === AssessmentStatus::Draft)->values();
        }

        $rows = $assessments->mapWithKeys(function (Assessment $assessment) use ($assessmentAttemptService, $groupMemberService, $assessmentQuestionAttemptScoringService, $attendanceScoringService, $forumDiscussionScoringService, $proctorSessionService) {
            return [$assessment->id => $this->rowStatus($assessment, $assessmentAttemptService, $groupMemberService, $assessmentQuestionAttemptScoringService, $attendanceScoringService, $forumDiscussionScoringService, $proctorSessionService)];
        })->all();

        $allSessions = $attendanceDerivationService->sessionsForCourse($this->course);

        $virtualClassSessions = $allSessions
            ->filter(fn (Session $session) => in_array($session->delivery_mode, [DeliveryMode::VirtualClass, DeliveryMode::Offline], true))
            ->values();

        $onlineSessions = $allSessions
            ->filter(fn (Session $session) => $session->delivery_mode === DeliveryMode::Online)
            ->values();

        $sessionPositions = $allSessions->values()
            ->mapWithKeys(fn (Session $session, int $index) => [$session->id => $index + 1])
            ->all();

        $grouped = collect(AssessmentType::cases())
            ->map(fn (AssessmentType $type) => $this->buildTypeGroup($type, $assessments, $rows, $attendanceDerivationService, $virtualClassSessions, $onlineSessions, $forumDiscussionScoringService, $sessionPositions, $coursePersonService, $forumThreadService, $forumCommentService))
            ->all();

        return view('livewire.courses.assessment-index', [
            'course' => $this->course,
            'isStudent' => $this->isStudent,
            'groupedAssessments' => $grouped,
            'rowStatus' => $rows,
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }

    /**
     * @param  Collection<int, Assessment>  $assessments
     * @param  array<string, array{status: string, route: string|null, attemptCount: int, attemptLimit: string, score: float|null, isExpired: bool, statusConfig: array{bg: string, text: string, icon: string}}>  $rows
     * @param  Collection<int, Session>  $virtualClassSessions
     * @param  Collection<int, Session>  $onlineSessions
     * @param  array<string, int>  $sessionPositions
     * @return array<string, mixed>
     */
    private function buildTypeGroup(AssessmentType $type, Collection $assessments, array $rows, AttendanceDerivationService $attendanceDerivationService, Collection $virtualClassSessions, Collection $onlineSessions, ForumDiscussionScoringService $forumDiscussionScoringService, array $sessionPositions, CoursePersonService $coursePersonService, ForumThreadService $forumThreadService, ForumCommentService $forumCommentService): array
    {
        $group = [
            'type' => $type,
            'assessments' => $assessments->where('type', $type)->values()->map(fn (Assessment $a) => [
                'data' => $a,
                'row' => $rows[$a->id],
                'sessionPosition' => $a->session_id ? ($sessionPositions[$a->session_id] ?? null) : null,
                'isReorderable' => ! $this->isStudent && ! $this->isAutoProvisionedType($a->type),
                'isAutoProvisionedType' => $this->isAutoProvisionedType($a->type),
                'isAssignmentType' => in_array($a->type, [AssessmentType::TheoryPersonalAssignment, AssessmentType::TheoryTeamAssignment, AssessmentType::TheoryQuiz, AssessmentType::TheoryFinalExam], true),
                'isDraft' => $a->status === AssessmentStatus::Draft,
                'editRoute' => $this->editRoute($a),
                'publishWireTargets' => "publishAssessment('{$a->id}'),unpublishAssessment('{$a->id}')",
                'examTypeLabel' => $a->finalExam ? str($a->finalExam->exam_type->value)->replace('_', ' ')->title()->toString() : null,
            ]),
            'sectionKey' => $type->value,
            'isExpanded' => isset($this->expandedSections[$type->value]) && $this->expandedSections[$type->value],
            'totalWeight' => $assessments->where('type', $type)->sum('weight'),
            'generateMethod' => $this->generateMethodForType($type),
            'showExamType' => $type === AssessmentType::TheoryFinalExam,
        ];

        $group['columnCount'] = 7 + ($group['showExamType'] ? 1 : 0);

        $group['selectableAssessmentIds'] = $group['assessments']
            ->filter(fn (array $item) => $item['row']['route'] && ! $item['isAutoProvisionedType'])
            ->pluck('data.id')
            ->values();

        if ($type === AssessmentType::Attendance) {
            if ($this->isStudent) {
                $group['sessionTableRows'] = $virtualClassSessions->values()->map(function (Session $session, int $index) use ($attendanceDerivationService) {
                    $attended = $attendanceDerivationService->isSessionAttended($session, auth()->id());

                    return [
                        'session' => $session,
                        'sessionIndex' => $index,
                        'met' => $attended,
                        'metLabel' => 'Completed',
                        'notMetLabel' => 'Not attended',
                        'points' => $attended ? '100 pts' : '0 pts',
                        'href' => route('sessions.index', $this->course).'?session='.$session->id,
                        'wireKey' => 'attendance-session-'.$session->id,
                    ];
                });
            } else {
                $students = $coursePersonService->studentsForCourse($this->course->id);

                $group['sessionTableRows'] = $virtualClassSessions->values()->map(function (Session $session, int $index) use ($students, $attendanceDerivationService) {
                    $attendedCount = $students->filter(
                        fn ($coursePerson) => $attendanceDerivationService->isSessionAttended($session, $coursePerson->user_id)
                    )->count();

                    return [
                        'session' => $session,
                        'sessionIndex' => $index,
                        'attendedCount' => $attendedCount,
                        'totalStudents' => $students->count(),
                        'href' => route('attendance.index', $this->course).'?session='.$session->id,
                        'wireKey' => 'attendance-session-'.$session->id,
                    ];
                });
            }

            $group['sessionTableEmptyMessage'] = __('No virtual class sessions yet.');
        }

        if ($type === AssessmentType::ForumDiscussion) {
            if ($this->isStudent) {
                $group['sessionTableRows'] = $onlineSessions->values()->map(function (Session $session, int $index) use ($forumDiscussionScoringService, $forumThreadService, $forumCommentService) {
                    $met = $forumDiscussionScoringService->hasMetForumPostRequirement($session, auth()->id());

                    $threads = $forumThreadService->forUserInSession(auth()->id(), $session->id);
                    $comments = $forumCommentService->forUserInSession(auth()->id(), $session->id, ['thread']);

                    return [
                        'session' => $session,
                        'sessionIndex' => $index,
                        'met' => $met,
                        'metLabel' => 'Completed',
                        'notMetLabel' => $forumDiscussionScoringService->requiredForumPosts($session).' posts required',
                        'points' => $met ? '100 pts' : '0 pts',
                        'href' => route('forum.index', $this->course).'?session='.$session->id,
                        'wireKey' => 'forum-discussion-session-'.$session->id,
                        'threadsJson' => $threads->map(fn (ForumThread $thread) => [
                            'id' => $thread->id,
                            'title' => $thread->title,
                            'createdAt' => $thread->created_at_display->format('d M Y, H:i'),
                        ])->values()->all(),
                        'commentsJson' => $comments->map(fn (ForumComment $comment) => [
                            'id' => $comment->id,
                            'threadId' => $comment->thread_id,
                            'body' => str($comment->body)->stripTags()->limit(200)->toString(),
                            'threadTitle' => $comment->thread->title,
                            'createdAt' => $comment->created_at_display->format('d M Y, H:i'),
                        ])->values()->all(),
                    ];
                });
            } else {
                $students = $coursePersonService->studentsForCourse($this->course->id);

                $group['sessionTableRows'] = $onlineSessions->values()->map(function (Session $session, int $index) use ($students, $forumDiscussionScoringService) {
                    $metCount = $students->filter(
                        fn ($coursePerson) => $forumDiscussionScoringService->hasMetForumPostRequirement($session, $coursePerson->user_id)
                    )->count();

                    return [
                        'session' => $session,
                        'sessionIndex' => $index,
                        'metCount' => $metCount,
                        'totalStudents' => $students->count(),
                        'href' => route('forum.index', $this->course).'?session='.$session->id,
                        'wireKey' => 'forum-discussion-session-'.$session->id,
                    ];
                });
            }

            $group['sessionTableEmptyMessage'] = __('No online sessions yet.');
        }

        return $group;
    }

    private function isAutoProvisionedType(AssessmentType $type): bool
    {
        return in_array($type, [AssessmentType::Attendance, AssessmentType::ForumDiscussion], true);
    }

    private function generateMethodForType(AssessmentType $type): ?string
    {
        return match ($type) {
            AssessmentType::TheoryPersonalAssignment => 'generatePersonalAssignments',
            AssessmentType::TheoryTeamAssignment => 'generateTeamAssignments',
            AssessmentType::TheoryQuiz => 'generateQuizzes',
            AssessmentType::TheoryFinalExam => 'generateFinalExam',
            default => null,
        };
    }

    public function editRoute(Assessment $assessment): string
    {
        return match ($assessment->type) {
            AssessmentType::TheoryQuiz => route('assessments.quiz.edit', $assessment),
            AssessmentType::TheoryFinalExam => route('assessments.final-exam.edit', $assessment),
            default => route('assessments.edit', $assessment),
        };
    }

    /**
     * @return array{status: string, route: string|null, attemptCount: int, attemptLimit: string, score: float|null, isExpired: bool, statusConfig: array{bg: string, text: string, icon: string}}
     */
    private function rowStatus(Assessment $assessment, AssessmentAttemptService $assessmentAttemptService, GroupMemberService $groupMemberService, AssessmentQuestionAttemptScoringService $assessmentQuestionAttemptScoringService, AttendanceScoringService $attendanceScoringService, ForumDiscussionScoringService $forumDiscussionScoringService, ProctorSessionService $proctorSessionService): array
    {
        $type = $assessment->type;
        $isExpired = $assessment->end_date && $assessment->end_date->isPast();

        $attemptLimit = $type === AssessmentType::TheoryQuiz
            ? $assessment->quiz?->total_attempts
            : $assessment->attempt_limit;
        $attemptLimit = $attemptLimit ? (string) $attemptLimit : 'unlimited';

        $route = match ($type) {
            AssessmentType::TheoryPersonalAssignment => route('assessments.personal.show', $assessment),
            AssessmentType::TheoryTeamAssignment => route('assessments.team.show', $assessment),
            AssessmentType::TheoryQuiz => route('assessments.quiz.show', $assessment),
            AssessmentType::TheoryFinalExam => route('assessments.final-exam.show', $assessment),
            AssessmentType::Attendance => route('assessments.attendance.show', $assessment),
            AssessmentType::ForumDiscussion => route('assessments.forum-discussion.show', $assessment),
        };

        $base = [
            'route' => $route,
            'attemptCount' => 0,
            'attemptLimit' => $attemptLimit,
            'score' => null,
            'isExpired' => $isExpired,
        ];

        if (! $this->isStudent) {
            return [
                ...$base,
                'status' => $assessment->status->value,
                'statusConfig' => $this->statusConfig($assessment->status->value),
            ];
        }

        if ($type === AssessmentType::TheoryQuiz) {
            $attempts = $assessmentAttemptService->forAssessmentAndUser($assessment->id, auth()->id())
                ->filter(fn ($attempt) => $attempt->submitted_at !== null)
                ->values();

            if ($attempts->isEmpty()) {
                return [
                    ...$base,
                    'status' => 'not_started',
                    'feedback' => null,
                    'statusConfig' => $this->statusConfig('not_started'),
                ];
            }

            $scoredAttempt = $attempts->first(fn ($attempt) => $attempt->score !== null);
            $pending = $attempts->contains(fn ($attempt) => $assessmentQuestionAttemptScoringService->hasPendingGrading($attempt->id));
            $status = $pending ? 'submitted' : 'graded';

            return [
                ...$base,
                'status' => $status,
                'attemptCount' => $attempts->count(),
                'score' => $scoredAttempt?->score?->score,
                'statusConfig' => $this->statusConfig($status),
            ];
        }

        if ($type === AssessmentType::Attendance) {
            $computed = $attendanceScoringService->computeForUser($assessment, auth()->id());

            return [
                ...$base,
                'status' => 'graded',
                'score' => $computed['score'],
                'statusConfig' => $this->statusConfig('graded'),
            ];
        }

        if ($type === AssessmentType::ForumDiscussion) {
            $computed = $forumDiscussionScoringService->computeForUser($assessment, auth()->id());

            return [
                ...$base,
                'status' => 'graded',
                'score' => $computed['score'],
                'statusConfig' => $this->statusConfig('graded'),
            ];
        }

        if ($type === AssessmentType::TheoryTeamAssignment) {
            $member = $groupMemberService->get(['user_id' => auth()->id()])
                ->first(fn (GroupMember $m) => $m->group->course_id === $this->course->id);
            $attempts = $member ? $assessmentAttemptService->forAssessmentAndGroup($assessment->id, $member->group_id) : collect();
        } else {
            $attempts = $assessmentAttemptService->forAssessmentAndUser($assessment->id, auth()->id());
        }

        $latest = $attempts->last();
        $score = $latest?->score?->score;
        $base['attemptCount'] = $attempts->count();

        if (! $latest) {
            return [
                ...$base,
                'status' => 'not_started',
                'attemptCount' => 0,
                'statusConfig' => $this->statusConfig('not_started'),
            ];
        }

        if ($latest->submitted_at === null) {
            return [
                ...$base,
                'status' => 'in_progress',
                'feedback' => null,
                'statusConfig' => $this->statusConfig('in_progress'),
            ];
        }

        if ($score !== null) {
            $isProctoredFinalExam = $type === AssessmentType::TheoryFinalExam
                && in_array($assessment->finalExam?->exam_type?->value, ['open_book', 'closed_book'], true);

            if ($isProctoredFinalExam) {
                $proctorSession = $proctorSessionService->findByAttempt($latest->id);

                if ($proctorSession !== null && $proctorSession->reviewed_at === null) {
                    return [
                        ...$base,
                        'status' => 'pending_review',
                        'score' => null,
                        'feedback' => null,
                        'statusConfig' => $this->statusConfig('pending_review'),
                    ];
                }

                if ($proctorSession?->review_decision === ProctorReviewDecision::Disqualified) {
                    return [
                        ...$base,
                        'status' => 'disqualified',
                        'score' => $score,
                        'statusConfig' => $this->statusConfig('disqualified'),
                    ];
                }
            }

            return [
                ...$base,
                'status' => 'graded',
                'score' => $score,
                'statusConfig' => $this->statusConfig('graded'),
            ];
        }

        return [
            ...$base,
            'status' => 'submitted',
            'feedback' => null,
            'statusConfig' => $this->statusConfig('submitted'),
        ];
    }

    /**
     * @return array{bg: string, text: string, icon: string}
     */
    private function statusConfig(string $status): array
    {
        return match ($status) {
            'completed', 'graded', 'published' => ['bg' => 'bg-success/10', 'text' => 'text-success', 'icon' => 'check_circle'],
            'submitted', 'pending_review' => ['bg' => 'bg-warning/10', 'text' => 'text-warning', 'icon' => 'schedule'],
            'draft' => ['bg' => 'bg-on-surface-variant/10', 'text' => 'text-on-surface-variant', 'icon' => 'edit_note'],
            'not_started' => ['bg' => 'bg-on-surface-variant/10', 'text' => 'text-on-surface-variant', 'icon' => 'pending'],
            'in_progress' => ['bg' => 'bg-primary/10', 'text' => 'text-primary', 'icon' => 'timelapse'],
            'disqualified' => ['bg' => 'bg-error/10', 'text' => 'text-error', 'icon' => 'cancel'],
            default => ['bg' => 'bg-on-surface-variant/10', 'text' => 'text-on-surface-variant', 'icon' => 'help'],
        };
    }
}
