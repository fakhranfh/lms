<?php

namespace App\Livewire\Courses\Concerns;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\QuizScoringMethod;
use App\Services\AssessmentQuestionOptionService;
use App\Services\AssessmentQuestionService;
use App\Services\AssessmentService;
use App\Services\CoursePersonService;
use App\Services\FinalExamService;
use App\Services\GroupMemberService;
use App\Services\GroupService;
use App\Services\MediaLibraryService;
use App\Services\QuizService;
use App\Services\R2StorageService;
use App\Services\SessionService;
use App\Support\AssessmentTypeLabel;
use Illuminate\Support\Facades\DB;

trait HasAssessmentIndexDevTools
{
    /**
     * Points distribution for the 3 dev-generated questions, summing to 100
     * to satisfy AssessmentForm's total-points validation.
     */
    private const GENERATED_QUESTION_POINTS = [30, 30, 40];

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
}
