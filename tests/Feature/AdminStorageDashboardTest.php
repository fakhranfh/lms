<?php

use App\Livewire\Admin\AdminStorageDashboard;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Module;
use App\Models\School;
use App\Models\StorageUsageLog;
use App\Models\User;
use Livewire\Livewire;

test('admin can view the storage dashboard', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    $response = $this->actingAs($admin)->get('http://admin.lms.local/storage');

    $response->assertStatus(200);
});

test('non-admin cannot view the storage dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('http://admin.lms.local/storage');

    $response->assertStatus(403);
});

test('dashboard loads without errors when there is no data', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    Livewire::actingAs($admin)
        ->test(AdminStorageDashboard::class)
        ->assertOk()
        ->assertSee('Global Usage');
});

test('dashboard links each school row to the materials page filtered by that school', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    $school = School::factory()->create();
    $course = Course::factory()->for($school)->create();
    $module = Module::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($module)->create();
    LessonMaterial::factory()->withLesson($lesson)->create();

    Livewire::actingAs($admin)
        ->test(AdminStorageDashboard::class)
        ->assertSee(route('admin.storage.materials', ['school' => $school->id]));
});

test('shows a placeholder message when there is not enough trend data yet', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    Livewire::actingAs($admin)
        ->test(AdminStorageDashboard::class)
        ->assertSee('Not enough data yet');
});

test('renders the trend chart once at least two usage snapshots exist', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    $first = StorageUsageLog::create([
        'school_id' => null,
        'total_used_bytes' => 1000,
        'quota_bytes' => 10000,
        'usage_percent' => 10,
        'last_alert_threshold' => 0,
    ]);
    $first->forceFill(['created_at' => now()->subDays(2)])->save();

    $second = StorageUsageLog::create([
        'school_id' => null,
        'total_used_bytes' => 2000,
        'quota_bytes' => 10000,
        'usage_percent' => 20,
        'last_alert_threshold' => 0,
    ]);
    $second->forceFill(['created_at' => now()->subDay()])->save();

    Livewire::actingAs($admin)
        ->test(AdminStorageDashboard::class)
        ->assertDontSee('Not enough data yet')
        ->assertSee('Latest: 20.0%');
});

test('sorting toggles direction on repeated clicks', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    Livewire::actingAs($admin)
        ->test(AdminStorageDashboard::class)
        ->assertSet('sortBy', 'used_bytes')
        ->assertSet('sortDirection', 'desc')
        ->call('sortByColumn', 'used_bytes')
        ->assertSet('sortDirection', 'asc')
        ->call('sortByColumn', 'material_count')
        ->assertSet('sortBy', 'material_count')
        ->assertSet('sortDirection', 'desc');
});
