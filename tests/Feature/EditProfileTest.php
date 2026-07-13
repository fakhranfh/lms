<?php

use App\Livewire\EditProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('authenticated user can view edit profile page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/edit-profile');

    $response->assertStatus(200)
        ->assertSee($user->name)
        ->assertSee($user->email);
});

test('unauthenticated user cannot view edit profile page', function () {
    $response = $this->get('/edit-profile');

    $response->assertRedirect('/login');
});

test('user can update profile name without changing email', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(EditProfile::class)
        ->set('name', 'Updated Name')
        ->set('email', $user->email)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('successMessage', 'Profile updated successfully.');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
    ]);
});

test('user changing email triggers verification and stores pending email', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(EditProfile::class)
        ->set('name', $user->name)
        ->set('email', 'newemail@example.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('pendingEmailSent', 'newemail@example.com');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'email' => $user->email,
        'pending_email' => 'newemail@example.com',
    ]);
});

test('user can upload profile photo', function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('GD extension not installed');
    }

    Storage::fake('public');
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('profile.jpg', 100, 100);

    Livewire::actingAs($user)->test(EditProfile::class)
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->set('photo', $file)
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('public')->assertExists('profile-photos/'.$file->hashName());
});

test('user can remove profile photo', function () {
    Storage::fake('public');
    $user = User::factory()->create(['profile_photo_path' => '/storage/profile-photos/test.jpg']);

    Livewire::actingAs($user)->test(EditProfile::class)
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->call('removePhotoNow')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'profile_photo_path' => null,
    ]);
});

test('email must be unique', function () {
    $user1 = User::factory()->create(['email' => 'test@example.com']);
    $user2 = User::factory()->create();

    Livewire::actingAs($user2)->test(EditProfile::class)
        ->set('name', $user2->name)
        ->set('email', 'test@example.com')
        ->call('save')
        ->assertHasErrors('email');
});

test('user can update timezone', function () {
    $user = User::factory()->create(['timezone' => 'UTC']);

    Livewire::actingAs($user)->test(EditProfile::class)
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->set('timezone', 'Asia/Jakarta')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('successMessage', 'Profile updated successfully.');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'timezone' => 'Asia/Jakarta',
    ]);
});

test('invalid timezone is rejected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(EditProfile::class)
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->set('timezone', 'Not/A_Timezone')
        ->call('save')
        ->assertHasErrors('timezone');
});

test('name is required', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(EditProfile::class)
        ->set('name', '')
        ->set('email', $user->email)
        ->call('save')
        ->assertHasErrors('name');
});

test('email is required', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(EditProfile::class)
        ->set('name', $user->name)
        ->set('email', '')
        ->call('save')
        ->assertHasErrors('email');
});

test('profile photo must be valid image', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100);

    Livewire::actingAs($user)->test(EditProfile::class)
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->set('photo', $file)
        ->call('save')
        ->assertHasErrors('photo');
});
