<?php

use App\Enums\RoleName;
use App\Livewire\Users\UserForm;
use App\Livewire\Users\UserImport;
use App\Livewire\Users\UserIndex;
use App\Livewire\Users\UserPhotoUpload;
use App\Livewire\Users\UserRoles;
use App\Models\School;
use App\Models\User;
use App\Services\R2StorageService;
use App\Support\CurrentSchool;
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
        ->call('loadUsers')
        ->assertSee('Jane Target')
        ->assertSee('editor');
});

test('users index search filters users by name or email', function () {
    $actor = actingAsUserManager(['users.view']);
    User::factory()->create(['name' => 'Jane Target', 'email' => 'jane@example.com']);
    User::factory()->create(['name' => 'Someone Else', 'email' => 'else@example.com']);

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->call('loadUsers')
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
        ->call('loadUsers')
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
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
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
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
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
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('save')
        ->assertForbidden();
});

test('creating a user fails validation when name is already used', function () {
    $actor = actingAsUserManager(['users.create']);
    User::factory()->create(['name' => 'Taken Name']);

    Livewire::actingAs($actor)->test(UserForm::class, ['id' => null])
        ->set('name', 'Taken Name')
        ->set('email', 'unique.email@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('save')
        ->assertHasErrors('name');
});

test('creating a user fails validation when email is already used', function () {
    $actor = actingAsUserManager(['users.create']);
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::actingAs($actor)->test(UserForm::class, ['id' => null])
        ->set('name', 'Unique Name')
        ->set('email', 'taken@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('save')
        ->assertHasErrors('email');
});

test('a soft-deleted user email is still reported as taken, not silently available', function () {
    $actor = actingAsUserManager(['users.create']);
    $trashed = User::factory()->create(['email' => 'gone@example.com']);
    $trashed->delete();

    $component = Livewire::actingAs($actor)->test(UserForm::class, ['id' => null])
        ->set('name', 'Unique Name')
        ->set('email', 'gone@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('save');

    expect($component->errors()->first('email'))->not->toBeNull();
    expect(User::withTrashed()->where('email', 'gone@example.com')->count())->toBe(1);
});

test('creating a user with a soft-deleted same-school email restores the old user instead of failing', function () {
    $school = School::factory()->create();
    $actor = User::factory()->forSchool($school)->create();
    $role = App\Models\Role::create(['name' => 'user-manager-'.uniqid(), 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::firstOrCreate(['name' => 'users.create', 'guard_name' => 'web']));
    $actor->assignRole($role);

    $teacherRole = App\Models\Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $school->id]);
    $trashed = User::factory()->forSchool($school)->create(['email' => 'gone@example.com', 'name' => 'Old Name']);
    $trashed->delete();

    app(CurrentSchool::class)->setSchoolId($school->id);

    Livewire::actingAs($actor)->test(UserForm::class, ['id' => null])
        ->set('name', 'Restored Name')
        ->set('email', 'gone@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->set('roles', [$teacherRole->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    expect(User::withTrashed()->where('email', 'gone@example.com')->count())->toBe(1);

    $restored = User::where('email', 'gone@example.com')->first();
    expect($restored->id)->toBe($trashed->id);
    expect($restored->name)->toBe('Restored Name');
    expect($restored->deleted_at)->toBeNull();
    expect($restored->hasRole(RoleName::Teacher))->toBeTrue();
});

test('the email conflict message specifies another school when the email belongs elsewhere', function () {
    $actor = actingAsUserManager(['users.create']);
    User::factory()->create(['email' => 'taken@example.com']); // gets its own auto-created school, different from $actor's

    $component = Livewire::actingAs($actor)->test(UserForm::class, ['id' => null])
        ->set('name', 'Unique Name')
        ->set('email', 'taken@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('save');

    expect($component->errors()->first('email'))->toBe('This email is already in use in another school.');
});

test('a School Admin (attached only via school_admins, not school_user) still gets the another-school message', function () {
    // Regression: auth()->user()->school_id only reflects school_user
    // membership. A School Admin attached solely via the school_admins
    // pivot (schools()) would previously resolve to a null "current
    // school", silently falling back to the generic message even though
    // the email genuinely belongs to a different school.
    $adminSchool = School::factory()->create();
    $actor = User::factory()->create(); // auto-attached to its own unrelated school via memberSchools()
    $actor->memberSchools()->detach();  // remove that membership entirely
    $actor->schools()->attach($adminSchool); // attach only as School Admin

    $role = App\Models\Role::create(['name' => 'user-manager-'.uniqid(), 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::firstOrCreate(['name' => 'users.create', 'guard_name' => 'web']));
    $actor->assignRole($role);

    expect($actor->school_id)->toBeNull();

    User::factory()->create(['email' => 'elsewhere@example.com']); // different school entirely

    $this->actingAs($actor);
    app(CurrentSchool::class)->setSchoolId($adminSchool->id);

    $component = Livewire::test(UserForm::class, ['id' => null])
        ->set('name', 'Unique Name')
        ->set('email', 'elsewhere@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('save');

    expect($component->errors()->first('email'))->toBe('This email is already in use in another school.');
});

test('the email conflict message is generic when the email belongs to the same school', function () {
    $school = School::factory()->create();
    $actor = User::factory()->forSchool($school)->create();
    $role = App\Models\Role::create(['name' => 'user-manager-'.uniqid(), 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::firstOrCreate(['name' => 'users.create', 'guard_name' => 'web']));
    $actor->assignRole($role);

    User::factory()->forSchool($school)->create(['email' => 'taken@example.com']);

    $component = Livewire::actingAs($actor)->test(UserForm::class, ['id' => null])
        ->set('name', 'Unique Name')
        ->set('email', 'taken@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('save');

    expect($component->errors()->first('email'))->toBe('This email is already in use.');
});

test('the availability check reports the cross-school email message', function () {
    $actor = actingAsUserManager(['users.create']);
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($actor)
        ->getJson(route('users.check-availability', ['field' => 'email', 'value' => 'taken@example.com']))
        ->assertOk()
        ->assertJson(['available' => false, 'message' => 'This email is already in use in another school.']);
});

test('importing reports the cross-school email message for a duplicate row', function () {
    $actor = actingAsUserManager(['users.import']);
    App\Models\Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);
    User::factory()->create(['email' => 'existing@example.com']);

    $csv = "name,email\nExisting Person,existing@example.com\n";
    $spreadsheet = UploadedFile::fake()->createWithContent('users.csv', $csv);

    $component = Livewire::actingAs($actor)->test(UserImport::class, ['role' => 'teacher'])
        ->set('spreadsheet', $spreadsheet)
        ->call('import');

    expect($component->get('importErrors'))->toContain('Row 2: "existing@example.com" — This email is already in use in another school.');
});

test('creating a user fails validation when password confirmation does not match', function () {
    $actor = actingAsUserManager(['users.create']);

    Livewire::actingAs($actor)->test(UserForm::class, ['id' => null])
        ->set('name', 'Mismatch User')
        ->set('email', 'mismatch@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'different123')
        ->call('save')
        ->assertHasErrors('password');
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

test('user with users.import can bulk import teachers on the teacher import page', function () {
    $actor = actingAsUserManager(['users.import']);
    App\Models\Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);

    $csv = "name,email\n"
        ."Jane Teach,jane.teach@example.com\n"
        ."John Teach,john.teach@example.com\n";

    $spreadsheet = UploadedFile::fake()->createWithContent('users.csv', $csv);

    Livewire::actingAs($actor)->test(UserImport::class, ['role' => 'teacher'])
        ->set('spreadsheet', $spreadsheet)
        ->call('import')
        ->assertSet('createdCount', 2);

    $teacher = User::where('email', 'jane.teach@example.com')->first();
    $otherTeacher = User::where('email', 'john.teach@example.com')->first();

    expect($teacher)->not->toBeNull()
        ->and($teacher->hasRole(RoleName::Teacher))->toBeTrue()
        ->and($teacher->profile_photo_path)->toBeNull()
        ->and($otherTeacher)->not->toBeNull()
        ->and($otherTeacher->hasRole(RoleName::Teacher))->toBeTrue();
});

test('user with users.import can bulk import students on the student import page', function () {
    $actor = actingAsUserManager(['users.import']);
    App\Models\Role::firstOrCreate(['name' => RoleName::Student->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);

    $csv = "name,email\nAlex Stud,alex.stud@example.com\n";
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

    $csv = "name,email\nExisting Person,existing@example.com\n";

    $spreadsheet = UploadedFile::fake()->createWithContent('users.csv', $csv);

    $component = Livewire::actingAs($actor)->test(UserImport::class, ['role' => 'teacher'])
        ->set('spreadsheet', $spreadsheet)
        ->call('import')
        ->assertSet('createdCount', 0);

    expect($component->get('importErrors'))->not->toBeEmpty();
});

test('a spreadsheet with the wrong columns is rejected when import is clicked', function () {
    $actor = actingAsUserManager(['users.import']);

    $csv = "name,email,role\nJane Teach,jane.teach@example.com,Teacher\n";
    $spreadsheet = UploadedFile::fake()->createWithContent('users.csv', $csv);

    $component = Livewire::actingAs($actor)->test(UserImport::class, ['role' => 'teacher'])
        ->set('spreadsheet', $spreadsheet)
        ->call('import');

    expect($component->get('columnError'))->not->toBeNull();
    $component->assertSet('createdCount', null);

    expect(User::where('email', 'jane.teach@example.com')->exists())->toBeFalse();
});

test('a row missing a name or valid email is skipped and reported on import', function () {
    $actor = actingAsUserManager(['users.import']);
    App\Models\Role::firstOrCreate(['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $actor->school_id]);

    $csv = "name,email\nNo Email Person,\n,missing.name@example.com\nValid Person,valid@example.com\n";
    $spreadsheet = UploadedFile::fake()->createWithContent('users.csv', $csv);

    $component = Livewire::actingAs($actor)->test(UserImport::class, ['role' => 'teacher'])
        ->set('spreadsheet', $spreadsheet)
        ->call('import');

    $component->assertSet('createdCount', 1);
    expect($component->get('importErrors'))->toHaveCount(2);
});

test('importing users requires users.import permission', function () {
    $actor = actingAsUserManager(['users.view']);
    $csv = "name,email\nJane Teach,jane.teach@example.com\n";
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

test('user with users.delete can soft delete a user', function () {
    $actor = actingAsUserManager(['users.delete']);
    $target = User::factory()->create();

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->call('loadUsers')
        ->call('destroy', $target->id)
        ->assertSet('successMessage', 'User deleted successfully.');

    expect(User::find($target->id))->toBeNull();
    expect(User::withTrashed()->find($target->id))->not->toBeNull();
    expect(User::withTrashed()->find($target->id)->trashed())->toBeTrue();
});

test('deleting a user requires users.delete permission', function () {
    $actor = actingAsUserManager(['users.view']);
    $target = User::factory()->create();

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->call('loadUsers')
        ->call('destroy', $target->id)
        ->assertForbidden();

    expect(User::find($target->id))->not->toBeNull();
});

test('deleting the last admin user is blocked', function () {
    $actor = actingAsUserManager(['users.delete']);
    $admin = Role::firstOrCreate(['name' => RoleName::Admin->value, 'guard_name' => 'web']);

    // The create_admin_role_and_assign_admin_user migration seeds its own
    // admin user; remove it so the target below is genuinely the only admin.
    User::role(RoleName::Admin)->get()->each->delete();

    $target = User::factory()->create();
    $target->assignRole($admin);

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->call('loadUsers')
        ->call('destroy', $target->id)
        ->assertSet('errorMessage', 'At least one user must keep the admin role.');

    expect(User::find($target->id))->not->toBeNull();
});

test('deleted users no longer appear in the users index', function () {
    $actor = actingAsUserManager(['users.view', 'users.delete']);
    $target = User::factory()->create(['name' => 'Soon Deleted']);

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->call('loadUsers')
        ->call('destroy', $target->id)
        ->assertDontSee('Soon Deleted');
});

test('user with users.delete can bulk delete multiple selected users', function () {
    $actor = actingAsUserManager(['users.delete']);
    $first = User::factory()->create();
    $second = User::factory()->create();

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->call('loadUsers')
        ->call('destroySelected', [$first->id, $second->id])
        ->assertSet('successMessage', '2 users deleted successfully.');

    expect(User::find($first->id))->toBeNull();
    expect(User::find($second->id))->toBeNull();
    expect(User::withTrashed()->find($first->id)->trashed())->toBeTrue();
    expect(User::withTrashed()->find($second->id)->trashed())->toBeTrue();
});

test('bulk deleting requires users.delete permission', function () {
    $actor = actingAsUserManager(['users.view']);
    $target = User::factory()->create();

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->call('loadUsers')
        ->call('destroySelected', [$target->id])
        ->assertForbidden();

    expect(User::find($target->id))->not->toBeNull();
});

test('bulk deleting all admins is blocked but non-admins in the same batch are still deleted', function () {
    $actor = actingAsUserManager(['users.delete']);
    $admin = Role::firstOrCreate(['name' => RoleName::Admin->value, 'guard_name' => 'web']);

    // The create_admin_role_and_assign_admin_user migration seeds its own
    // admin user; remove it so the target below is genuinely the only admin.
    User::role(RoleName::Admin)->get()->each->delete();

    $adminTarget = User::factory()->create();
    $adminTarget->assignRole($admin);
    $regularTarget = User::factory()->create();

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->call('loadUsers')
        ->call('destroySelected', [$adminTarget->id, $regularTarget->id])
        ->assertSet('errorMessage', 'At least one user must keep the admin role.');

    expect(User::find($adminTarget->id))->not->toBeNull();
    expect(User::find($regularTarget->id))->toBeNull();
});

test('the users index page renders checkboxes for bulk selection', function () {
    $actor = actingAsUserManager(['users.view', 'users.delete']);
    $target = User::factory()->create();

    Livewire::actingAs($actor)->test(UserIndex::class)
        ->call('loadUsers')
        ->assertSeeHtml('data-user-checkbox')
        ->assertSeeHtml('value="'.$target->id.'"');
});

test('the availability check reports a taken name as unavailable', function () {
    $actor = actingAsUserManager(['users.create']);
    User::factory()->create(['name' => 'Taken Name']);

    $this->actingAs($actor)
        ->getJson(route('users.check-availability', ['field' => 'name', 'value' => 'Taken Name']))
        ->assertOk()
        ->assertJson(['available' => false]);
});

test('the availability check reports a free name as available', function () {
    $actor = actingAsUserManager(['users.create']);

    $this->actingAs($actor)
        ->getJson(route('users.check-availability', ['field' => 'name', 'value' => 'Nobody Yet']))
        ->assertOk()
        ->assertJson(['available' => true]);
});

test('the availability check reports a taken email as unavailable', function () {
    $actor = actingAsUserManager(['users.create']);
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($actor)
        ->getJson(route('users.check-availability', ['field' => 'email', 'value' => 'taken@example.com']))
        ->assertOk()
        ->assertJson(['available' => false]);
});

test('the availability check ignores the current user when editing', function () {
    $actor = actingAsUserManager(['users.edit']);
    $target = User::factory()->create(['name' => 'Existing Name']);

    $this->actingAs($actor)
        ->getJson(route('users.check-availability', ['field' => 'name', 'value' => 'Existing Name', 'ignore_id' => $target->id]))
        ->assertOk()
        ->assertJson(['available' => true]);
});

test('the availability check requires users.create or users.edit permission', function () {
    $actor = actingAsUserManager(['users.view']);

    $this->actingAs($actor)
        ->getJson(route('users.check-availability', ['field' => 'name', 'value' => 'Anything']))
        ->assertForbidden();
});

test('bulk photo upload search only matches by name, not email', function () {
    $actor = actingAsUserManager(['users.edit']);
    User::factory()->create(['name' => 'Findable Jane', 'email' => 'unrelated@example.com']);
    User::factory()->create(['name' => 'Someone Else', 'email' => 'jane@example.com']);

    Livewire::actingAs($actor)->test(UserPhotoUpload::class)
        ->call('loadUsers')
        ->set('search', 'Jane')
        ->assertSee('Findable Jane')
        ->assertDontSee('Someone Else');
});

test('user with users.edit can stage and save a bulk photo upload', function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('GD extension not installed');
    }

    $actor = actingAsUserManager(['users.edit']);
    $target = User::factory()->create();
    $target->assignRole(App\Models\Role::firstOrCreate(
        ['name' => RoleName::Teacher->value, 'guard_name' => 'web', 'school_id' => $actor->school_id],
        ['slug' => RoleName::Teacher->slug()]
    ));

    $this->mock(R2StorageService::class, function ($mock) {
        $mock->shouldReceive('uploadPublicFile')
            ->once()
            ->andReturn('https://r2.example.com/tmp-imports/photos/tmp-avatar.jpg');
        $mock->shouldReceive('promoteTempPhoto')
            ->once()
            ->with('https://r2.example.com/tmp-imports/photos/tmp-avatar.jpg', 'photos/teacher')
            ->andReturn('https://r2.example.com/photos/teacher/avatar.jpg');
    });

    $photo = UploadedFile::fake()->image('avatar.jpg', 100, 100);

    $component = Livewire::actingAs($actor)->test(UserPhotoUpload::class)
        ->set("uploads.{$target->id}", $photo);

    expect($component->get('stagedPhotoUrls'))->toHaveKey((string) $target->id, 'https://r2.example.com/tmp-imports/photos/tmp-avatar.jpg');

    $component->call('save')
        ->assertSet('stagedPhotoUrls', []);

    expect($target->fresh()->profile_photo_path)->toBe('https://r2.example.com/photos/teacher/avatar.jpg');
});

test('re-uploading a photo before save discards the previous staged temp file', function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('GD extension not installed');
    }

    $actor = actingAsUserManager(['users.edit']);
    $target = User::factory()->create();

    $this->mock(R2StorageService::class, function ($mock) {
        $mock->shouldReceive('uploadPublicFile')
            ->once()
            ->andReturn('https://r2.example.com/tmp-imports/photos/tmp-first.jpg');
        $mock->shouldReceive('delete')
            ->once()
            ->with('https://r2.example.com/tmp-imports/photos/tmp-first.jpg')
            ->andReturn(true);
        $mock->shouldReceive('uploadPublicFile')
            ->once()
            ->andReturn('https://r2.example.com/tmp-imports/photos/tmp-second.jpg');
    });

    $component = Livewire::actingAs($actor)->test(UserPhotoUpload::class)
        ->set("uploads.{$target->id}", UploadedFile::fake()->image('first.jpg', 100, 100))
        ->set("uploads.{$target->id}", UploadedFile::fake()->image('second.jpg', 100, 100));

    expect($component->get('stagedPhotoUrls'))->toHaveKey((string) $target->id, 'https://r2.example.com/tmp-imports/photos/tmp-second.jpg');
});

test('staging a bulk photo upload requires users.edit permission', function () {
    $actor = actingAsUserManager(['users.view']);
    $target = User::factory()->create();

    Livewire::actingAs($actor)->test(UserPhotoUpload::class)
        ->set("uploads.{$target->id}", UploadedFile::fake()->create('avatar.jpg', 100))
        ->assertForbidden();
});

test('saving a bulk photo upload requires users.edit permission', function () {
    $actor = actingAsUserManager(['users.view']);

    Livewire::actingAs($actor)->test(UserPhotoUpload::class)
        ->call('save')
        ->assertForbidden();
});
