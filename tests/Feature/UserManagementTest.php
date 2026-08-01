<?php

use App\Enums\RoleName;
use App\Livewire\Users\UserForm;
use App\Livewire\Users\UserImport;
use App\Livewire\Users\UserIndex;
use App\Livewire\Users\UserRoles;
use App\Models\School;
use App\Models\User;
use App\Services\R2StorageService;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function actingAsUserManager(array $permissionNames): User
{
    $user = User::factory()->create();

    $role = Role::create(['name' => 'user-manager-'.uniqid(), 'guard_name' => 'web']);

    foreach ($permissionNames as $permissionName) {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']));
    }

    $user->assignRole($role);

    return $user;
}

test('users index lists users with their roles', function () {
    $actor = actingAsUserManager(['users.view']);
    $target = User::factory()->create(['name' => 'Jane Target']);
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $target->assignRole($role);

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->assertSee('Jane Target')
        ->assertSee('editor');
});

test('users index search filters users by name or email', function () {
    $actor = actingAsUserManager(['users.view']);
    User::factory()->create(['name' => 'Jane Target', 'email' => 'jane@example.com']);
    User::factory()->create(['name' => 'Someone Else', 'email' => 'else@example.com']);

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->set('search', 'Jane')
        ->assertSee('Jane Target')
        ->assertDontSee('Someone Else');
});

test('users index filters users by role', function () {
    $actor = actingAsUserManager(['users.view']);
    $editorRole = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $viewerRole = Role::create(['name' => 'viewer', 'guard_name' => 'web']);

    $editor = User::factory()->create(['name' => 'Editor User']);
    $editor->assignRole($editorRole);

    $viewer = User::factory()->create(['name' => 'Viewer User']);
    $viewer->assignRole($viewerRole);

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->set('filterRole', $editorRole->id)
        ->assertSee('Editor User')
        ->assertDontSee('Viewer User');
});

test('users index role filter only lists roles scoped to the current school', function () {
    $actor = actingAsUserManager(['users.view']);
    App\Models\Role::create(['name' => 'own-school-role', 'guard_name' => 'web', 'school_id' => $actor->school_id]);

    $otherSchool = School::factory()->create();
    App\Models\Role::create(['name' => 'other-school-role', 'guard_name' => 'web', 'school_id' => $otherSchool->id]);

    $component = Livewire::actingAs($actor)->test(UserIndex::class);

    expect($component->instance()->availableRoles()->pluck('name')->all())
        ->toContain('own-school-role')
        ->not->toContain('other-school-role');
});

test('user with users.assign-roles can update a target user roles', function () {
    $actor = actingAsUserManager(['users.assign-roles']);
    $target = User::factory()->create();
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    Livewire::actingAs($actor)->test(UserRoles::class, ['id' => $target->id])
        ->set('roles', [$role->id])
        ->call('updateRoles')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    expect($target->fresh()->roles->pluck('id')->all())->toBe([$role->id]);
});

test('updating roles requires users.assign-roles permission even via direct component call', function () {
    $actor = actingAsUserManager(['users.view']);
    $target = User::factory()->create();
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    Livewire::actingAs($actor)->test(UserRoles::class, ['id' => $target->id])
        ->set('roles', [$role->id])
        ->call('updateRoles')
        ->assertForbidden();
});

test('removing the last admin role from the only admin user is blocked', function () {
    $actor = actingAsUserManager(['users.assign-roles']);
    $admin = Role::firstOrCreate(['name' => RoleName::Admin->value, 'guard_name' => 'web']);

    // The create_admin_role_and_assign_admin_user migration seeds its own
    // admin user; remove it so the target below is genuinely the only admin.
    User::role(RoleName::Admin)->get()->each->delete();

    $target = User::factory()->create();
    $target->assignRole($admin);

    Livewire::actingAs($actor)->test(UserRoles::class, ['id' => $target->id])
        ->set('roles', [])
        ->call('updateRoles')
        ->assertHasErrors('roles');

    expect($target->fresh()->hasRole(RoleName::Admin))->toBeTrue();
});

test('the create user page loads via its real route without an id parameter', function () {
    $actor = actingAsUserManager(['users.create']);

    $this->actingAs($actor)->get(route('users.create'))
        ->assertOk();
});

test('user with users.create can create a new user', function () {
    $actor = actingAsUserManager(['users.create']);
    $teacherRole = App\Models\Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);

    Livewire::actingAs($actor)->test(UserForm::class, ['id' => null])
        ->set('name', 'New Teacher')
        ->set('email', 'new.teacher@example.com')
        ->set('password', 'password123')
        ->set('roles', [$teacherRole->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    $created = User::where('email', 'new.teacher@example.com')->first();
    expect($created)->not->toBeNull();
    expect($created->hasRole(RoleName::Teacher))->toBeTrue();
    expect($created->email_verified_at)->not->toBeNull();
});

test('creating a user with a photo uploads it via R2StorageService', function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('GD extension not installed');
    }

    $this->mock(R2StorageService::class, function ($mock) {
        $mock->shouldReceive('uploadPublicFile')
            ->once()
            ->andReturn('https://r2.example.com/profile-photos/new-teacher.jpg');
    });

    $actor = actingAsUserManager(['users.create']);
    $file = UploadedFile::fake()->image('teacher.jpg', 100, 100);

    Livewire::actingAs($actor)->test(UserForm::class, ['id' => null])
        ->set('name', 'Photo Teacher')
        ->set('email', 'photo.teacher@example.com')
        ->set('password', 'password123')
        ->set('photo', $file)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'email' => 'photo.teacher@example.com',
        'profile_photo_path' => 'https://r2.example.com/profile-photos/new-teacher.jpg',
    ]);
});

