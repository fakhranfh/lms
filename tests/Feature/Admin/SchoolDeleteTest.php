<?php

use App\Livewire\Schools\SchoolIndex;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;

test('admin can delete a school', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    $school = School::factory()->create(['domain' => 'to-delete.lms.local']);

    Livewire::actingAs($admin)
        ->test(SchoolIndex::class)
        ->call('destroy', $school->id)
        ->assertSet('successMessage', 'School deleted successfully.');

    expect(School::find($school->id))->toBeNull();
});

test('root domain school cannot be deleted', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole('Admin');

    $school = School::where('domain', config('app.domain'))->firstOrFail();

    Livewire::actingAs($admin)
        ->test(SchoolIndex::class)
        ->call('destroy', $school->id)
        ->assertSet('errorMessage', 'The root domain school cannot be deleted.');

    expect(School::find($school->id))->not->toBeNull();
});
