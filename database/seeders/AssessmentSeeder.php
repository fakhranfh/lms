<?php

namespace Database\Seeders;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\CourseMembershipStatus;
use App\Enums\FinalExamType;
use App\Enums\QuizQuestionType;
use App\Enums\QuizScoringMethod;
use App\Enums\RoleInCourse;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\FinalExam;
use App\Models\Group;
use App\Models\Period;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use App\Services\QuizAttemptScoringService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AssessmentSeeder extends Seeder
{
    /**
     * Seed a Personal Assignment and a Team Assignment (with groups, questions,
     * and a mix of not-started/submitted/graded attempts) for existing courses
     * that don't have any assessments yet. Also backfills a Quiz and a Final
     * Exam for any course that doesn't have one yet, even if it already has
     * other assessment types, and re-links any previously-seeded Quiz whose
     * session has since expired to a still-open one.
     */
    public function run(): void
    {
        $courses = Course::doesntHave('assessments')->get();

        foreach ($courses as $course) {
            $this->seedCourseAssessments($course);
        }

        $this->seedMissingQuizzes();
        $this->seedMissingFinalExams();
        $this->relinkExpiredQuizzes();
    }

    private function seedMissingQuizzes(): void
    {
        $courses = Course::whereHas('sessions')
            ->whereDoesntHave('assessments', fn ($query) => $query->where('type', AssessmentType::TheoryQuiz))
            ->get();

        foreach ($courses as $course) {
            $session = $this->quizSessionForCourse($course);

            if (! $session) {
                continue;
            }

            $this->seedQuiz($course, $session, $this->studentsForCourse($course));
        }
    }

    private function seedMissingFinalExams(): void
    {
        foreach (Course::all() as $course) {
            $missingTypes = collect(FinalExamType::cases())
                ->reject(fn (FinalExamType $type) => Assessment::where('course_id', $course->id)
                    ->where('type', AssessmentType::TheoryFinalExam)
                    ->whereHas('finalExam', fn ($query) => $query->where('exam_type', $type))
                    ->exists());

            if ($missingTypes->isEmpty()) {
                continue;
            }

            $period = $this->periodForCourse($course);

            if (! $period) {
                continue;
            }

            foreach ($missingTypes as $type) {
                $this->seedFinalExam($course, $period, $type);
            }
        }
    }

    /**
     * Move any already-seeded Quiz assessment whose window has passed onto a
     * still-open session, so demo quizzes never show up as expired.
     */
    private function relinkExpiredQuizzes(): void
    {
        $expiredQuizzes = Assessment::where('type', AssessmentType::TheoryQuiz)
            ->where('end_date', '<', now())
            ->with('quiz', 'course')
            ->get();

        foreach ($expiredQuizzes as $assessment) {
            $session = $this->quizSessionForCourse($assessment->course);

            if (! $session) {
                continue;
            }

            $assessment->update([
                'session_id' => $session->id,
                'start_date' => $session->date_start,
                'end_date' => $session->date_end,
            ]);

            $assessment->quiz?->update([
                'start_date' => $session->date_start,
                'due_date' => $session->date_end,
            ]);
        }
    }

    /**
     * Prefer the earliest session that hasn't ended yet, so seeded quizzes
     * are always attemptable; fall back to the course's latest session if
     * every session has already passed.
     */
    private function quizSessionForCourse(Course $course): ?Session
    {
        return $course->sessions()->where('date_end', '>=', now())->orderBy('date_start')->first()
            ?? $course->sessions()->orderBy('date_start', 'desc')->first();
    }

    private function seedCourseAssessments(Course $course): void
    {
        $teacher = CoursePerson::where('course_id', $course->id)
            ->where('role_in_course', RoleInCourse::Teacher)
            ->first()
            ?->user;

        if (! $teacher) {
            return;
        }

        $students = $this->studentsForCourse($course);
        $groups = $this->groupsForCourse($course, $students, $teacher);

        $personal = $this->createAssignment($course, AssessmentType::TheoryPersonalAssignment, AssessmentAssignedTo::Individual, 'Personal Assignment: Reflection Essay');
        $this->seedPersonalQuestions($personal);
        $this->seedIndividualAttempts($personal, $students);

        $team = $this->createAssignment($course, AssessmentType::TheoryTeamAssignment, AssessmentAssignedTo::Group, 'Team Assignment: Group Project');
        $this->seedTeamQuestions($team);
        $this->seedGroupAttempts($team, $groups);

        $session = $this->quizSessionForCourse($course);
        if ($session) {
            $this->seedQuiz($course, $session, $students);
        }

        $period = $this->periodForCourse($course);
        if ($period) {
            foreach (FinalExamType::cases() as $type) {
                $this->seedFinalExam($course, $period, $type);
            }
        }
    }

    /**
     * Reuse the course's existing Period if one exists, otherwise group all
     * of the course's sessions into a single "Midterm to Final" Period.
     */
    private function periodForCourse(Course $course): ?Period
    {
        $existing = Period::where('course_id', $course->id)->first();

        if ($existing) {
            return $existing;
        }

        $sessions = $course->sessions()->orderBy('date_start')->get();

        if ($sessions->isEmpty()) {
            return null;
        }

        $period = Period::create([
            'course_id' => $course->id,
            'title' => 'Full Semester',
            'order' => 1,
        ]);

        foreach ($sessions as $order => $session) {
            $period->sessions()->attach($session->id, ['order' => $order + 1]);
        }

        return $period;
    }

    /**
     * Seeds without attempts, so every seeded Final Exam starts as
     * "not started" for every student regardless of exam_type.
     */
    private function seedFinalExam(Course $course, Period $period, FinalExamType $type): void
    {
        $assessment = Assessment::create([
            'course_id' => $course->id,
            'session_id' => null,
            'type' => AssessmentType::TheoryFinalExam,
            'title' => 'Final Exam: '.$this->finalExamTitleSuffix($type),
            'weight' => round(AssessmentType::TheoryFinalExam->defaultWeight() / count(FinalExamType::cases()), 2),
            'assigned_to' => AssessmentAssignedTo::Individual,
            'start_date' => Carbon::now()->subDays(3),
            'end_date' => Carbon::now()->addWeek(),
            'status' => AssessmentStatus::Published,
        ]);

        FinalExam::create([
            'assessment_id' => $assessment->id,
            'period_id' => $period->id,
            'exam_type' => $type,
            'start_date' => $assessment->start_date,
            'end_date' => $assessment->end_date,
            'allow_local_files' => $type === FinalExamType::TakeHome,
            'allow_internet' => $type !== FinalExamType::ClosedBook,
        ]);

        if ($type === FinalExamType::TakeHome) {
            $this->seedFinalExamEssayQuestions($assessment);
        } else {
            $this->seedFinalExamQuizQuestions($assessment, $type);
        }
    }

    private function finalExamTitleSuffix(FinalExamType $type): string
    {
        return match ($type) {
            FinalExamType::TakeHome => 'Take Home',
            FinalExamType::OpenBook => 'Open Book',
            FinalExamType::ClosedBook => 'Closed Book',
        };
    }

    /**
     * Standard (non-proctored) Take Home final exams use the essay-answer
     * flow (AssessmentFinalExamShow), so they get plain AssessmentQuestions.
     */
    private function seedFinalExamEssayQuestions(Assessment $assessment): void
    {
        $questions = [
            ['description' => '<p>Synthesize the key concepts covered throughout this course into a single coherent argument, using at least two concrete examples to support your points (800-1000 words).</p>', 'points' => 60],
            ['description' => '<p>Critically evaluate a real-world scenario of your choosing through the lens of what you learned in this course. Justify your conclusions.</p>', 'points' => 40],
        ];

        foreach ($questions as $order => $question) {
            $assessment->questions()->create([
                'description' => $question['description'],
                'points' => $question['points'],
                'order' => $order + 1,
            ]);
        }
    }

    /**
     * Open Book / Closed Book final exams are proctored and use the
     * quiz-style attempt flow (ProctorExamShow), so they need a Quiz with
     * multiple-choice questions rather than essay AssessmentQuestions.
     */
    private function seedFinalExamQuizQuestions(Assessment $assessment, FinalExamType $type): void
    {
        $quiz = Quiz::create([
            'assessment_id' => $assessment->id,
            'start_date' => $assessment->start_date,
            'due_date' => $assessment->end_date,
            'total_question' => 0,
            'total_attempts' => 1,
            'scoring_method' => QuizScoringMethod::Highest,
            'time_limit_per_attempt' => 60,
        ]);

        $definitions = $type === FinalExamType::OpenBook ? [
            [
                'description' => '<p>Using any course materials as reference, which option best solves the case study provided?</p>',
                'points' => 50,
                'options' => [
                    ['label' => 'Apply the approach covered in Week 3, adapted to the given constraints', 'is_correct' => true],
                    ['label' => 'Ignore the case study constraints entirely', 'is_correct' => false],
                    ['label' => 'Use a method not covered in this course', 'is_correct' => false],
                ],
            ],
            [
                'description' => '<p>Which comparison best justifies choosing one approach over the other for the given scenario?</p>',
                'points' => 50,
                'options' => [
                    ['label' => 'Approach A trades simplicity for flexibility, which fits this scenario', 'is_correct' => true],
                    ['label' => 'Both approaches are functionally identical', 'is_correct' => false],
                    ['label' => 'Neither approach applies to this scenario', 'is_correct' => false],
                ],
            ],
        ] : [
            [
                'description' => '<p>Without referring to any materials, which statement best explains the core principle covered in this course?</p>',
                'points' => 50,
                'options' => [
                    ['label' => 'The principle balances tradeoffs based on context', 'is_correct' => true],
                    ['label' => 'The principle has no practical application', 'is_correct' => false],
                    ['label' => 'The principle was deprecated in the course', 'is_correct' => false],
                ],
            ],
            [
                'description' => '<p>Solve the following problem using only what you have memorized: which answer is correct?</p>',
                'points' => 50,
                'options' => [
                    ['label' => 'The value obtained by applying the memorized formula directly', 'is_correct' => true],
                    ['label' => 'The value obtained by guessing', 'is_correct' => false],
                    ['label' => 'There is no correct answer', 'is_correct' => false],
                ],
            ],
        ];

        foreach ($definitions as $order => $definition) {
            $question = $quiz->questions()->create([
                'description' => $definition['description'],
                'points' => $definition['points'],
                'question_type' => QuizQuestionType::MultipleChoice,
                'order' => $order + 1,
            ]);

            foreach ($definition['options'] as $optionOrder => $option) {
                $question->options()->create([
                    'label' => $option['label'],
                    'is_correct' => $option['is_correct'],
                    'order' => $optionOrder + 1,
                ]);
            }
        }

        $quiz->update(['total_question' => count($definitions)]);
    }

    /**
     * @param  Collection<int, User>  $students
     */
    private function seedQuiz(Course $course, Session $session, Collection $students): void
    {
        $assessment = Assessment::create([
            'course_id' => $course->id,
            'session_id' => $session->id,
            'type' => AssessmentType::TheoryQuiz,
            'title' => 'Quiz: Concept Check',
            'weight' => AssessmentType::TheoryQuiz->defaultWeight(),
            'assigned_to' => AssessmentAssignedTo::Individual,
            'start_date' => $session->date_start,
            'end_date' => $session->date_end,
            'status' => AssessmentStatus::Published,
        ]);

        $quiz = Quiz::create([
            'assessment_id' => $assessment->id,
            'start_date' => $session->date_start,
            'due_date' => $session->date_end,
            'total_question' => 0,
            'total_attempts' => null,
            'scoring_method' => QuizScoringMethod::Highest,
            'time_limit_per_attempt' => 20,
        ]);

        $questions = $this->seedQuizQuestions($quiz);

        $this->seedQuizAttempts($assessment, $quiz, $questions, $students);
    }

    /**
     * @return Collection<int, QuizQuestion>
     */
    private function seedQuizQuestions(Quiz $quiz): Collection
    {
        $definitions = [
            [
                'description' => '<p>Which of the following best describes the core concept covered this week?</p>',
                'points' => 25,
                'options' => [
                    ['label' => 'A structured way to apply the concept to real problems', 'is_correct' => true],
                    ['label' => 'An unrelated historical footnote', 'is_correct' => false],
                    ['label' => 'A deprecated technique no longer taught', 'is_correct' => false],
                ],
            ],
            [
                'description' => '<p>Which statement is correct about the tradeoffs involved?</p>',
                'points' => 25,
                'options' => [
                    ['label' => 'There are no tradeoffs to consider', 'is_correct' => false],
                    ['label' => 'Simplicity is traded for flexibility, and vice versa', 'is_correct' => true],
                    ['label' => 'Tradeoffs only matter at scale', 'is_correct' => false],
                ],
            ],
            [
                'description' => '<p>Which approach best applies this concept to a real-world problem?</p>',
                'points' => 25,
                'options' => [
                    ['label' => 'Applying it rigidly regardless of context', 'is_correct' => false],
                    ['label' => 'Adapting it to the constraints of the specific problem', 'is_correct' => true],
                    ['label' => 'Avoiding it in practical settings', 'is_correct' => false],
                ],
            ],
            [
                'description' => '<p>What is the most common mistake when first learning this concept?</p>',
                'points' => 25,
                'options' => [
                    ['label' => 'Overcomplicating simple cases', 'is_correct' => true],
                    ['label' => 'Using it too rarely', 'is_correct' => false],
                    ['label' => 'Documenting it too thoroughly', 'is_correct' => false],
                ],
            ],
        ];

        $questions = collect();

        foreach ($definitions as $order => $definition) {
            $question = $quiz->questions()->create([
                'description' => $definition['description'],
                'points' => $definition['points'],
                'question_type' => QuizQuestionType::MultipleChoice,
                'order' => $order + 1,
            ]);

            foreach ($definition['options'] as $optionOrder => $option) {
                $question->options()->create([
                    'label' => $option['label'],
                    'is_correct' => $option['is_correct'],
                    'order' => $optionOrder + 1,
                ]);
            }

            $questions->push($question->load('options'));
        }

        $quiz->update(['total_question' => $questions->count()]);

        return $questions;
    }

    /**
     * @param  Collection<int, QuizQuestion>  $questions
     * @param  Collection<int, User>  $students
     */
    private function seedQuizAttempts(Assessment $assessment, Quiz $quiz, Collection $questions, Collection $students): void
    {
        $scoringService = app(QuizAttemptScoringService::class);

        foreach ($students as $student) {
            $outcome = $this->randomAttemptOutcome();

            if ($outcome === 'not_started') {
                continue;
            }

            $attempt = $assessment->attempts()->create([
                'user_id' => $student->id,
                'submitted_by' => $student->id,
                'attempt_number' => 1,
                'started_at' => Carbon::now()->subDays(random_int(1, 5)),
                'submitted_at' => Carbon::now()->subDays(random_int(0, 4)),
            ]);

            foreach ($questions as $question) {
                $correctOption = $question->options->firstWhere('is_correct', true);
                $selectedOption = random_int(1, 10) <= 7 ? $correctOption : $question->options->firstWhere('is_correct', false);

                $attempt->quizAnswers()->create([
                    'quiz_question_id' => $question->id,
                    'selected_option_id' => $selectedOption?->id,
                    'score' => $scoringService->scoreObjectiveAnswer($question, $selectedOption?->id),
                ]);
            }

            $scoringService->recomputeForUser($quiz, $assessment->id, $student->id);
        }
    }

    private function createAssignment(Course $course, AssessmentType $type, AssessmentAssignedTo $assignedTo, string $title): Assessment
    {
        return Assessment::create([
            'course_id' => $course->id,
            'session_id' => null,
            'type' => $type,
            'title' => $title,
            'weight' => $type->defaultWeight(),
            'assigned_to' => $assignedTo,
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->addWeek(),
            'status' => AssessmentStatus::Published,
        ]);
    }

    private function seedPersonalQuestions(Assessment $assessment): void
    {
        $questions = [
            ['description' => '<p>Write a short reflection (300-500 words) on the most important concept covered so far in this course, and explain why it matters to you.</p>', 'points' => 60],
            ['description' => '<p>Describe one real-world example where this concept applies, and how you would approach it differently after taking this course.</p>', 'points' => 40],
        ];

        foreach ($questions as $order => $question) {
            $assessment->questions()->create([
                'description' => $question['description'],
                'points' => $question['points'],
                'order' => $order + 1,
            ]);
        }
    }

    private function seedTeamQuestions(Assessment $assessment): void
    {
        $questions = [
            ['description' => '<p>As a group, design a small project that applies the concepts covered in this course to a real-world problem. Describe your approach and division of work.</p>', 'points' => 70],
            ['description' => '<p>Summarize the key challenges your group faced and how you resolved them.</p>', 'points' => 30],
        ];

        foreach ($questions as $order => $question) {
            $assessment->questions()->create([
                'description' => $question['description'],
                'points' => $question['points'],
                'order' => $order + 1,
            ]);
        }
    }

    /**
     * @param  Collection<int, User>  $students
     */
    private function seedIndividualAttempts(Assessment $assessment, Collection $students): void
    {
        foreach ($students as $student) {
            $outcome = $this->randomAttemptOutcome();

            if ($outcome === 'not_started') {
                continue;
            }

            $attempt = $assessment->attempts()->create([
                'user_id' => $student->id,
                'submitted_by' => $student->id,
                'attempt_number' => 1,
                'started_at' => Carbon::now()->subDays(random_int(1, 5)),
                'submitted_at' => Carbon::now()->subDays(random_int(0, 4)),
            ]);

            $attempt->answer()->create([
                'answer_text' => '<p>'.$this->answerBank()[array_rand($this->answerBank())].'</p>',
            ]);

            if ($outcome === 'graded') {
                $attempt->score()->create([
                    'score' => random_int(65, 98),
                    'graded_by' => $this->gradedBy($assessment),
                    'graded_at' => Carbon::now()->subDays(random_int(0, 3)),
                    'feedback' => $this->feedbackBank()[array_rand($this->feedbackBank())],
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, Group>  $groups
     */
    private function seedGroupAttempts(Assessment $assessment, Collection $groups): void
    {
        foreach ($groups as $group) {
            $outcome = $this->randomAttemptOutcome();

            if ($outcome === 'not_started' || $group->members->isEmpty()) {
                continue;
            }

            $submitter = $group->members->random()->user_id;

            $attempt = $assessment->attempts()->create([
                'group_id' => $group->id,
                'submitted_by' => $submitter,
                'attempt_number' => 1,
                'started_at' => Carbon::now()->subDays(random_int(1, 5)),
                'submitted_at' => Carbon::now()->subDays(random_int(0, 4)),
            ]);

            $attempt->answer()->create([
                'answer_text' => '<p>'.$this->answerBank()[array_rand($this->answerBank())].'</p>',
            ]);

            if ($outcome === 'graded') {
                $attempt->score()->create([
                    'score' => random_int(65, 98),
                    'graded_by' => $this->gradedBy($assessment),
                    'graded_at' => Carbon::now()->subDays(random_int(0, 3)),
                    'feedback' => $this->feedbackBank()[array_rand($this->feedbackBank())],
                ]);
            }
        }
    }

    private function gradedBy(Assessment $assessment): string
    {
        return CoursePerson::where('course_id', $assessment->course_id)
            ->where('role_in_course', RoleInCourse::Teacher)
            ->first()
            ->user_id;
    }

    /**
     * @return 'not_started'|'submitted'|'graded'
     */
    private function randomAttemptOutcome(): string
    {
        return match (random_int(1, 10)) {
            1, 2, 3 => 'not_started',
            4, 5, 6, 7 => 'graded',
            default => 'submitted',
        };
    }

    /**
     * Reuse existing course groups, splitting the student roster into groups
     * of ~3 if none exist yet.
     *
     * @param  Collection<int, User>  $students
     * @return Collection<int, Group>
     */
    private function groupsForCourse(Course $course, Collection $students, User $teacher): Collection
    {
        $existing = Group::where('course_id', $course->id)->with('members')->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $groups = collect();

        foreach ($students->chunk(3)->values() as $index => $chunk) {
            $group = Group::create([
                'course_id' => $course->id,
                'name' => 'Group '.($index + 1),
                'created_by' => $teacher->id,
            ]);

            foreach ($chunk as $student) {
                $group->members()->create([
                    'user_id' => $student->id,
                    'joined_at' => now(),
                ]);
            }

            $groups->push($group->load('members'));
        }

        return $groups;
    }

    /**
     * Reuse existing enrolled students for the course, enrolling a small
     * demo pool of new ones if none exist yet.
     *
     * @return Collection<int, User>
     */
    private function studentsForCourse(Course $course): Collection
    {
        $studentIds = CoursePerson::where('course_id', $course->id)
            ->where('role_in_course', RoleInCourse::Student)
            ->pluck('user_id');

        $existing = User::whereIn('id', $studentIds)->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $school = School::findOrFail($course->school_id);
        $students = User::factory()->forSchool($school)->count(6)->create();

        $students->each(function (User $student) use ($course): void {
            $student->assignRole('Student');

            CoursePerson::create([
                'id' => (string) Str::uuid(),
                'course_id' => $course->id,
                'user_id' => $student->id,
                'role_in_course' => RoleInCourse::Student,
                'enrolled_at' => now(),
                'status' => CourseMembershipStatus::Active,
            ]);
        });

        return $students;
    }

    /**
     * @return array<int, string>
     */
    private function answerBank(): array
    {
        return [
            'The most important concept for me was understanding how the underlying principles connect to practical outcomes. Working through the exercises made it click in a way the lecture alone did not.',
            'I found that applying this concept to a project I was already working on made it much easier to internalize. It changed how I approach similar problems going forward.',
            'Our group discussed several approaches before settling on the one we felt best balanced simplicity and effectiveness, which we detail below along with the tradeoffs we considered.',
            'This assignment helped clarify a concept I had struggled with earlier in the course. I have included a concrete example to illustrate my understanding.',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function feedbackBank(): array
    {
        return [
            'Solid work overall — your reasoning is clear and well-supported. Consider expanding on the practical example next time.',
            'Good effort. The core idea comes through, but a bit more detail on the tradeoffs would strengthen this further.',
            'Well done, this shows a strong grasp of the material. Keep up the thorough explanations.',
            'Nice work — the example you chose fits well. Try to tie it back to the course concepts a little more explicitly next time.',
        ];
    }
}