test('creating a user requires users.create permission', function () {
    $actor = actingAsUserManager(['users.view']);

    Livewire::actingAs($actor)->test(UserForm::class, ['id' => null])
        ->set('name', 'New Teacher')
        ->set('email', 'blocked@example.com')
        ->set('password', 'password123')
        ->call('save')
        ->assertForbidden();
});

test('user with users.edit can update an existing user', function () {
    $actor = actingAsUserManager(['users.edit']);
    $target = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

    Livewire::actingAs($actor)->test(UserForm::class, ['id' => $target->id])
        ->set('name', 'Updated Name')
        ->set('email', 'updated@example.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

test('editing a user without a new password keeps the current password', function () {
    $actor = actingAsUserManager(['users.edit']);
    $target = User::factory()->create(['password' => 'original-password']);
    $originalHash = $target->password;

    Livewire::actingAs($actor)->test(UserForm::class, ['id' => $target->id])
        ->set('name', $target->name)
        ->set('email', $target->email)
        ->set('password', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($target->fresh()->password)->toBe($originalHash);
});

test('editing a user requires users.edit permission', function () {
    $actor = actingAsUserManager(['users.view']);
    $target = User::factory()->create();

    Livewire::actingAs($actor)->test(UserForm::class, ['id' => $target->id])
        ->set('name', 'Blocked Update')
        ->set('email', $target->email)
        ->call('save')
        ->assertForbidden();
});

test('user with users.import can bulk import teachers with photos on the teacher import page', function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('GD extension not installed');
    }

    $this->mock(R2StorageService::class, function ($mock) {
        $mock->shouldReceive('uploadPublicFile')
            ->once()
            ->andReturn('https://r2.example.com/profile-photos/jane.jpg');
    });

    $actor = actingAsUserManager(['users.import']);
    App\Models\Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);

    $csv = "name,email,photo_filename\n"
        ."Jane Teach,jane.teach@example.com,jane.jpg\n"
        ."John Teach,john.teach@example.com,\n";

    $spreadsheet = UploadedFile::fake()->createWithContent('users.csv', $csv);
    $photo = UploadedFile::fake()->image('jane.jpg', 100, 100);

    Livewire::actingAs($actor)->test(UserImport::class, ['role' => 'teacher'])
        ->set('spreadsheet', $spreadsheet)
        ->set('photos', [$photo])
        ->call('import')
        ->assertSet('createdCount', 2);

    $teacher = User::where('email', 'jane.teach@example.com')->first();
    $otherTeacher = User::where('email', 'john.teach@example.com')->first();

    expect($teacher)->not->toBeNull()
        ->and($teacher->hasRole(RoleName::Teacher))->toBeTrue()
        ->and($teacher->profile_photo_path)->toBe('https://r2.example.com/profile-photos/jane.jpg')
        ->and($otherTeacher)->not->toBeNull()
        ->and($otherTeacher->hasRole(RoleName::Teacher))->toBeTrue()
        ->and($otherTeacher->profile_photo_path)->toBeNull();
});

test('user with users.import can bulk import students on the student import page', function () {
    $actor = actingAsUserManager(['users.import']);
    App\Models\Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);

    $csv = "name,email,photo_filename\nAlex Stud,alex.stud@example.com,\n";
    $spreadsheet = UploadedFile::fake()->createWithContent('users.csv', $csv);

    Livewire::actingAs($actor)->test(UserImport::class, ['role' => 'student'])
        ->set('spreadsheet', $spreadsheet)
        ->call('import')
        ->assertSet('createdCount', 1);

    $student = User::where('email', 'alex.stud@example.com')->first();

    expect($student)->not->toBeNull()
        ->and($student->hasRole(RoleName::Student))->toBeTrue();
});

test('importing skips rows with an email that already exists and reports it', function () {
    $actor = actingAsUserManager(['users.import']);
    App\Models\Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);
    User::factory()->create(['email' => 'existing@example.com']);

    $csv = "name,email,photo_filename\nExisting Person,existing@example.com,\n";

    $spreadsheet = UploadedFile::fake()->createWithContent('users.csv', $csv);

    $component = Livewire::actingAs($actor)->test(UserImport::class, ['role' => 'teacher'])
        ->set('spreadsheet', $spreadsheet)
        ->call('import')
        ->assertSet('createdCount', 0);

    expect($component->get('importErrors'))->not->toBeEmpty();
});

test('importing rejects a spreadsheet with the wrong columns', function () {
    $actor = actingAsUserManager(['users.import']);

    $csv = "name,email,role,photo_filename\nJane Teach,jane.teach@example.com,Teacher,\n";
    $spreadsheet = UploadedFile::fake()->createWithContent('users.csv', $csv);

    $component = Livewire::actingAs($actor)->test(UserImport::class, ['role' => 'teacher'])
        ->set('spreadsheet', $spreadsheet)
        ->call('import')
        ->assertSet('createdCount', null);

    expect($component->get('importErrors'))->not->toBeEmpty();
    expect(User::where('email', 'jane.teach@example.com')->exists())->toBeFalse();
});

test('importing users requires users.import permission', function () {
    $actor = actingAsUserManager(['users.view']);
    $csv = "name,email,photo_filename\nJane Teach,jane.teach@example.com,\n";
    $spreadsheet = UploadedFile::fake()->createWithContent('users.csv', $csv);

    Livewire::actingAs($actor)->test(UserImport::class, ['role' => 'teacher'])
        ->set('spreadsheet', $spreadsheet)
        ->call('import')
        ->assertForbidden();
});

test('the import page rejects an unknown role', function () {
    $actor = actingAsUserManager(['users.import']);

    Livewire::actingAs($actor)->test(UserImport::class, ['role' => 'admin'])
        ->assertStatus(404);
});
