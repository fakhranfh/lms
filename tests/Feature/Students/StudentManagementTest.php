<?php

use App\Enums\RoleName;
use App\Livewire\Students\ForcePasswordChange;
use App\Livewire\Students\StudentForm;
use App\Livewire\Students\StudentGenerate;
use App\Livewire\Students\StudentIndex;
use App\Models\Role;
use App\Models\User;
use App\Models\UserLoginLink;
use App\Services\UserLoginLinkService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Creates a teacher-like actor with the given student-management
 * permissions, scoped to their own school.
 *
 * @param  array<int, string>  $permissionNames
 */
function actingAsStudentManager(array $permissionNames): User
{
    $user = User::factory()->create();

    $role = Role::create(['name' => 'student-manager-'.uniqid(), 'guard_name' => 'web', 'school_id' => $user->school_id]);

    foreach ($permissionNames as $permissionName) {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']));
    }

    $user->assignRole($role);

    return $user;
}

function makeStudent(User $actor, array $attributes = []): User
{
    $student = User::factory()->create(array_merge(['school_id' => $actor->school_id], $attributes));

    $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);
    $student->assignRole($studentRole);

    return $student;
}

test('students index lists only students, not other roles', function () {
    $actor = actingAsStudentManager(['students.view']);
    $student = makeStudent($actor, ['name' => 'Student One']);

    $teacher = User::factory()->create(['name' => 'Teacher One', 'school_id' => $actor->school_id]);
    $teacherRole = Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);
    $teacher->assignRole($teacherRole);

    Livewire::actingAs($actor)->test(StudentIndex::class)
        ->call('loadUsers')
        ->assertSee('Student One')
        ->assertDontSee('Teacher One');
});

test('students index paginates results', function () {
    $actor = actingAsStudentManager(['students.view']);

    User::factory()->count(20)->create(['school_id' => $actor->school_id])
        ->each(function (User $student) use ($actor) {
            $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);
            $student->assignRole($studentRole);
        });

    $component = Livewire::actingAs($actor)->test(StudentIndex::class)
        ->call('loadUsers')
        ->set('perPage', 10);

    expect($component->get('students')->count())->toBe(10);
});

test('students index supports navigating pages via gotoPage', function () {
    $actor = actingAsStudentManager(['students.view']);

    User::factory()->count(20)->create(['school_id' => $actor->school_id])
        ->each(function (User $student) use ($actor) {
            $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);
            $student->assignRole($studentRole);
        });

    $component = Livewire::actingAs($actor)->test(StudentIndex::class)
        ->call('loadUsers')
        ->set('perPage', 10)
        ->call('gotoPage', 2);

    expect($component->get('students')->currentPage())->toBe(2);
});

test('students index search filters by name or email', function () {
    $actor = actingAsStudentManager(['students.view']);
    makeStudent($actor, ['name' => 'Findable Student', 'email' => 'findable@example.com']);
    makeStudent($actor, ['name' => 'Other Student', 'email' => 'other@example.com']);

    Livewire::actingAs($actor)->test(StudentIndex::class)
        ->call('loadUsers')
        ->set('search', 'Findable')
        ->assertSee('Findable Student')
        ->assertDontSee('Other Student');
});

test('students index requires students.view permission', function () {
    $actor = User::factory()->create();

    test()->actingAs($actor)
        ->get(route('students.index'))
        ->assertForbidden();
});

test('creating a student assigns the student role and generates a login link with must_change_password', function () {
    $actor = actingAsStudentManager(['students.create']);
    Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);

    Livewire::actingAs($actor)->test(StudentForm::class)
        ->set('name', 'New Student')
        ->set('email', 'new-student@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save');

    $student = User::where('email', 'new-student@example.com')->first();

    expect($student)->not->toBeNull();
    expect($student->hasRole(RoleName::Student))->toBeTrue();
    expect($student->must_change_password)->toBeTrue();
    expect(UserLoginLink::where('user_id', $student->id)->exists())->toBeTrue();
});

