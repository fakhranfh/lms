<?php

namespace App\Services;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\CourseMembershipStatus;
use App\Enums\DeliveryMode;
use App\Enums\FinalExamType;
use App\Enums\MaterialType;
use App\Enums\QuizScoringMethod;
use App\Enums\RoleInCourse;
use App\Enums\SyllabusPolicyScope;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\Course;
use App\Models\Group;
use App\Models\MediaLibraryItem;
use App\Models\Period;
use App\Models\Quiz;
use App\Models\School;
use App\Models\Session;
use App\Models\Syllabus;
use App\Models\SyllabusLearningOutcome;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dev-only: builds a full, realistic demo course (sessions, syllabus,
 * materials, every assessment type, students, and groups) in one call, so
 * the app can be exercised end-to-end without manually filling in every
 * form. Mirrors the CourseSeeder/SessionSeeder/SyllabusSeeder/AssessmentSeeder
 * patterns, parameterized by how many courses to generate.
 */
class DemoCourseGeneratorService
{
    public function __construct(
        private CourseService $courseService,
        private CoursePersonService $coursePersonService,
        private SessionService $sessionService,
        private SessionSubtopicService $sessionSubtopicService,
        private MediaLibraryService $mediaLibraryService,
        private SchoolService $schoolService,
        private SyllabusService $syllabusService,
        private GroupService $groupService,
        private PeriodService $periodService,
        private AssessmentService $assessmentService,
        private QuizService $quizService,
        private FinalExamService $finalExamService,
    ) {}

    /**
     * @return Collection<int, Course>
     */
    public function generate(string $schoolId, string $createdBy, int $count): Collection
    {
        $count = max(1, min(20, $count));
        $school = $this->schoolService->find($schoolId);
        abort_if(! $school, 404, 'School not found.');

        $courses = collect();

        foreach ($this->pickBlueprints($schoolId, $count) as $blueprint) {
            $courses->push(DB::transaction(fn () => $this->generateCourse($school, $createdBy, $blueprint)));
        }

        return $courses;
    }

