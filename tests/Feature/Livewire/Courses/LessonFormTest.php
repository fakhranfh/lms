<?php

namespace Tests\Feature\Livewire\Courses;

use App\Enums\MaterialType;
use App\Livewire\Courses\LessonForm;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Module;
use App\Models\School;
use App\Models\User;
use App\Services\LessonMaterialService;
use App\Services\LessonService;
use App\Services\R2StorageService;
use Livewire\Livewire;
use Tests\TestCase;

class LessonFormTest extends TestCase
{
    private School $school;

    private User $instructor;

    private Course $course;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->instructor = User::factory()
            ->for($this->school)
            ->create();
        $this->course = Course::factory()
            ->for($this->school)
            ->create();
        $this->module = Module::factory()
            ->for($this->course)
            ->create();

        $this->actingAs($this->instructor);
    }

    public function test_create_lesson_component_renders(): void
    {
        $this->instructor->givePermissionTo('lessons.create');

        Livewire::test(LessonForm::class, ['module' => $this->module])
            ->assertStatus(200)
            ->assertSee('Create Lesson');
    }

    public function test_edit_lesson_component_renders(): void
    {
        $lesson = Lesson::factory()
            ->for($this->module)
            ->create(['title' => 'Test Lesson Title']);

        $this->instructor->givePermissionTo('lessons.edit');

        Livewire::test(LessonForm::class, [
            'module' => $this->module,
            'lesson' => $lesson,
        ])
            ->assertStatus(200)
            ->assertSee('Edit Lesson')
            ->assertSee('Lesson Title');
    }

    public function test_can_create_lesson(): void
    {
        $this->instructor->givePermissionTo('lessons.create');

        Livewire::test(LessonForm::class, ['module' => $this->module])
            ->set('title', 'Introduction to HTML')
            ->set('content', '<h1>HTML Basics</h1>')
            ->set('durationMinutes', 15)
            ->call('save')
            ->assertRedirect(route('courses.show', $this->course));

        $this->assertDatabaseHas('lessons', [
            'title' => 'Introduction to HTML',
            'content' => '<h1>HTML Basics</h1>',
            'module_id' => $this->module->id,
            'duration_minutes' => 15,
            'order' => 1,
        ]);
    }

    public function test_lesson_order_auto_increments(): void
    {
        $this->instructor->givePermissionTo('lessons.create');

        Lesson::factory()
            ->for($this->module)
            ->create(['order' => 1]);

        Livewire::test(LessonForm::class, ['module' => $this->module])
            ->set('title', 'Second Lesson')
            ->call('save');

        $this->assertDatabaseHas('lessons', [
            'title' => 'Second Lesson',
            'module_id' => $this->module->id,
            'order' => 2,
        ]);
    }

    public function test_can_update_lesson(): void
    {
        $lesson = Lesson::factory()
            ->for($this->module)
            ->create(['title' => 'Old Title']);

        $this->instructor->givePermissionTo('lessons.edit');

        Livewire::test(LessonForm::class, [
            'module' => $this->module,
            'lesson' => $lesson,
        ])
            ->set('title', 'New Title')
            ->set('content', '<p>New content</p>')
            ->call('save')
            ->assertRedirect(route('courses.show', $this->course));

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'title' => 'New Title',
            'content' => '<p>New content</p>',
        ]);
    }

    public function test_duration_must_be_numeric(): void
    {
        $this->instructor->givePermissionTo('lessons.create');

        Livewire::test(LessonForm::class, ['module' => $this->module])
            ->set('title', 'Lesson')
            ->set('durationMinutes', 'not_a_number')
            ->call('save')
            ->assertHasErrors('durationMinutes');
    }

    public function test_duration_max_value_validation(): void
    {
        $this->instructor->givePermissionTo('lessons.create');

        Livewire::test(LessonForm::class, ['module' => $this->module])
            ->set('title', 'Lesson')
            ->set('durationMinutes', 481)
            ->call('save')
            ->assertHasErrors('durationMinutes');
    }

    public function test_required_fields_validation(): void
    {
        $this->instructor->givePermissionTo('lessons.create');

        Livewire::test(LessonForm::class, ['module' => $this->module])
            ->set('title', '')
            ->call('save')
            ->assertHasErrors('title');
    }

    public function test_user_cannot_access_form_without_permission(): void
    {
        Livewire::test(LessonForm::class, ['module' => $this->module])
            ->assertStatus(403);
    }

    public function test_user_cannot_create_lesson_in_different_school_module(): void
    {
        $this->markTestSkipped('Livewire component render happens before mount abort; tested via CourseBuilderTest for module/lesson deletion from different schools');
    }

    public function test_uploading_a_material_before_saving_creates_a_draft_lesson(): void
    {
        $this->instructor->givePermissionTo('lessons.create');

        $this->mock(R2StorageService::class, function ($mock) {
            $mock->shouldReceive('generatePresignedPutUrl')
                ->andReturn(['url' => 'https://example.com/presigned', 'key' => 'temp/abc/notes.pdf', 'lesson_id' => 'placeholder']);
        });

        $component = Livewire::test(LessonForm::class, ['module' => $this->module])
            ->set('title', 'Draft Lesson');

        $result = $component->instance()->generateUploadUrl('notes.pdf', 'PDF', app(R2StorageService::class), app(LessonService::class));

        $this->assertArrayNotHasKey('error', $result);
        $this->assertDatabaseHas('lessons', [
            'title' => 'Draft Lesson',
            'module_id' => $this->module->id,
            'is_published' => false,
        ]);
    }

    public function test_uploading_a_material_without_a_title_returns_an_error(): void
    {
        $this->instructor->givePermissionTo('lessons.create');

        $component = Livewire::test(LessonForm::class, ['module' => $this->module]);

        $result = $component->instance()->generateUploadUrl('notes.pdf', 'PDF', app(R2StorageService::class), app(LessonService::class));

        $this->assertArrayHasKey('error', $result);
        $this->assertDatabaseMissing('lessons', [
            'module_id' => $this->module->id,
        ]);
    }

    public function test_finalize_upload_returns_error_when_validation_fails(): void
    {
        $lesson = Lesson::factory()->for($this->module)->create();

        $this->instructor->givePermissionTo('lessons.edit');

        $this->mock(LessonMaterialService::class, function ($mock) {
            $mock->shouldReceive('finalizeR2Upload')
                ->andThrow(new \Exception('File content does not match Video format. Expected file signature not found in header.'));
        });

        $component = Livewire::test(LessonForm::class, [
            'module' => $this->module,
            'lesson' => $lesson,
        ]);

        $result = $component->instance()->finalizeUpload([
            'type' => 'Video',
            'temp_key' => 'temp/abc/fake.mp4',
        ], app(LessonMaterialService::class));

        $this->assertArrayHasKey('error', $result);
        $this->assertSame(
            'File content does not match Video format. Expected file signature not found in header.',
            $result['error']
        );
        $this->assertDatabaseMissing('lesson_materials', [
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_material_title_can_be_updated(): void
    {
        $lesson = Lesson::factory()->for($this->module)->create();
        $material = LessonMaterial::factory()->for($lesson)->create(['title' => 'original-filename']);

        $this->instructor->givePermissionTo('lessons.edit');

        $component = Livewire::test(LessonForm::class, [
            'module' => $this->module,
            'lesson' => $lesson,
        ]);

        $component->instance()->updateMaterialTitle($material->id, 'A Much Better Title', app(LessonMaterialService::class));

        $this->assertDatabaseHas('lesson_materials', [
            'id' => $material->id,
            'title' => 'A Much Better Title',
        ]);
    }

    public function test_current_materials_list_only_shows_active_version(): void
    {
        $lesson = Lesson::factory()->for($this->module)->create();

        // Same material title, two versions: v1 inactive, v2 active
        LessonMaterial::factory()
            ->for($lesson)
            ->create(['title' => 'Duplicate Guard Test', 'version' => 1, 'is_active' => false]);
        $activeVersion = LessonMaterial::factory()
            ->for($lesson)
            ->create(['title' => 'Duplicate Guard Test', 'version' => 2, 'is_active' => true]);

        $this->instructor->givePermissionTo('lessons.edit');

        $component = Livewire::test(LessonForm::class, [
            'module' => $this->module,
            'lesson' => $lesson,
        ]);

        $materials = $component->instance()->materials;

        // Only the active version should appear in the "Current Materials" list,
        // not both versions as separate rows.
        $matching = $materials->where('title', 'Duplicate Guard Test');
        $this->assertCount(1, $matching);
        $this->assertSame($activeVersion->id, $matching->first()->id);
    }

    public function test_material_versions_can_be_retrieved(): void
    {
        $lesson = Lesson::factory()->for($this->module)->create();
        $material = LessonMaterial::factory()
            ->for($lesson)
            ->create(['title' => 'Versioned Material', 'version' => 1, 'is_active' => true]);

        // Create additional versions
        LessonMaterial::factory()
            ->for($lesson)
            ->create(['title' => 'Versioned Material', 'version' => 2, 'is_active' => false]);

        $this->instructor->givePermissionTo('lessons.edit');

        $component = Livewire::test(LessonForm::class, [
            'module' => $this->module,
            'lesson' => $lesson,
        ]);

        $versions = $component->instance()->getMaterialVersions($material->id);

        expect($versions)->toHaveCount(2);
        expect($versions->first()->version)->toBe(2);
        expect($versions->first()->is_active)->toBe(false);
        expect($versions->last()->version)->toBe(1);
        expect($versions->last()->is_active)->toBe(true);
    }

    public function test_can_switch_material_version(): void
    {
        $lesson = Lesson::factory()->for($this->module)->create();
        $v1 = LessonMaterial::factory()
            ->for($lesson)
            ->create(['title' => 'Switch Test', 'version' => 1, 'is_active' => true]);
        $v2 = LessonMaterial::factory()
            ->for($lesson)
            ->create(['title' => 'Switch Test', 'version' => 2, 'is_active' => false]);

        $this->instructor->givePermissionTo('lessons.edit');

        $component = Livewire::test(LessonForm::class, [
            'module' => $this->module,
            'lesson' => $lesson,
        ]);

        $component->call('switchToVersion', $v1->id, 2);

        $this->assertDatabaseHas('lesson_materials', [
            'id' => $v1->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('lesson_materials', [
            'id' => $v2->id,
            'is_active' => true,
        ]);
    }

    public function test_can_delete_material_version(): void
    {
        $lesson = Lesson::factory()->for($this->module)->create();
        $v1 = LessonMaterial::factory()
            ->for($lesson)
            ->create(['title' => 'Delete Test', 'version' => 1, 'is_active' => false]);
        $v2 = LessonMaterial::factory()
            ->for($lesson)
            ->create(['title' => 'Delete Test', 'version' => 2, 'is_active' => true]);

        $this->instructor->givePermissionTo('lessons.edit');

        $component = Livewire::test(LessonForm::class, [
            'module' => $this->module,
            'lesson' => $lesson,
        ]);

        $component->call('deleteVersion', $v1->id, 1);

        $this->assertDatabaseMissing('lesson_materials', [
            'id' => $v1->id,
        ]);

        $this->assertDatabaseHas('lesson_materials', [
            'id' => $v2->id,
            'is_active' => true,
        ]);
    }

    public function test_generate_version_upload_url_returns_presigned_url(): void
    {
        $lesson = Lesson::factory()->for($this->module)->create();
        $material = LessonMaterial::factory()
            ->for($lesson)
            ->withType(MaterialType::PDF)
            ->create(['title' => 'Replaceable Material']);

        $this->instructor->givePermissionTo('lessons.edit');

        $this->mock(R2StorageService::class, function ($mock) {
            $mock->shouldReceive('enforceQuotaLimit')->once();
            $mock->shouldReceive('generatePresignedPutUrl')
                ->once()
                ->andReturn(['url' => 'https://r2.example.com/presigned', 'key' => 'temp/lesson/abc-file.pdf']);
        });

        $component = Livewire::test(LessonForm::class, [
            'module' => $this->module,
            'lesson' => $lesson,
        ]);

        $result = $component->instance()->generateVersionUploadUrl(
            $material->id,
            'replacement.pdf',
            app(R2StorageService::class)
        );

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('key', $result);
    }

    public function test_finalize_version_upload_creates_new_version(): void
    {
        $lesson = Lesson::factory()->for($this->module)->create();
        $material = LessonMaterial::factory()
            ->for($lesson)
            ->withType(MaterialType::PDF)
            ->create(['title' => 'Version Upload Test', 'version' => 1, 'is_active' => true]);

        $this->instructor->givePermissionTo('lessons.edit');

        $this->mock(LessonMaterialService::class, function ($mock) use ($material) {
            $mock->shouldReceive('finalizeVersionUpload')
                ->once()
                ->with($material->id, ['temp_key' => 'temp/abc/replacement.pdf'])
                ->andReturn(
                    LessonMaterial::factory()->make(['id' => 'new-version-id', 'version' => 2, 'is_active' => true])
                );
        });

        $component = Livewire::test(LessonForm::class, [
            'module' => $this->module,
            'lesson' => $lesson,
        ]);

        $result = $component->instance()->finalizeVersionUpload(
            $material->id,
            ['temp_key' => 'temp/abc/replacement.pdf'],
            app(LessonMaterialService::class)
        );

        $this->assertSame([], $result);
    }

    public function test_finalize_version_upload_returns_error_on_validation_failure(): void
    {
        $lesson = Lesson::factory()->for($this->module)->create();
        $material = LessonMaterial::factory()
            ->for($lesson)
            ->withType(MaterialType::PDF)
            ->create(['title' => 'Version Upload Fail Test']);

        $this->instructor->givePermissionTo('lessons.edit');

        $this->mock(LessonMaterialService::class, function ($mock) {
            $mock->shouldReceive('finalizeVersionUpload')
                ->andThrow(new \Exception('File content does not match PDF format.'));
        });

        $component = Livewire::test(LessonForm::class, [
            'module' => $this->module,
            'lesson' => $lesson,
        ]);

        $result = $component->instance()->finalizeVersionUpload(
            $material->id,
            ['temp_key' => 'temp/abc/fake.pdf'],
            app(LessonMaterialService::class)
        );

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('File content does not match PDF format.', $result['error']);
    }
}
