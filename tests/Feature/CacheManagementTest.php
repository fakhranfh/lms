<?php

use App\Enums\RoleName;
use App\Livewire\Admin\CacheManagement;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Livewire\Livewire;

beforeEach(function () {
    Redis::connection()->flushdb();
});

afterEach(function () {
    Redis::connection()->flushdb();
});

test('admin can view cache management page in local environment', function () {
    app()->detectEnvironment(fn () => 'local');

    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole(RoleName::Admin);

    Redis::set('sample-key', 'sample-value');

    $response = $this->actingAs($admin)->get('https://admin.lms.local/cache');

    $response->assertStatus(200)->assertSee('sample-key');

    app()->detectEnvironment(fn () => 'testing');
});

test('non admin gets 403', function () {
    $school = School::factory()->create();
    $user = User::factory()->forSchool($school)->create();

    $response = $this->actingAs($user)->get('http://admin.lms.local/cache');

    $response->assertStatus(403);
});

test('page is not accessible outside local environment', function () {
    app()->detectEnvironment(fn () => 'production');

    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole(RoleName::Admin);

    $response = $this->actingAs($admin)->get('http://admin.lms.local/cache');

    $response->assertStatus(404);

    app()->detectEnvironment(fn () => 'testing');
});

test('search filters keys', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole(RoleName::Admin);

    Redis::set('alpha-key', '1');
    Redis::set('beta-key', '2');

    Livewire::actingAs($admin)->test(CacheManagement::class)
        ->set('search', 'alpha')
        ->assertSee('alpha-key')
        ->assertDontSee('beta-key');
});

test('admin can view and edit a string key', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole(RoleName::Admin);

    Redis::set('editable-key', 'old-value');

    Livewire::actingAs($admin)->test(CacheManagement::class)
        ->call('selectKey', 'editable-key')
        ->assertSet('selectedType', 'string')
        ->assertSet('editValue', 'old-value')
        ->set('editValue', 'new-value')
        ->call('save')
        ->assertSet('successMessage', 'Key updated successfully.');

    expect(Redis::get('editable-key'))->toBe('new-value');
});

test('admin can delete a key', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole(RoleName::Admin);

    Redis::set('deletable-key', 'value');

    Livewire::actingAs($admin)->test(CacheManagement::class)
        ->call('deleteKey', 'deletable-key')
        ->assertSet('successMessage', 'Key deleted successfully.');

    expect(Redis::exists('deletable-key'))->toBe(0);
});

test('admin can flush the database', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole(RoleName::Admin);

    Redis::set('key-one', '1');
    Redis::set('key-two', '2');

    Livewire::actingAs($admin)->test(CacheManagement::class)
        ->call('flushDatabase')
        ->assertSet('successMessage', 'Cache database flushed successfully.');

    expect(Redis::keys('*'))->toBeEmpty();
});
