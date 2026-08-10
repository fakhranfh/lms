<?php

namespace Database\Seeders;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\CourseMembershipStatus;
use App\Enums\RoleInCourse;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Group;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AssessmentSeeder extends Seeder
{
    /**
     * Seed a Personal Assignment and a Team Assignment (with groups, questions,
     * and a mix of not-started/submitted/graded attempts) for existing courses
     * that don't have any assessments yet.
     */
    public function run(): void
    {
        $courses = Course::doesntHave('assessments')->get();

        foreach ($courses as $course) {
            $this->seedCourseAssessments($course);
        }
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
