<?php

use App\Enums\RoleName;
use App\Livewire\Teachers\TeacherForm;
use App\Livewire\Teachers\TeacherGenerate;
use App\Livewire\Teachers\TeacherIndex;
use App\Models\Role;
use App\Models\User;
use App\Models\UserLoginLink;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Creates a school-admin-like actor with the given teacher-management
 * permissions, scoped to their own school.
 *
 * @param  array<int, string>  $permissionNames
 */
function actingAsTeacherManager(array $permissionNames): User
{
    $user = User::factory()->create();

    $role = Role::create(['name' => 'teacher-manager-'.uniqid(), 'guard_name' => 'web', 'school_id' => $user->school_id]);

    foreach ($permissionNames as $permissionName) {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']));
    }

    $user->assignRole($role);

    return $user;
}

function makeTeacher(User $actor, array $attributes = []): User
{
    $teacher = User::factory()->create(array_merge(['school_id' => $actor->school_id], $attributes));

    $teacherRole = Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);
    $teacher->assignRole($teacherRole);

    return $teacher;
}

test('teachers index lists only teachers, not other roles', function () {
    $actor = actingAsTeacherManager(['teachers.view']);
    $teacher = makeTeacher($actor, ['name' => 'Teacher One']);

    $student = User::factory()->create(['name' => 'Student One', 'school_id' => $actor->school_id]);
    $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);
    $student->assignRole($studentRole);

    Livewire::actingAs($actor)->test(TeacherIndex::class)
        ->call('loadUsers')
        ->assertSee('Teacher One')
        ->assertDontSee('Student One');
});

test('teachers index paginates results', function () {
    $actor = actingAsTeacherManager(['teachers.view']);

    User::factory()->count(20)->create(['school_id' => $actor->school_id])
        ->each(function (User $teacher) use ($actor) {
            $teacherRole = Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);
            $teacher->assignRole($teacherRole);
        });

    $component = Livewire::actingAs($actor)->test(TeacherIndex::class)
        ->call('loadUsers')
        ->set('perPage', 10);

    expect($component->get('teachers')->count())->toBe(10);
});

test('teachers index search filters by name or email', function () {
    $actor = actingAsTeacherManager(['teachers.view']);
    makeTeacher($actor, ['name' => 'Findable Teacher', 'email' => 'findable-teacher@example.com']);
    makeTeacher($actor, ['name' => 'Other Teacher', 'email' => 'other-teacher@example.com']);

    Livewire::actingAs($actor)->test(TeacherIndex::class)
        ->call('loadUsers')
        ->set('search', 'Findable')
        ->assertSee('Findable Teacher')
        ->assertDontSee('Other Teacher');
});

test('teachers index requires teachers.view permission', function () {
    $actor = User::factory()->create();

    test()->actingAs($actor)
        ->get(route('teachers.index'))
        ->assertForbidden();
});

test('creating a teacher assigns the teacher role and generates a login link with must_change_password', function () {
    $actor = actingAsTeacherManager(['teachers.create']);
    Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);

    Livewire::actingAs($actor)->test(TeacherForm::class)
        ->set('name', 'New Teacher')
        ->set('email', 'new-teacher@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('save');

    $teacher = User::where('email', 'new-teacher@example.com')->first();

    expect($teacher)->not->toBeNull();
    expect($teacher->hasRole(RoleName::Teacher))->toBeTrue();
    expect($teacher->must_change_password)->toBeTrue();
    expect(UserLoginLink::where('user_id', $teacher->id)->exists())->toBeTrue();
});

