<?php

use App\Livewire\MediaLibrary\MediaLibraryIndex;
use App\Models\MediaLibraryItem;
use App\Models\School;
use App\Models\User;
use App\Services\MediaLibraryService;
use App\Services\R2StorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function actingAsUserWithMediaPermissions(array $permissions = ['media.view']): User
{
    $school = School::factory()->create();
    $user = User::factory()->forSchool($school)->create();
    $user->givePermissionTo($permissions);
    test()->actingAs($user);

    return $user;
}

test('user without media.view permission is forbidden', function () {
    $school = School::factory()->create();
    $user = User::factory()->forSchool($school)->create();
    $this->actingAs($user);

    Livewire::test(MediaLibraryIndex::class)->assertStatus(403);
});

test('school admin and teacher can view the media library', function (string $permission) {
    actingAsUserWithMediaPermissions(['media.view']);

    Livewire::test(MediaLibraryIndex::class)
        ->assertStatus(200)
        ->assertSee('Media Library');
})->with(['media.view']);

test('media list only shows items for the current school', function () {
    $user = actingAsUserWithMediaPermissions(['media.view']);

    $ownItem = MediaLibraryItem::factory()->withSchool($user->school())->create(['title' => 'Own School File']);
    $otherSchool = School::factory()->create();
    MediaLibraryItem::factory()->withSchool($otherSchool)->create(['title' => 'Other School File']);

    Livewire::test(MediaLibraryIndex::class)
        ->assertSee('Own School File')
        ->assertDontSee('Other School File');
});

test('search filters media by title', function () {
    $user = actingAsUserWithMediaPermissions(['media.view']);

    MediaLibraryItem::factory()->withSchool($user->school())->create(['title' => 'Findable Document']);
    MediaLibraryItem::factory()->withSchool($user->school())->create(['title' => 'Something Else']);

    Livewire::test(MediaLibraryIndex::class)
        ->set('search', 'Findable')
        ->assertSee('Findable Document')
        ->assertDontSee('Something Else');
});

test('per page controls how many items are paginated', function () {
    $user = actingAsUserWithMediaPermissions(['media.view']);

    MediaLibraryItem::factory()->withSchool($user->school())->count(15)->create();

    Livewire::test(MediaLibraryIndex::class)
        ->set('perPage', 12)
        ->assertViewHas('items', fn ($items) => $items->count() === 12 && $items->hasPages())
        ->set('perPage', 24)
        ->assertViewHas('items', fn ($items) => $items->count() === 15 && ! $items->hasPages());
});

test('an unsupported per page value falls back to the default', function () {
    $user = actingAsUserWithMediaPermissions(['media.view']);

    MediaLibraryItem::factory()->withSchool($user->school())->count(15)->create();

    Livewire::test(MediaLibraryIndex::class)
        ->set('perPage', 999)
        ->assertViewHas('items', fn ($items) => $items->count() === 12);
});

test('finalize upload returns error when validation fails', function () {
    $user = actingAsUserWithMediaPermissions(['media.view', 'media.create']);

    $this->mock(MediaLibraryService::class, function ($mock) use ($user) {
        $mock->shouldReceive('finalizeUpload')
            ->andThrow(new Exception('File content does not match PDF format.'));
        $mock->shouldReceive('list')
            ->andReturn(MediaLibraryItem::query()->where('school_id', $user->school()->id));
    });

    $component = Livewire::test(MediaLibraryIndex::class);

    $result = $component->instance()->finalizeUpload([
        'type' => 'PDF',
        'temp_key' => 'temp/media/fake.pdf',
    ], app(MediaLibraryService::class));

    expect($result)->toHaveKey('error');
    expect($result['error'])->toBe('File content does not match PDF format.');
    $this->assertDatabaseMissing('media_library_items', ['school_id' => $user->school()->id]);
});

test('user without media.create cannot generate an upload url', function () {
    actingAsUserWithMediaPermissions(['media.view']);

    $component = Livewire::test(MediaLibraryIndex::class);

    expect(fn () => $component->instance()->generateUploadUrl('notes.pdf', 'PDF', app(MediaLibraryService::class)))
        ->toThrow(HttpException::class);
});