test('creating a student without permission is forbidden', function () {
    $actor = User::factory()->create();

    Livewire::actingAs($actor)->test(StudentForm::class)
        ->set('name', 'New Student')
        ->set('email', 'blocked@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertForbidden();
});

test('editing a student updates their profile', function () {
    $actor = actingAsStudentManager(['students.edit']);
    $student = makeStudent($actor, ['name' => 'Old Name', 'email' => 'old@example.com']);

    Livewire::actingAs($actor)->test(StudentForm::class, ['id' => $student->id])
        ->set('name', 'New Name')
        ->set('email', 'old@example.com')
        ->call('save');

    expect($student->refresh()->name)->toBe('New Name');
});

test('editing a student without permission is forbidden', function () {
    $actor = actingAsStudentManager(['students.view']);
    $student = makeStudent($actor);

    Livewire::actingAs($actor)->test(StudentForm::class, ['id' => $student->id])
        ->set('name', 'Blocked Update')
        ->set('email', $student->email)
        ->call('save')
        ->assertForbidden();
});

test('deleting a student requires students.delete permission', function () {
    $actor = actingAsStudentManager(['students.view']);
    $student = makeStudent($actor);

    Livewire::actingAs($actor)->test(StudentIndex::class)
        ->call('destroy', $student->id)
        ->assertForbidden();
});

test('a manager with permission can delete a student', function () {
    $actor = actingAsStudentManager(['students.view', 'students.delete']);
    $student = makeStudent($actor);

    Livewire::actingAs($actor)->test(StudentIndex::class)
        ->call('destroy', $student->id);

    expect(User::withTrashed()->find($student->id)->trashed())->toBeTrue();
});

test('bulk delete requires students.delete permission', function () {
    $actor = actingAsStudentManager(['students.view']);
    $student = makeStudent($actor);

    Livewire::actingAs($actor)->test(StudentIndex::class)
        ->call('destroySelected', [$student->id])
        ->assertForbidden();
});

test('bulk delete removes selected students', function () {
    $actor = actingAsStudentManager(['students.view', 'students.delete']);
    $studentOne = makeStudent($actor);
    $studentTwo = makeStudent($actor);

    Livewire::actingAs($actor)->test(StudentIndex::class)
        ->call('destroySelected', [$studentOne->id, $studentTwo->id]);

    expect(User::withTrashed()->find($studentOne->id)->trashed())->toBeTrue();
    expect(User::withTrashed()->find($studentTwo->id)->trashed())->toBeTrue();
});

test('matching ids returns every student id matching the current filters', function () {
    $actor = actingAsStudentManager(['students.view']);
    $studentOne = makeStudent($actor, ['name' => 'Match One']);
    $studentTwo = makeStudent($actor, ['name' => 'Match Two']);
    makeStudent($actor, ['name' => 'Other Student']);

    Livewire::actingAs($actor)->test(StudentIndex::class)
        ->set('search', 'Match')
        ->call('matchingIds')
        ->assertReturned(fn (array $ids) => collect($ids)->sort()->values()->all() === collect([$studentOne->id, $studentTwo->id])->sort()->values()->all());
});

test('destroy all matching removes every student matching the current filters, not just the current page', function () {
    $actor = actingAsStudentManager(['students.view', 'students.delete']);
    makeStudent($actor, ['name' => 'Match One']);
    makeStudent($actor, ['name' => 'Match Two']);
    $unrelated = makeStudent($actor, ['name' => 'Other Student']);

    Livewire::actingAs($actor)->test(StudentIndex::class)
        ->set('search', 'Match')
        ->call('destroyAllMatching');

    expect(User::withTrashed()->where('name', 'like', 'Match%')->get()->every(fn (User $user) => $user->trashed()))->toBeTrue();
    expect($unrelated->fresh()->trashed())->toBeFalse();
});

test('destroy all matching requires students.delete permission', function () {
    $actor = actingAsStudentManager(['students.view']);
    $student = makeStudent($actor);

    Livewire::actingAs($actor)->test(StudentIndex::class)
        ->call('destroyAllMatching')
        ->assertForbidden();

    expect($student->fresh()->trashed())->toBeFalse();
});

test('generating students creates the requested number with login links', function () {
    $actor = actingAsStudentManager(['students.create']);
    $studentRole = Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);

    Livewire::actingAs($actor)->test(StudentGenerate::class)
        ->set('count', 3)
        ->call('generate');

    $generatedCount = DB::table('model_has_roles')
        ->where('role_id', $studentRole->id)
        ->count();

    expect($generatedCount)->toBe(3);
    expect(UserLoginLink::count())->toBe(3);
});

test('generating students without permission is forbidden', function () {
    $actor = User::factory()->create();

    test()->actingAs($actor)
        ->get(route('students.generate'))
        ->assertForbidden();
});

test('importing students requires students.import permission', function () {
    $actor = User::factory()->create();

    test()->actingAs($actor)
        ->get(route('students.import'))
        ->assertForbidden();
});

test('login link login redirects to force password change when flagged', function () {
    $user = User::factory()->create(['must_change_password' => true]);
    $link = app(UserLoginLinkService::class)->createLink($user);

    $response = test()->get(route('user-login-link.login', $link->token));

    $response->assertRedirect(route('password.force-change'));
    test()->assertAuthenticatedAs($user);
});

test('login link login redirects to dashboard when password already changed', function () {
    $user = User::factory()->create(['must_change_password' => false]);
    $link = app(UserLoginLinkService::class)->createLink($user);

    $response = test()->get(route('user-login-link.login', $link->token));

    $response->assertRedirect(route('dashboard'));
});

test('force password change updates password and clears the flag', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    Livewire::actingAs($user)->test(ForcePasswordChange::class)
        ->set('password', 'brand-new-pass')
        ->set('password_confirmation', 'brand-new-pass')
        ->call('save')
        ->assertRedirect(route('dashboard'));

    expect($user->refresh()->must_change_password)->toBeFalse();
});

test('force password change requires matching confirmation', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    Livewire::actingAs($user)->test(ForcePasswordChange::class)
        ->set('password', 'brand-new-pass')
        ->set('password_confirmation', 'different')
        ->call('save')
        ->assertHasErrors(['password']);
});
