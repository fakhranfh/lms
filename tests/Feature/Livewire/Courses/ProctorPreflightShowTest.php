<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\RoleName;
use App\Livewire\Courses\ProctorPreflightShow;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\FinalExam;
use App\Models\Period;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\ProctorSessionService;
use Livewire\Livewire;
use Tests\TestCase;

class ProctorPreflightShowTest extends TestCase
{
    private School $school;

    private User $student;

    private Course $course;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->student = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();

        $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $this->school->id]);
        $this->student->assignRole($studentRole);

        CoursePerson::factory()->for($this->course)->student()->create(['user_id' => $this->student->id]);

        $this->assessment = Assessment::factory()->for($this->course)->create([
            'type' => AssessmentType::TheoryFinalExam,
        ]);
    }

    public function test_open_book_exam_shows_preflight_checks(): void
    {
        $period = Period::factory()->for($this->course)->create();
        FinalExam::factory()->for($this->assessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::OpenBook,
        ]);

        $this->student->givePermissionTo('assessment.view');
        $this->actingAs($this->student);

        Livewire::test(ProctorPreflightShow::class, ['assessment' => $this->assessment])
            ->assertStatus(200)
            ->assertSee($this->assessment->title);
    }

    public function test_all_checks_must_pass_before_starting(): void
    {
        $period = Period::factory()->for($this->course)->create();
        FinalExam::factory()->for($this->assessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::ClosedBook,
        ]);

        $this->student->givePermissionTo('assessment.view');
        $this->actingAs($this->student);

        $component = Livewire::test(ProctorPreflightShow::class, ['assessment' => $this->assessment]);
        $this->assertFalse($component->get('allChecksPassed'));

        $component->call('markCheckPassed', 'speed')
            ->call('markCheckPassed', 'camera')
            ->call('markCheckPassed', 'face')
            ->call('markCheckPassed', 'screen');

        $this->assertTrue($component->get('allChecksPassed'));
        $this->assertTrue(app(ProctorSessionService::class)->hasPassedPreflight($this->assessment->id, $this->student->id));
    }

    public function test_failing_a_check_clears_the_preflight_passed_flag(): void
    {
        $period = Period::factory()->for($this->course)->create();
        FinalExam::factory()->for($this->assessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::ClosedBook,
        ]);

        $this->student->givePermissionTo('assessment.view');
        $this->actingAs($this->student);

        Livewire::test(ProctorPreflightShow::class, ['assessment' => $this->assessment])
            ->call('markCheckPassed', 'speed')
            ->call('markCheckPassed', 'camera')
            ->call('markCheckPassed', 'face')
            ->call('markCheckPassed', 'screen')
            ->call('markCheckFailed', 'screen');

        $this->assertFalse(app(ProctorSessionService::class)->hasPassedPreflight($this->assessment->id, $this->student->id));
    }

    public function test_redirects_to_exam_page_when_preflight_already_passed(): void
    {
        $period = Period::factory()->for($this->course)->create();
        FinalExam::factory()->for($this->assessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::ClosedBook,
        ]);

        app(ProctorSessionService::class)->markPreflightPassed($this->assessment->id, $this->student->id);

        $this->student->givePermissionTo('assessment.view');
        $this->actingAs($this->student);

        Livewire::test(ProctorPreflightShow::class, ['assessment' => $this->assessment])
            ->assertRedirect(route('assessments.final-exam.proctor.show', $this->assessment));
    }

    public function test_standard_exam_type_redirects_away(): void
    {
        $period = Period::factory()->for($this->course)->create();
        FinalExam::factory()->for($this->assessment)->create([
            'period_id' => $period->id,
            'exam_type' => FinalExamType::TakeHome,
        ]);

        $this->student->givePermissionTo('assessment.view');
        $this->actingAs($this->student);

        Livewire::test(ProctorPreflightShow::class, ['assessment' => $this->assessment])
            ->assertRedirect(route('assessments.final-exam.show', $this->assessment));
    }
}
