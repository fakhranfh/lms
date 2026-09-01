<?php

namespace Tests\Feature\Livewire\Courses;

use App\Livewire\Courses\SessionForm;
use App\Models\Course;
use App\Models\MediaLibraryItem;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use App\Services\MediaLibraryService;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SessionFormTest extends TestCase
{
    private School $school;

    private User $teacher;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->teacher = User::factory()->forSchool($this->school)->create();
        $this->course = Course::factory()->for($this->school)->create();

        $this->actingAs($this->teacher);
    }

    public function test_create_session_component_renders(): void
    {
        $this->teacher->givePermissionTo('sessions.create');

        Livewire::test(SessionForm::class, ['course' => $this->course])
            ->assertStatus(200)
            ->assertSee('Create Session');
    }

    public function test_edit_session_component_renders(): void
    {
        $session = Session::factory()->for($this->course)->create(['title' => 'Existing Session']);

        $this->teacher->givePermissionTo('sessions.edit');

        Livewire::test(SessionForm::class, ['course' => $this->course, 'session' => $session])
            ->assertStatus(200)
            ->assertSee('Edit Session')
            ->assertSet('title', 'Existing Session');
    }

    public function test_can_create_session_with_subtopics_and_video_conference(): void
    {
        $this->teacher->givePermissionTo('sessions.create');

        Livewire::test(SessionForm::class, ['course' => $this->course])
            ->set('title', 'Session 1: Introduction')
            ->set('learningOutcome', 'Understand the basics')
            ->set('dateStart', '2026-09-01T09:00')
            ->set('dateEnd', '2026-09-08T09:00')
            ->set('deliveryMode', 'online')
            ->call('addSubtopic')
            ->set('subtopics.0', 'Overview')
            ->call('addVideoConference')
            ->set('videoConferences.0.title', 'Main Meeting')
            ->set('videoConferences.0.scheduled_start_at', '2026-09-01T09:00')
            ->set('videoConferences.0.scheduled_end_at', '2026-09-01T11:00')
            ->call('save')
            ->assertRedirect(route('sessions.index', $this->course));

        $this->assertDatabaseHas('course_sessions', [
            'course_id' => $this->course->id,
            'title' => 'Session 1: Introduction',
            'delivery_mode' => 'online',
        ]);

        $session = Session::where('title', 'Session 1: Introduction')->firstOrFail();

        $this->assertDatabaseHas('session_subtopics', [
            'session_id' => $session->id,
            'subtopic' => 'Overview',
        ]);

        $this->assertDatabaseHas('video_conferences', [
            'session_id' => $session->id,
            'title' => 'Main Meeting',
        ]);
    }

    public function test_can_attach_media_library_item_to_session(): void
    {
        $this->teacher->givePermissionTo('sessions.create');

        $mediaItem = MediaLibraryItem::factory()->for($this->school)->create();

        Livewire::test(SessionForm::class, ['course' => $this->course])
            ->set('title', 'Session with material')
            ->set('dateStart', '2026-09-01T09:00')
            ->set('dateEnd', '2026-09-08T09:00')
            ->set('selectedMaterialIds', [$mediaItem->id])
            ->call('save');

        $session = Session::where('title', 'Session with material')->firstOrFail();

        $this->assertTrue($session->materials()->where('media_library_items.id', $mediaItem->id)->exists());
    }

    public function test_can_upload_material_directly_and_add_to_selection(): void
    {
        $this->teacher->givePermissionTo(['sessions.create', 'media.create']);

        $uploadedItem = MediaLibraryItem::factory()->for($this->school)->create(['title' => 'Uploaded Slides']);

        $this->mock(MediaLibraryService::class, function ($mock) use ($uploadedItem) {
            $mock->shouldReceive('finalizeUpload')->once()->andReturn($uploadedItem);
            $mock->shouldReceive('list')->andReturn(MediaLibraryItem::query()->where('school_id', $this->school->id));
        });

        $component = Livewire::test(SessionForm::class, ['course' => $this->course]);

        $result = $component->instance()->finalizeMaterialUpload([
            'type' => 'PDF',
            'temp_key' => 'temp/media/fake.pdf',
        ], app(MediaLibraryService::class));

        expect($result)->toMatchArray([
            'id' => $uploadedItem->id,
            'title' => 'Uploaded Slides',
        ]);

        $component->assertSet('selectedMaterialIds', [$uploadedItem->id]);
    }

    public function test_uploaded_material_is_persisted_when_session_is_saved(): void
    {
        $this->teacher->givePermissionTo(['sessions.create', 'media.create']);

        $uploadedItem = MediaLibraryItem::factory()->for($this->school)->create();

        $this->mock(MediaLibraryService::class, function ($mock) use ($uploadedItem) {
            $mock->shouldReceive('finalizeUpload')->once()->andReturn($uploadedItem);
            $mock->shouldReceive('list')->andReturn(MediaLibraryItem::query()->where('school_id', $this->school->id));
        });

        Livewire::test(SessionForm::class, ['course' => $this->course])
            ->set('title', 'Session with uploaded material')
            ->set('dateStart', '2026-09-01T09:00')
            ->set('dateEnd', '2026-09-08T09:00')
            ->call('finalizeMaterialUpload', [
                'type' => 'PDF',
                'temp_key' => 'temp/media/fake.pdf',
            ])
            ->call('save');

        $session = Session::where('title', 'Session with uploaded material')->firstOrFail();

        $this->assertTrue($session->materials()->where('media_library_items.id', $uploadedItem->id)->exists());
    }

    public function test_finalize_material_upload_returns_error_when_validation_fails(): void
    {
        $this->teacher->givePermissionTo(['sessions.create', 'media.create']);

        $this->mock(MediaLibraryService::class, function ($mock) {
            $mock->shouldReceive('finalizeUpload')
                ->andThrow(new \Exception('File content does not match PDF format.'));
            $mock->shouldReceive('list')->andReturn(MediaLibraryItem::query()->where('school_id', $this->school->id));
        });

        $component = Livewire::test(SessionForm::class, ['course' => $this->course]);

        $result = $component->instance()->finalizeMaterialUpload([
            'type' => 'PDF',
            'temp_key' => 'temp/media/fake.pdf',
        ], app(MediaLibraryService::class));

        expect($result)->toHaveKey('error');
        expect($result['error'])->toBe('File content does not match PDF format.');
        $component->assertSet('selectedMaterialIds', []);
    }

    public function test_user_without_media_create_cannot_upload_material(): void
    {
        $this->teacher->givePermissionTo('sessions.create');

        $component = Livewire::test(SessionForm::class, ['course' => $this->course]);

        expect(fn () => $component->instance()->generateMaterialUploadUrl('notes.pdf', 'PDF', app(MediaLibraryService::class)))
            ->toThrow(HttpException::class);
    }

    public function test_can_update_session(): void
    {
        $session = Session::factory()->for($this->course)->create(['title' => 'Old Title']);

        $this->teacher->givePermissionTo('sessions.edit');

        Livewire::test(SessionForm::class, ['course' => $this->course, 'session' => $session])
            ->set('title', 'New Title')
            ->call('save')
            ->assertRedirect(route('sessions.index', $this->course));

        $this->assertDatabaseHas('course_sessions', [
            'id' => $session->id,
            'title' => 'New Title',
        ]);
    }

    public function test_required_fields_validation(): void
    {
        $this->teacher->givePermissionTo('sessions.create');

        Livewire::test(SessionForm::class, ['course' => $this->course])
            ->set('title', '')
            ->set('dateStart', '')
            ->set('dateEnd', '')
            ->call('save')
            ->assertHasErrors(['title', 'dateStart', 'dateEnd']);
    }

    public function test_user_cannot_access_form_without_permission(): void
    {
        Livewire::test(SessionForm::class, ['course' => $this->course])
            ->assertStatus(403);
    }

    public function test_user_cannot_create_session_in_different_school_course(): void
    {
        $otherSchool = School::factory()->create();
        $otherCourse = Course::factory()->for($otherSchool)->create();

        $this->teacher->givePermissionTo('sessions.create');

        Livewire::test(SessionForm::class, ['course' => $otherCourse])
            ->assertStatus(403);
    }
}
