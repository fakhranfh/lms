<?php

use App\Livewire\Admin\AdminStorageMaterials;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Module;
use App\Models\School;
use App\Models\User;
use App\Services\StorageMonitoringService;
use Livewire\Livewire;

function createMaterialUnder(School $school, string $courseTitle, string $moduleTitle, string $lessonTitle, string $materialTitle): LessonMaterial
{
    $course = Course::factory()->for($school)->create(['title' => $courseTitle]);
    $module = Module::factory()->for($course)->create(['title' => $moduleTitle]);
    $lesson = Lesson::factory()->for($module)->create(['title' => $lessonTitle]);

    return LessonMaterial::factory()->withLesson($lesson)->create(['title' => $materialTitle]);
}

test('admin can view the materials page', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    $response = $this->actingAs($admin)->get('http://admin.lms.local/storage/materials');

    $response->assertStatus(200);
});

test('non-admin cannot view the materials page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('http://admin.lms.local/storage/materials');

    $response->assertStatus(403);
});

test('filters materials by school', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();
    createMaterialUnder($schoolA, 'Course A', 'Module A', 'Lesson A', 'Material In School A');
    createMaterialUnder($schoolB, 'Course B', 'Module B', 'Lesson B', 'Material In School B');

    Livewire::actingAs($admin)
        ->test(AdminStorageMaterials::class)
        ->set('schoolId', $schoolA->id)
        ->assertSee('Material In School A')
        ->assertDontSee('Material In School B');
});

test('cascading filters reset dependent selections', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    $school = School::factory()->create();
    $material = createMaterialUnder($school, 'Course X', 'Module X', 'Lesson X', 'Cascading Material');

    Livewire::actingAs($admin)
        ->test(AdminStorageMaterials::class)
        ->set('schoolId', $school->id)
        ->set('courseId', $material->lesson->module->course_id)
        ->set('moduleId', $material->lesson->module_id)
        ->assertSet('lessonId', null)
        ->set('schoolId', $school->id)
        ->assertSet('courseId', null)
        ->assertSet('moduleId', null);
});

test('filters materials by name search', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    $school = School::factory()->create();
    createMaterialUnder($school, 'Course', 'Module', 'Lesson', 'Unique Searchable Title');
    createMaterialUnder($school, 'Course 2', 'Module 2', 'Lesson 2', 'Something Else Entirely');

    Livewire::actingAs($admin)
        ->test(AdminStorageMaterials::class)
        ->set('search', 'Searchable')
        ->assertSee('Unique Searchable Title')
        ->assertDontSee('Something Else Entirely');
});

test('school query string parameter pre-filters the page', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();
    createMaterialUnder($schoolA, 'Course A', 'Module A', 'Lesson A', 'Material In School A');
    createMaterialUnder($schoolB, 'Course B', 'Module B', 'Lesson B', 'Material In School B');

    $response = $this->actingAs($admin)->get('http://admin.lms.local/storage/materials?school='.$schoolA->id);

    $response->assertSee('Material In School A')
        ->assertDontSee('Material In School B');
});

test('changing per page resets pagination and limits results', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    $school = School::factory()->create();
    foreach (range(1, 12) as $i) {
        createMaterialUnder($school, "Course {$i}", "Module {$i}", "Lesson {$i}", "Material {$i}");
    }

    Livewire::actingAs($admin)
        ->test(AdminStorageMaterials::class)
        ->assertSet('perPage', 15)
        ->set('perPage', 10)
        ->assertSet('perPage', 10)
        ->assertViewHas('formatBytes');

    expect(app(StorageMonitoringService::class)
        ->filteredMaterialsQuery(['school_id' => $school->id])
        ->paginate(10)
        ->count())->toBe(10);
});

test('clicking a sortable column toggles direction', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    Livewire::actingAs($admin)
        ->test(AdminStorageMaterials::class)
        ->assertSet('sort', 'created_at')
        ->assertSet('direction', 'desc')
        ->call('sortBy', 'file_size')
        ->assertSet('sort', 'file_size')
        ->assertSet('direction', 'asc')
        ->call('sortBy', 'file_size')
        ->assertSet('direction', 'desc');
});

test('deleting a material removes it from the list', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    $school = School::factory()->create();
    $material = createMaterialUnder($school, 'Course', 'Module', 'Lesson', 'Material To Delete');

    Livewire::actingAs($admin)
        ->test(AdminStorageMaterials::class)
        ->assertSee('Material To Delete')
        ->call('deleteMaterial', $material->id)
        ->assertDontSee('Material To Delete');
});
