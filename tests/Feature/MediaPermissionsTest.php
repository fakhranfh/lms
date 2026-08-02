<?php

use App\Models\School;
use App\Services\RoleService;

test('school admin and teacher roles get media permissions by default for new schools', function () {
    $school = School::factory()->create();

    app(RoleService::class)->createDefaultRolesForSchool($school->id);

    $schoolAdmin = $school->roles()->where('name', 'School Admin')->first();
    $teacher = $school->roles()->where('name', 'Teacher')->first();

    expect($schoolAdmin->hasPermissionTo('media.view'))->toBeTrue()
        ->and($schoolAdmin->hasPermissionTo('media.create'))->toBeTrue()
        ->and($schoolAdmin->hasPermissionTo('media.delete'))->toBeTrue()
        ->and($teacher->hasPermissionTo('media.view'))->toBeTrue()
        ->and($teacher->hasPermissionTo('media.create'))->toBeTrue()
        ->and($teacher->hasPermissionTo('media.delete'))->toBeTrue();
});