    private function generateCourse(School $school, string $createdBy, array $blueprint): Course
    {
        $course = $this->courseService->create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'created_by' => $createdBy,
            'title' => $blueprint['title'],
            'description' => $blueprint['description'],
        ]);

        $sessions = $this->generateSessions($course);
        $this->generateSyllabus($course);
        $students = $this->generateStudents($school, $course);
        $groups = $this->generateGroups($course, $students);
        $this->generateAssessments($course, $sessions, $students, $groups);

        return $course;
    }

    /**
     * @return Collection<int, Session>
     */
    private function generateSessions(Course $course): Collection
    {
        $start = Carbon::now()->subWeek()->startOfDay();
        $sessions = collect();

        foreach ($this->sessionBlueprints() as $index => $blueprint) {
            $dateStart = (clone $start)->addWeeks($index);
            $dateEnd = (clone $dateStart)->addDays(6)->endOfDay();

            $session = $this->sessionService->create([
                'course_id' => $course->id,
                'title' => $blueprint['title'],
                'learning_outcome' => $blueprint['learning_outcome'],
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'delivery_mode' => $blueprint['delivery_mode'],
                'order' => $index + 1,
            ]);

            foreach ($blueprint['subtopics'] as $order => $subtopic) {
                $this->sessionSubtopicService->create([
                    'session_id' => $session->id,
                    'subtopic' => $subtopic,
                    'order' => $order + 1,
                ]);
            }

            $material = $this->generateMaterial($course, $session, $blueprint);
            $session->materials()->sync([$material->id => ['order' => 1]]);

            $sessions->push($session);
        }

        return $sessions;
    }

    private function generateMaterial(Course $course, Session $session, array $blueprint): MediaLibraryItem
    {
        $content = "%PDF-1.4\n"
            .'1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj'."\n"
            .'2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj'."\n"
            .'3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]/Resources<<>>/Contents 4 0 R>>endobj'."\n"
            .'4 0 obj<</Length 44>>stream'."\n"
            ."BT /F1 18 Tf 20 100 Td ({$session->title}) Tj ET".
            "\nendstream endobj\n"
            .'trailer<</Size 5/Root 1 0 R>>'."\n"
            .'%%EOF';

        $key = "media/{$course->school_id}/demo-generated/{$session->id}.pdf";

        return $this->mediaLibraryService->createFromRawContent(
            $course->school_id,
            $course->created_by,
            $key,
            $content,
            'application/pdf',
            [
                'type' => MaterialType::PDF->value,
                'title' => "{$blueprint['title']} - Reading Material",
                'description' => "Reading material covering: {$blueprint['learning_outcome']}",
            ],
        );
    }

    private function generateSyllabus(Course $course): void
    {
        $subject = $course->title;

        $syllabus = $this->syllabusService->create([
            'course_id' => $course->id,
            'course_description' => "This course, {$subject}, introduces students to the core theories, tools, and practices that define the field. Across the term, learners progress from foundational concepts to applied, hands-on work, building a portfolio of exercises that demonstrate real-world competency. The course balances lecture-based instruction with guided practice, case studies, and collaborative projects so students can connect theory to industry practice.",
            'submission_and_collection' => "All assignments for {$subject} must be submitted through the LMS submission portal before the posted deadline in PDF or the specified project format. Late submissions are accepted up to 24 hours after the deadline with a 10% grade penalty per day; after that window, submissions will not be accepted without prior instructor approval. Group assignments require a single submission per team, with all member names clearly listed.",
            'tutorial_activity_plan' => "Weekly tutorial sessions for {$subject} reinforce lecture material through guided exercises, live demonstration walkthroughs, and Q&A. Students are expected to attempt the pre-tutorial preparation tasks before each session so that in-class time can focus on troubleshooting, deeper discussion, and peer collaboration. Tutorial attendance and participation are tracked as part of the overall course engagement record.",
            'teaching_learning_strategies' => "The teaching approach for {$subject} combines interactive lectures, in-class discussions, hands-on labs, and project-based learning. Concepts are introduced through direct instruction, then immediately reinforced with practical exercises and real-world case studies. Peer review and group critique sessions are used throughout the term to build collaborative and communication skills alongside technical mastery.",
            'textbooks' => "Primary reference: course-provided lecture notes and slide decks distributed via the LMS. Supplementary reading: current edition textbooks and official documentation relevant to {$subject}, as listed in the weekly session materials. Students are encouraged to consult additional open-access resources referenced during lectures.",
            'competency_map' => "Sessions in {$subject} are mapped to specific competencies: early sessions build foundational knowledge and terminology, mid-course sessions develop applied technical skills through hands-on practice, and later sessions target analysis, evaluation, and independent problem-solving. Each competency builds cumulatively on the previous one, culminating in the final assessment and project deliverables.",
            'video_overview' => "A short video overview introducing the goals, structure, and expectations of {$subject} is available to students at the start of the term, giving a walkthrough of the syllabus, grading breakdown, and weekly session cadence.",
        ]);

        $this->generateClassPolicies($syllabus);
        $learningOutcomes = $this->generateLearningOutcomes($syllabus, $subject);
        $this->generateEvaluations($syllabus, $learningOutcomes);
        $this->generateRubric($syllabus, $learningOutcomes);
    }

    private function generateClassPolicies(Syllabus $syllabus): void
    {
        $policies = [
            ['scope' => SyllabusPolicyScope::F2fVideo, 'content' => 'Student must attend class, and participate in classroom discussions.'],
            ['scope' => SyllabusPolicyScope::F2fVideo, 'content' => 'The ringing, beeping, or buzzing of phones and watches during class time is extremely disruptive. Please turn off or silence all devices before coming to the classroom.'],
            ['scope' => SyllabusPolicyScope::Online, 'content' => "Student must be active in the classroom discussion forum, responding to the lecturer's questions and discussing with classmates."],
            ['scope' => SyllabusPolicyScope::Online, 'content' => 'Student must be active in the team room, especially when discussing team assignments.'],
            ['scope' => SyllabusPolicyScope::General, 'content' => 'Student must read the learning material and other references before class. Reading materials and cases will be distributed ahead of time.'],
            ['scope' => SyllabusPolicyScope::General, 'content' => 'Student must complete and submit all personal and team assignments by the posted deadline.'],
            ['scope' => SyllabusPolicyScope::General, 'content' => 'Penalties for cheating and plagiarism are extremely severe. If unsure about a certain activity, consult the instructor first. Standard academic honesty procedures will be followed.'],
        ];

        foreach ($policies as $order => $policy) {
            $syllabus->classPolicies()->create([
                'scope' => $policy['scope'],
                'content' => $policy['content'],
                'order' => $order + 1,
            ]);
        }
    }

    /**
     * @return array<int, SyllabusLearningOutcome>
     */
    private function generateLearningOutcomes(Syllabus $syllabus, string $subject): array
    {
        $descriptions = [
            "Explain the core concepts, terminology, and theoretical foundations of {$subject}.",
            "Apply the tools and techniques covered in {$subject} to solve practical, real-world problems.",
            "Analyze and evaluate the effectiveness of different approaches used within {$subject}.",
            "Design and produce a project artifact that demonstrates independent mastery of {$subject}.",
        ];

        $outcomes = [];

        foreach ($descriptions as $index => $description) {
            $outcomes[] = $syllabus->learningOutcomes()->create([
                'code' => 'LO'.($index + 1),
                'description' => $description,
                'order' => $index + 1,
            ]);
        }

        return $outcomes;
    }

    /**
     * @param  array<int, SyllabusLearningOutcome>  $learningOutcomes
     */
    private function generateEvaluations(Syllabus $syllabus, array $learningOutcomes): void
    {
        $evaluation = $syllabus->evaluations()->create(['class_type' => 'LEC', 'order' => 1]);

        $activities = [
            ['activity' => 'Forum Discussion', 'weight' => 10],
            ['activity' => 'Attendance', 'weight' => 10],
            ['activity' => 'THEORY: Final Exam', 'weight' => 30],
            ['activity' => 'THEORY: Team Assignment', 'weight' => 15],
            ['activity' => 'THEORY: Personal Assignment', 'weight' => 20],
            ['activity' => 'THEORY: Quiz', 'weight' => 15],
        ];

        foreach ($activities as $order => $activityData) {
            $activity = $evaluation->activities()->create([
                'activity' => $activityData['activity'],
                'weight' => $activityData['weight'],
                'order' => $order + 1,
            ]);

            $activity->learningOutcomes()->attach(collect($learningOutcomes)->pluck('id'));
        }
    }

    /**
     * @param  array<int, SyllabusLearningOutcome>  $learningOutcomes
     */
    private function generateRubric(Syllabus $syllabus, array $learningOutcomes): void
    {
        $levels = [
            ['label' => 'Excellent', 'score_min' => 85, 'score_max' => 100],
            ['label' => 'Good', 'score_min' => 75, 'score_max' => 84],
            ['label' => 'Average', 'score_min' => 65, 'score_max' => 74],
            ['label' => 'Poor', 'score_min' => 0, 'score_max' => 64],
        ];

        $proficiencyLevels = [];

        foreach ($levels as $order => $levelData) {
            $proficiencyLevels[] = $syllabus->rubricProficiencyLevels()->create([
                'label' => $levelData['label'],
                'score_min' => $levelData['score_min'],
                'score_max' => $levelData['score_max'],
                'order' => $order + 1,
            ]);
        }

        $criteriaTemplates = [
            'Concepts are described correctly and completely, supported with relevant examples.',
            'Concepts are described correctly and completely, without relevant examples.',
            'Concepts are described correctly but incompletely, supported with relevant examples.',
            'Concepts are described correctly but incompletely, without relevant examples.',
        ];

        foreach ($learningOutcomes as $loIndex => $learningOutcome) {
            $keyIndicators = [
                ['code' => ($loIndex + 1).'.1', 'description' => "Ability to describe the core concepts covered in {$learningOutcome->code}."],
                ['code' => ($loIndex + 1).'.2', 'description' => "Ability to apply the practices and techniques covered in {$learningOutcome->code}."],
            ];

            foreach ($keyIndicators as $kiOrder => $keyIndicatorData) {
                $keyIndicator = $learningOutcome->rubricKeyIndicators()->create([
                    'code' => $keyIndicatorData['code'],
                    'description' => $keyIndicatorData['description'],
                    'order' => $kiOrder + 1,
                ]);

                foreach ($proficiencyLevels as $levelIndex => $proficiencyLevel) {
                    $keyIndicator->cells()->create([
                        'rubric_proficiency_level_id' => $proficiencyLevel->id,
                        'description' => $criteriaTemplates[$levelIndex],
                    ]);
                }
            }
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function generateStudents(School $school, Course $course): Collection
    {
        $names = [
            'Amelia Santoso', 'Bagas Wirawan', 'Citra Puspita', 'Dimas Prakoso', 'Erika Wulandari',
            'Fajar Nugroho', 'Gita Anggraini', 'Hendra Saputra',
        ];

        $students = collect();

        foreach ($names as $index => $name) {
            $email = Str::slug($name).'-'.Str::lower(Str::random(4)).'@demo.local';

            $student = User::factory()->forSchool($school)->create([
                'name' => $name,
                'email' => $email,
            ]);
            $student->assignRole('Student');

            $this->coursePersonService->enroll($course->id, $student->id, [
                'role_in_course' => RoleInCourse::Student,
                'enrolled_at' => now(),
                'status' => CourseMembershipStatus::Active,
            ]);

            $students->push($student);
        }

        return $students;
    }

    /**
     * @param  Collection<int, User>  $students
     * @return Collection<int, Group>
     */
    private function generateGroups(Course $course, Collection $students): Collection
    {
        $groups = collect();

        foreach ($students->chunk(3)->values() as $index => $chunk) {
            $group = $this->groupService->create([
                'course_id' => $course->id,
                'name' => 'Group '.($index + 1),
                'created_by' => $course->created_by,
                'target_size' => $chunk->count(),
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
     * @param  Collection<int, Session>  $sessions
     * @param  Collection<int, User>  $students
     * @param  Collection<int, Group>  $groups
     */
    private function generateAssessments(Course $course, Collection $sessions, Collection $students, Collection $groups): void
    {
        $personal = $this->createAssignment($course, AssessmentType::TheoryPersonalAssignment, AssessmentAssignedTo::Individual, 'Personal Assignment: Reflection Essay');
        $this->seedPersonalQuestions($personal);
        $this->seedIndividualAttempts($course, $personal, $students);

        $team = $this->createAssignment($course, AssessmentType::TheoryTeamAssignment, AssessmentAssignedTo::Group, 'Team Assignment: Group Project');
        $this->seedTeamQuestions($team);
        $this->seedGroupAttempts($course, $team, $groups);

        $quizSession = $sessions->firstWhere('date_end', '>=', now()) ?? $sessions->last();
        if ($quizSession) {
            $this->seedQuiz($course, $quizSession, $students);
        }

        $period = $this->periodForCourse($course, $sessions);
        foreach (FinalExamType::cases() as $type) {
            $this->seedFinalExam($course, $period, $type);
        }
    }

    private function periodForCourse(Course $course, Collection $sessions): Period
    {
        $period = $this->periodService->create(['course_id' => $course->id, 'title' => 'Full Semester', 'order' => 1]);

        foreach ($sessions as $order => $session) {
            $period->sessions()->attach($session->id, ['order' => $order + 1]);
        }

        return $period;
    }

    private function createAssignment(Course $course, AssessmentType $type, AssessmentAssignedTo $assignedTo, string $title): Assessment
    {
        return $this->assessmentService->create([
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
    private function seedIndividualAttempts(Course $course, Assessment $assessment, Collection $students): void
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
                    'graded_by' => $course->created_by,
                    'graded_at' => Carbon::now()->subDays(random_int(0, 3)),
                    'feedback' => $this->feedbackBank()[array_rand($this->feedbackBank())],
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, Group>  $groups
     */
    private function seedGroupAttempts(Course $course, Assessment $assessment, Collection $groups): void
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
                    'graded_by' => $course->created_by,
                    'graded_at' => Carbon::now()->subDays(random_int(0, 3)),
                    'feedback' => $this->feedbackBank()[array_rand($this->feedbackBank())],
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, User>  $students
     */
    private function seedQuiz(Course $course, Session $session, Collection $students): void
    {
        $assessment = $this->assessmentService->create([
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

        $quiz = $this->quizService->create([
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
     * @return Collection<int, AssessmentQuestion>
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
                'question_type' => AssessmentQuestionType::MultipleChoice,
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
     * @param  Collection<int, AssessmentQuestion>  $questions
     * @param  Collection<int, User>  $students
     */
    private function seedQuizAttempts(Assessment $assessment, Quiz $quiz, Collection $questions, Collection $students): void
    {
        $scoringService = app(AssessmentQuestionAttemptScoringService::class);

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

                $attempt->questionAnswers()->create([
                    'assessment_question_id' => $question->id,
                    'selected_option_id' => $selectedOption?->id,
                    'score' => $scoringService->scoreObjectiveAnswer($question, $selectedOption?->id),
                ]);
            }

            $scoringService->recomputeForUser($quiz, $assessment->id, $student->id);
        }
    }

    private function seedFinalExam(Course $course, Period $period, FinalExamType $type): void
    {
        $assessment = $this->assessmentService->create([
            'course_id' => $course->id,
            'session_id' => null,
            'type' => AssessmentType::TheoryFinalExam,
            'title' => 'Final Exam: '.$this->finalExamTitleSuffix($type),
            'weight' => round(AssessmentType::TheoryFinalExam->defaultWeight() / count(FinalExamType::cases()), 2),
            'assigned_to' => AssessmentAssignedTo::Individual,
            'start_date' => Carbon::now()->subDays(3),
            'end_date' => Carbon::now()->addWeek(),
            'status' => AssessmentStatus::Published,
            'attempt_limit' => 1,
        ]);

        $this->finalExamService->create([
            'assessment_id' => $assessment->id,
            'period_id' => $period->id,
            'exam_type' => $type,
            'start_date' => $assessment->start_date,
            'end_date' => $assessment->end_date,
            'instructions' => $this->finalExamInstructions($type),
        ]);

        if ($type === FinalExamType::TakeHome) {
            $this->seedFinalExamEssayQuestions($assessment);
        } else {
            $this->seedFinalExamQuizQuestions($assessment, $type);
        }
    }

    private function finalExamInstructions(FinalExamType $type): string
    {
        return match ($type) {
            FinalExamType::TakeHome => <<<'HTML'
                <p>This is a <strong>Take Home</strong> final exam. You may take as much time as you need within the submission window.</p>
                <ul>
                    <li>You may use any course materials, notes, or online resources.</li>
                    <li>Answers should be written in your own words and clearly justified.</li>
                    <li>Submit your answer before the deadline; late submissions will not be accepted.</li>
                </ul>
                HTML,
            FinalExamType::OpenBook => <<<'HTML'
                <p>This is a <strong>proctored Open Book</strong> exam. Your webcam and screen will be recorded for the full duration.</p>
                <ul>
                    <li>You may reference local files and printed course materials.</li>
                    <li>Internet access to unrelated sites and applications is <strong>not</strong> permitted and will be flagged.</li>
                    <li>Complete the pre-flight checks (internet speed, camera, microphone, screen share) before the exam begins.</li>
                    <li>Once started, the exam cannot be paused &mdash; make sure you're ready before clicking Start.</li>
                </ul>
                HTML,
            FinalExamType::ClosedBook => <<<'HTML'
                <p>This is a <strong>proctored Closed Book</strong> exam. Your webcam and screen will be recorded for the full duration.</p>
                <ul>
                    <li>No local files, notes, or printed materials are allowed during this exam.</li>
                    <li>Internet access and unauthorized applications are <strong>not</strong> permitted and will be flagged.</li>
                    <li>Complete the pre-flight checks (internet speed, camera, microphone, screen share) before the exam begins.</li>
                    <li>Once started, the exam cannot be paused &mdash; make sure you're ready before clicking Start.</li>
                </ul>
                HTML,
        };
    }

    private function finalExamTitleSuffix(FinalExamType $type): string
    {
        return match ($type) {
            FinalExamType::TakeHome => 'Take Home',
            FinalExamType::OpenBook => 'Open Book',
            FinalExamType::ClosedBook => 'Closed Book',
        };
    }

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

    private function seedFinalExamQuizQuestions(Assessment $assessment, FinalExamType $type): void
    {
        $quiz = $this->quizService->create([
            'assessment_id' => $assessment->id,
            'start_date' => $assessment->start_date,
            'due_date' => $assessment->end_date,
            'total_question' => 0,
            'total_attempts' => 1,
            'scoring_method' => QuizScoringMethod::Highest,
            'time_limit_per_attempt' => 60,
        ]);

        $mcDefinitions = $type === FinalExamType::OpenBook
            ? $this->buildMultipleChoiceQuestions(
                'Using any course materials as reference, which option best solves case study #%d provided?',
                'Apply the approach covered in Week %d, adapted to the given constraints',
                'Ignore the case study constraints entirely',
                'Use a method not covered in this course',
            )
            : $this->buildMultipleChoiceQuestions(
                'Without referring to any materials, which statement best explains core principle #%d covered in this course?',
                'The principle balances tradeoffs based on context, as taught in Week %d',
                'The principle has no practical application',
                'The principle was deprecated in the course',
            );

        $essayDefinitions = $type === FinalExamType::OpenBook
            ? $this->buildEssayQuestions('Using any course materials as reference, analyze case study #%d and justify your recommended solution (300-500 words).')
            : $this->buildEssayQuestions('Without referring to any materials, explain core principle #%d covered in this course and justify your answer from memory (300-500 words).');

        $order = 0;

        foreach ($mcDefinitions as $definition) {
            $order++;

            $question = $quiz->questions()->create([
                'description' => $definition['description'],
                'points' => $definition['points'],
                'question_type' => AssessmentQuestionType::MultipleChoice,
                'order' => $order,
            ]);

            foreach ($definition['options'] as $optionOrder => $option) {
                $question->options()->create([
                    'label' => $option['label'],
                    'is_correct' => $option['is_correct'],
                    'order' => $optionOrder + 1,
                ]);
            }
        }

        foreach ($essayDefinitions as $definition) {
            $order++;

            $quiz->questions()->create([
                'description' => $definition['description'],
                'points' => $definition['points'],
                'question_type' => AssessmentQuestionType::Essay,
                'order' => $order,
            ]);
        }

        $quiz->update(['total_question' => $order]);
    }

    /**
     * @return array<int, array{description: string, points: int, options: array<int, array{label: string, is_correct: bool}>}>
     */
    private function buildMultipleChoiceQuestions(string $descriptionTemplate, string $correctTemplate, string $distractorOne, string $distractorTwo): array
    {
        $questions = [];

        for ($i = 1; $i <= 15; $i++) {
            $questions[] = [
                'description' => '<p>'.sprintf($descriptionTemplate, $i).'</p>',
                'points' => 4,
                'options' => [
                    ['label' => sprintf($correctTemplate, $i), 'is_correct' => true],
                    ['label' => $distractorOne, 'is_correct' => false],
                    ['label' => $distractorTwo, 'is_correct' => false],
                ],
            ];
        }

        return $questions;
    }

    /**
     * @return array<int, array{description: string, points: int}>
     */
    private function buildEssayQuestions(string $descriptionTemplate): array
    {
        $questions = [];

        for ($i = 1; $i <= 5; $i++) {
            $questions[] = [
                'description' => '<p>'.sprintf($descriptionTemplate, $i).'</p>',
                'points' => 8,
            ];
        }

        return $questions;
    }

    private function randomAttemptOutcome(): string
    {
        return match (random_int(1, 10)) {
            1, 2, 3 => 'not_started',
            4, 5, 6, 7 => 'graded',
            default => 'submitted',
        };
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

    /**
     * @return array<int, array{title: string, learning_outcome: string, delivery_mode: DeliveryMode, subtopics: array<int, string>}>
     */
    private function sessionBlueprints(): array
    {
        return [
            [
                'title' => 'Introduction & Course Overview',
                'learning_outcome' => 'Understand the course structure, expectations, and grading policy.',
                'delivery_mode' => DeliveryMode::VirtualClass,
                'subtopics' => ['Course syllabus walkthrough', 'Learning outcomes overview', 'Grading and evaluation policy'],
            ],
            [
                'title' => 'Core Concepts',
                'learning_outcome' => 'Explain the fundamental concepts covered in this course.',
                'delivery_mode' => DeliveryMode::Online,
                'subtopics' => ['Key terminology', 'Foundational theory', 'Real-world examples'],
            ],
            [
                'title' => 'Hands-on Practice',
                'learning_outcome' => 'Apply core concepts through guided exercises.',
                'delivery_mode' => DeliveryMode::Offline,
                'subtopics' => ['Guided exercise walkthrough', 'Common pitfalls', 'Q&A'],
            ],
            [
                'title' => 'Case Study Discussion',
                'learning_outcome' => 'Analyze a real-world case study using concepts learned so far.',
                'delivery_mode' => DeliveryMode::VirtualClass,
                'subtopics' => ['Case study background', 'Group discussion', 'Key takeaways'],
            ],
            [
                'title' => 'Advanced Topics',
                'learning_outcome' => 'Explore advanced applications and edge cases.',
                'delivery_mode' => DeliveryMode::Offline,
                'subtopics' => ['Advanced techniques', 'Edge cases', 'Best practices'],
            ],
            [
                'title' => 'Review & Wrap-up',
                'learning_outcome' => 'Consolidate learning from the course and prepare for assessment.',
                'delivery_mode' => DeliveryMode::Online,
                'subtopics' => ['Recap of key topics', 'Sample questions', 'Final Q&A'],
            ],
        ];
    }

    /**
     * Cycles through a pool of real course titles/descriptions, appending a
     * "Batch N" suffix once the pool is exhausted so titles stay meaningful
     * (never lorem ipsum) no matter how many courses are requested.
     *
     * @return array<int, array{title: string, description: string}>
     */
    private function pickBlueprints(string $schoolId, int $count): array
    {
        $existingTitles = $this->courseService->get(['school_id' => $schoolId])->pluck('title')->all();
        $pool = $this->courseBlueprints();

        $picked = [];
        $batch = 1;
        $poolIndex = 0;

        while (count($picked) < $count) {
            $blueprint = $pool[$poolIndex % count($pool)];

            if ($poolIndex >= count($pool)) {
                $batch = intdiv($poolIndex, count($pool)) + 1;
                $blueprint['title'] = "{$blueprint['title']} (Batch {$batch})";
            }

            if (! in_array($blueprint['title'], $existingTitles, true) && ! in_array($blueprint['title'], array_column($picked, 'title'), true)) {
                $picked[] = $blueprint;
            }

            $poolIndex++;

            if ($poolIndex > count($pool) * 50) {
                break;
            }
        }

        return $picked;
    }

    /**
     * @return array<int, array{title: string, description: string}>
     */
    private function courseBlueprints(): array
    {
        return [
            ['title' => 'Web Development Fundamentals', 'description' => 'Learn the foundations of modern web development. Master HTML, CSS, and JavaScript to build responsive, interactive websites.'],
            ['title' => 'Backend Development with Laravel', 'description' => 'Build powerful backend applications using Laravel. Learn routing, databases, authentication, and API design patterns.'],
            ['title' => 'PHP Development Fundamentals', 'description' => 'Master PHP programming from basics to advanced OOP concepts. Learn modern PHP practices, error handling, and best practices for production-ready applications.'],
            ['title' => 'Modern Frontend Development', 'description' => 'Master modern frontend technologies: HTML5, CSS4, JavaScript ES2024, and responsive design. Build beautiful, performant web interfaces.'],
            ['title' => 'Database Design & SQL', 'description' => 'Learn relational database design, SQL optimization, and best practices. Design efficient databases that scale.'],
            ['title' => 'Building RESTful APIs', 'description' => 'Design and build scalable RESTful APIs. Learn REST principles, API design patterns, authentication, versioning, and testing.'],
            ['title' => 'Data Structures & Algorithms', 'description' => 'Master fundamental data structures and algorithms. Improve problem-solving skills and write efficient code.'],
            ['title' => 'Mobile App Development with Flutter', 'description' => 'Build cross-platform mobile applications with Flutter and Dart. Cover state management, navigation, and native device integration.'],
            ['title' => 'Cloud Computing Essentials', 'description' => 'Understand core cloud computing concepts, deployment models, and services. Learn to design and deploy scalable cloud-based applications.'],
            ['title' => 'Introduction to Machine Learning', 'description' => 'Explore the fundamentals of machine learning, from supervised and unsupervised learning to model evaluation and deployment.'],
        ];
    }
}
