<?php

namespace Database\Seeders;

use App\Enums\SyllabusPolicyScope;
use App\Models\Course;
use App\Models\Syllabus;
use App\Models\SyllabusEvaluation;
use App\Models\SyllabusLearningOutcome;
use App\Models\SyllabusRubricKeyIndicator;
use App\Models\SyllabusRubricProficiencyLevel;
use Illuminate\Database\Seeder;

class SyllabusSeeder extends Seeder
{
    /**
     * Seed a syllabus (class policies, learning outcomes, evaluation
     * breakdown, and assessment rubric) for existing courses that don't
     * have one yet.
     */
    public function run(): void
    {
        $courses = Course::doesntHave('syllabus')->get();

        foreach ($courses as $course) {
            $this->seedCourseSyllabus($course);
        }
    }

    private function seedCourseSyllabus(Course $course): void
    {
        $subject = $course->title;

        $syllabus = Syllabus::create([
            'course_id' => $course->id,
            'course_description' => "This course, {$subject}, introduces students to the core theories, tools, and practices that define the field. Across the term, learners progress from foundational concepts to applied, hands-on work, building a portfolio of exercises that demonstrate real-world competency. The course balances lecture-based instruction with guided practice, case studies, and collaborative projects so students can connect theory to industry practice.",
            'submission_and_collection' => "All assignments for {$subject} must be submitted through the LMS submission portal before the posted deadline in PDF or the specified project format. Late submissions are accepted up to 24 hours after the deadline with a 10% grade penalty per day; after that window, submissions will not be accepted without prior instructor approval. Group assignments require a single submission per team, with all member names clearly listed.",
            'tutorial_activity_plan' => "Weekly tutorial sessions for {$subject} reinforce lecture material through guided exercises, live demonstration walkthroughs, and Q&A. Students are expected to attempt the pre-tutorial preparation tasks before each session so that in-class time can focus on troubleshooting, deeper discussion, and peer collaboration. Tutorial attendance and participation are tracked as part of the overall course engagement record.",
            'teaching_learning_strategies' => "The teaching approach for {$subject} combines interactive lectures, in-class discussions, hands-on labs, and project-based learning. Concepts are introduced through direct instruction, then immediately reinforced with practical exercises and real-world case studies. Peer review and group critique sessions are used throughout the term to build collaborative and communication skills alongside technical mastery.",
            'textbooks' => "Primary reference: course-provided lecture notes and slide decks distributed via the LMS. Supplementary reading: current edition textbooks and official documentation relevant to {$subject}, as listed in the weekly session materials. Students are encouraged to consult additional open-access resources referenced during lectures.",
            'competency_map' => "Sessions in {$subject} are mapped to specific competencies: early sessions build foundational knowledge and terminology, mid-course sessions develop applied technical skills through hands-on practice, and later sessions target analysis, evaluation, and independent problem-solving. Each competency builds cumulatively on the previous one, culminating in the final assessment and project deliverables.",
            'video_overview' => "A short video overview introducing the goals, structure, and expectations of {$subject} is available to students at the start of the term, giving a walkthrough of the syllabus, grading breakdown, and weekly session cadence.",
        ]);

        $this->seedClassPolicies($syllabus);
        $learningOutcomes = $this->seedLearningOutcomes($syllabus, $subject);
        $this->seedEvaluations($syllabus, $learningOutcomes);
        $this->seedRubric($syllabus, $learningOutcomes);
    }

    private function seedClassPolicies(Syllabus $syllabus): void
    {
        $policies = [
            [
                'scope' => SyllabusPolicyScope::F2fVideo,
                'content' => 'Student must attend class, and participate in classroom discussions.',
            ],
            [
                'scope' => SyllabusPolicyScope::F2fVideo,
                'content' => 'The ringing, beeping, or buzzing of phones and watches during class time is extremely disruptive. Please turn off or silence all devices before coming to the classroom.',
            ],
            [
                'scope' => SyllabusPolicyScope::Online,
                'content' => "Student must be active in the classroom discussion forum, responding to the lecturer's questions and discussing with classmates.",
            ],
            [
                'scope' => SyllabusPolicyScope::Online,
                'content' => 'Student must be active in the team room, especially when discussing team assignments.',
            ],
            [
                'scope' => SyllabusPolicyScope::General,
                'content' => 'Student must read the learning material and other references before class. Reading materials and cases will be distributed ahead of time.',
            ],
            [
                'scope' => SyllabusPolicyScope::General,
                'content' => 'Student must complete and submit all personal and team assignments by the posted deadline.',
            ],
            [
                'scope' => SyllabusPolicyScope::General,
                'content' => 'Penalties for cheating and plagiarism are extremely severe. If unsure about a certain activity, consult the instructor first. Standard academic honesty procedures will be followed.',
            ],
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
    private function seedLearningOutcomes(Syllabus $syllabus, string $subject): array
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
    private function seedEvaluations(Syllabus $syllabus, array $learningOutcomes): void
    {
        /** @var SyllabusEvaluation $evaluation */
        $evaluation = $syllabus->evaluations()->create([
            'class_type' => 'LEC',
            'order' => 1,
        ]);

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
    private function seedRubric(Syllabus $syllabus, array $learningOutcomes): void
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
                [
                    'code' => ($loIndex + 1).'.1',
                    'description' => "Ability to describe the core concepts covered in {$learningOutcome->code}.",
                ],
                [
                    'code' => ($loIndex + 1).'.2',
                    'description' => "Ability to apply the practices and techniques covered in {$learningOutcome->code}.",
                ],
            ];

            foreach ($keyIndicators as $kiOrder => $keyIndicatorData) {
                /** @var SyllabusRubricKeyIndicator $keyIndicator */
                $keyIndicator = $learningOutcome->rubricKeyIndicators()->create([
                    'code' => $keyIndicatorData['code'],
                    'description' => $keyIndicatorData['description'],
                    'order' => $kiOrder + 1,
                ]);

                foreach ($proficiencyLevels as $levelIndex => $proficiencyLevel) {
                    /** @var SyllabusRubricProficiencyLevel $proficiencyLevel */
                    $keyIndicator->cells()->create([
                        'rubric_proficiency_level_id' => $proficiencyLevel->id,
                        'description' => $criteriaTemplates[$levelIndex],
                    ]);
                }
            }
        }
    }
}