test('creating a teacher without permission is forbidden', function () {
    $actor = User::factory()->create();

    Livewire::actingAs($actor)->test(TeacherForm::class)
        ->set('name', 'New Teacher')
        ->set('email', 'blocked-teacher@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('save')
        ->assertForbidden();
});

test('editing a teacher updates their profile', function () {
    $actor = actingAsTeacherManager(['teachers.edit']);
    $teacher = makeTeacher($actor, ['name' => 'Old Name', 'email' => 'old-teacher@example.com']);

    Livewire::actingAs($actor)->test(TeacherForm::class, ['id' => $teacher->id])
        ->set('name', 'New Name')
        ->set('email', 'old-teacher@example.com')
        ->call('save');

    expect($teacher->refresh()->name)->toBe('New Name');
});

test('editing a teacher without permission is forbidden', function () {
    $actor = actingAsTeacherManager(['teachers.view']);
    $teacher = makeTeacher($actor);

    Livewire::actingAs($actor)->test(TeacherForm::class, ['id' => $teacher->id])
        ->set('name', 'Blocked Update')
        ->set('email', $teacher->email)
        ->call('save')
        ->assertForbidden();
});

test('regenerating a login link sets a new login url for the teacher', function () {
    $actor = actingAsTeacherManager(['teachers.edit']);
    $teacher = makeTeacher($actor);

    $component = Livewire::actingAs($actor)->test(TeacherForm::class, ['id' => $teacher->id])
        ->call('regenerateLoginLink');

    expect($component->get('loginUrl'))->not->toBeNull();
    expect(UserLoginLink::where('user_id', $teacher->id)->exists())->toBeTrue();
});

test('regenerating a login link without permission is forbidden', function () {
    $actor = actingAsTeacherManager(['teachers.view']);
    $teacher = makeTeacher($actor);

    Livewire::actingAs($actor)->test(TeacherForm::class, ['id' => $teacher->id])
        ->call('regenerateLoginLink')
        ->assertForbidden();
});

test('regenerating a login link from the teachers index sets a new login url', function () {
    $actor = actingAsTeacherManager(['teachers.view', 'teachers.edit']);
    $teacher = makeTeacher($actor);

    $component = Livewire::actingAs($actor)->test(TeacherIndex::class)
        ->call('regenerateLoginLink', $teacher->id);

    expect($component->get('regeneratedLoginUrl'))->not->toBeNull();
    expect(UserLoginLink::where('user_id', $teacher->id)->exists())->toBeTrue();
});

test('regenerating a login link from the teachers index without permission is forbidden', function () {
    $actor = actingAsTeacherManager(['teachers.view']);
    $teacher = makeTeacher($actor);

    Livewire::actingAs($actor)->test(TeacherIndex::class)
        ->call('regenerateLoginLink', $teacher->id)
        ->assertForbidden();
});

test('exporting teachers to excel streams a download for matching teachers', function () {
    $actor = actingAsTeacherManager(['teachers.view']);
    makeTeacher($actor, ['name' => 'Excel Teacher', 'email' => 'excel-teacher@example.com']);

    Livewire::actingAs($actor)->test(TeacherIndex::class)
        ->call('exportExcel')
        ->assertFileDownloaded('teachers-'.now()->format('Y-m-d').'.xlsx');
});

test('exporting teachers to excel without permission is forbidden', function () {
    $actor = User::factory()->create();

    Livewire::actingAs($actor)->test(TeacherIndex::class)
        ->call('exportExcel')
        ->assertForbidden();
});

test('exporting teachers to pdf streams a download for matching teachers', function () {
    $actor = actingAsTeacherManager(['teachers.view']);
    makeTeacher($actor, ['name' => 'Pdf Teacher', 'email' => 'pdf-teacher@example.com']);

    Livewire::actingAs($actor)->test(TeacherIndex::class)
        ->call('exportPdf')
        ->assertFileDownloaded('teachers-'.now()->format('Y-m-d').'.pdf');
});

test('exporting teachers to pdf without permission is forbidden', function () {
    $actor = User::factory()->create();

    Livewire::actingAs($actor)->test(TeacherIndex::class)
        ->call('exportPdf')
        ->assertForbidden();
});

test('deleting a teacher requires teachers.delete permission', function () {
    $actor = actingAsTeacherManager(['teachers.view']);
    $teacher = makeTeacher($actor);

    Livewire::actingAs($actor)->test(TeacherIndex::class)
        ->call('destroy', $teacher->id)
        ->assertForbidden();
});

test('a manager with permission can delete a teacher', function () {
    $actor = actingAsTeacherManager(['teachers.view', 'teachers.delete']);
    $teacher = makeTeacher($actor);

    Livewire::actingAs($actor)->test(TeacherIndex::class)
        ->call('destroy', $teacher->id);

    expect(User::withTrashed()->find($teacher->id)->trashed())->toBeTrue();
});

test('importing teachers requires teachers.import permission', function () {
    $actor = User::factory()->create();

    test()->actingAs($actor)
        ->get(route('teachers.import'))
        ->assertForbidden();
});

test('teacher role no longer has student management permissions after the teachers permissions migration', function () {
    $school = User::factory()->create()->school_id;
    $teacherRole = Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $school]);

    foreach (['students.view', 'students.create', 'students.edit', 'students.delete', 'students.import'] as $permission) {
        expect($teacherRole->hasPermissionTo($permission))->toBeFalse();
    }
});

test('a teacher-role user gets 403 on the teachers and students routes, a school admin gets 200', function () {
    $teacherUser = User::factory()->create();
    $teacherRole = Role::create(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $teacherUser->school_id]);
    $teacherRole->givePermissionTo([
        Permission::firstOrCreate(['name' => 'teachers.view', 'guard_name' => 'web']),
        Permission::firstOrCreate(['name' => 'students.view', 'guard_name' => 'web']),
    ]);
    $teacherUser->assignRole($teacherRole);

    test()->actingAs($teacherUser)->get(route('teachers.index'))->assertForbidden();
    test()->actingAs($teacherUser)->get(route('students.index'))->assertForbidden();

    $schoolAdmin = User::factory()->create();
    $schoolAdminRole = Role::create(['name' => RoleName::SchoolAdmin->value, 'guard_name' => 'web', 'school_id' => $schoolAdmin->school_id]);
    $schoolAdminRole->givePermissionTo([
        Permission::firstOrCreate(['name' => 'teachers.view', 'guard_name' => 'web']),
        Permission::firstOrCreate(['name' => 'students.view', 'guard_name' => 'web']),
    ]);
    $schoolAdmin->assignRole($schoolAdminRole);

    test()->actingAs($schoolAdmin)->get(route('teachers.index'))->assertOk();
    test()->actingAs($schoolAdmin)->get(route('students.index'))->assertOk();
});

test('school admin default permission groups include students and teachers', function () {
    expect(RoleName::SchoolAdmin->permissionGroups())->toContain('Students', 'Teachers');
});

test('generating teachers creates the requested number with login links', function () {
    $actor = actingAsTeacherManager(['teachers.create']);
    $teacherRole = Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);

    Livewire::actingAs($actor)->test(TeacherGenerate::class)
        ->set('count', 3)
        ->call('generate');

    $generatedCount = DB::table('model_has_roles')
        ->where('role_id', $teacherRole->id)
        ->count();

    expect($generatedCount)->toBe(3);
    expect(UserLoginLink::count())->toBe(3);
});

test('generating teachers without permission is forbidden', function () {
    $actor = User::factory()->create();

    test()->actingAs($actor)
        ->get(route('teachers.generate'))
        ->assertForbidden();
});
