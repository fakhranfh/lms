<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\DeliveryMode;
use App\Enums\RoleName;
use App\Livewire\Courses\AssessmentForumDiscussionShow;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Forum;
use App\Models\ForumComment;
use App\Models\ForumThread;
use App\Models\Role;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentForumDiscussionShowTest extends TestCase
{
    private School $school;

    private User $teacher;

    private User $student;

    private Course $course;

    private Assessment $assessment;

    private Session $session;

    private Forum $forum;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();
        $this->session = Session::factory()->create(['course_id' => $this->course->id, 'delivery_mode' => DeliveryMode::Online, 'required_forum_posts' => 2]);
        $this->forum = Forum::factory()->create(['course_id' => $this->course->id, 'session_id' => $this->session->id]);

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);

        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $this->student->assignRole($studentRole);

        $this->assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::ForumDiscussion,
            'weight' => 10,
            'start_date' => null,
            'end_date' => null,
        ]);
    }

    public function test_student_sees_derived_score_and_it_is_persisted(): void
    {
        $this->student->givePermissionTo(['assessment.view']);
        $this->actingAs($this->student);

        $thread = ForumThread::factory()->create(['forum_id' => $this->forum->id, 'user_id' => $this->student->id]);
        ForumComment::factory()->create(['thread_id' => $thread->id, 'user_id' => $this->student->id]);

        Livewire::test(AssessmentForumDiscussionShow::class, ['assessment' => $this->assessment])
            ->assertSee($this->session->title)
            ->assertSee('Completed')
            ->assertSee('100 pts');

        $this->assertDatabaseHas('assessment_attempts', [
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->student->id,
        ]);
        $this->assertDatabaseHas('assessment_scores', ['score' => 10]);
    }

    public function test_teacher_sees_per_session_summary(): void
    {
        $this->teacher->givePermissionTo(['assessment.view']);
        $this->actingAs($this->teacher);

        Livewire::test(AssessmentForumDiscussionShow::class, ['assessment' => $this->assessment])
            ->assertSee($this->session->title)
            ->assertSee('0 of 1 students met the 2-post requirement');
    }

    public function test_non_forum_discussion_assessment_404s(): void
    {
        $this->teacher->givePermissionTo(['assessment.view']);
        $this->actingAs($this->teacher);

        $other = Assessment::factory()->for($this->course)->create(['type' => AssessmentType::TheoryQuiz]);

        Livewire::test(AssessmentForumDiscussionShow::class, ['assessment' => $other])
            ->assertStatus(404);
    }
}