test('media can be deleted via the global delete-confirmed event', function () {
    $user = actingAsUserWithMediaPermissions(['media.view', 'media.delete']);

    $item = MediaLibraryItem::factory()->withSchool($user->school())->create();

    $this->mock(R2StorageService::class, function ($mock) {
        $mock->shouldReceive('delete')->once()->andReturn(true);
        $mock->shouldReceive('checkSchoolQuota')
            ->andReturn(['used' => 0, 'limit' => 1073741824, 'remaining' => 1073741824, 'percentage' => 0.0, 'limit_gb' => 1]);
        $mock->shouldReceive('getPublicUrl')->andReturn('https://example.com/file');
    });

    Livewire::test(MediaLibraryIndex::class)
        ->call('deleteMedia', $item->id, app(MediaLibraryService::class))
        ->assertSet('successMessage', 'Media deleted successfully.');

    $this->assertDatabaseMissing('media_library_items', ['id' => $item->id]);
});

test('deleting media from another school is ignored', function () {
    $user = actingAsUserWithMediaPermissions(['media.view', 'media.delete']);

    $otherSchool = School::factory()->create();
    $otherItem = MediaLibraryItem::factory()->withSchool($otherSchool)->create();

    Livewire::test(MediaLibraryIndex::class)
        ->call('deleteMedia', $otherItem->id, app(MediaLibraryService::class));

    $this->assertDatabaseHas('media_library_items', ['id' => $otherItem->id]);
});

test('title can be updated', function () {
    $user = actingAsUserWithMediaPermissions(['media.view', 'media.create']);

    $item = MediaLibraryItem::factory()->withSchool($user->school())->create(['title' => 'original-filename']);

    Livewire::test(MediaLibraryIndex::class)
        ->call('updateTitle', $item->id, 'A Much Better Title', app(MediaLibraryService::class));

    $this->assertDatabaseHas('media_library_items', [
        'id' => $item->id,
        'title' => 'A Much Better Title',
    ]);
});

test('bulk delete removes all given media ids belonging to the school', function () {
    $user = actingAsUserWithMediaPermissions(['media.view', 'media.delete']);

    $items = MediaLibraryItem::factory()->withSchool($user->school())->count(3)->create();

    $this->mock(R2StorageService::class, function ($mock) {
        $mock->shouldReceive('delete')->times(3)->andReturn(true);
        $mock->shouldReceive('checkSchoolQuota')
            ->andReturn(['used' => 0, 'limit' => 1073741824, 'remaining' => 1073741824, 'percentage' => 0.0, 'limit_gb' => 1]);
        $mock->shouldReceive('getPublicUrl')->andReturn('https://example.com/file');
    });

    Livewire::test(MediaLibraryIndex::class)
        ->call('bulkDelete', $items->pluck('id')->all(), app(MediaLibraryService::class))
        ->assertSet('successMessage', '3 media items deleted successfully.');

    foreach ($items as $item) {
        $this->assertDatabaseMissing('media_library_items', ['id' => $item->id]);
    }
});

test('bulk delete only affects the current school even if other ids are passed', function () {
    $user = actingAsUserWithMediaPermissions(['media.view', 'media.delete']);

    $ownItem = MediaLibraryItem::factory()->withSchool($user->school())->create();
    $otherSchool = School::factory()->create();
    $otherItem = MediaLibraryItem::factory()->withSchool($otherSchool)->create();

    $this->mock(R2StorageService::class, function ($mock) {
        $mock->shouldReceive('delete')->once()->andReturn(true);
        $mock->shouldReceive('checkSchoolQuota')
            ->andReturn(['used' => 0, 'limit' => 1073741824, 'remaining' => 1073741824, 'percentage' => 0.0, 'limit_gb' => 1]);
        $mock->shouldReceive('getPublicUrl')->andReturn('https://example.com/file');
    });

    Livewire::test(MediaLibraryIndex::class)
        ->call('bulkDelete', [$ownItem->id, $otherItem->id], app(MediaLibraryService::class));

    $this->assertDatabaseMissing('media_library_items', ['id' => $ownItem->id]);
    $this->assertDatabaseHas('media_library_items', ['id' => $otherItem->id]);
});

test('bulk delete with an empty id list is a no-op', function () {
    actingAsUserWithMediaPermissions(['media.view', 'media.delete']);

    Livewire::test(MediaLibraryIndex::class)
        ->call('bulkDelete', [], app(MediaLibraryService::class))
        ->assertSet('successMessage', null);
});

test('user without media.delete cannot bulk delete', function () {
    $user = actingAsUserWithMediaPermissions(['media.view']);

    $item = MediaLibraryItem::factory()->withSchool($user->school())->create();

    $component = Livewire::test(MediaLibraryIndex::class);

    expect(fn () => $component->instance()->bulkDelete([$item->id], app(MediaLibraryService::class)))
        ->toThrow(HttpException::class);
});
