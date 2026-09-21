<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\School;
use App\Services\RoleService;

test('default school admin role only receives users, roles, permissions, media, sessions, syllabus, forum, assessment, attendance, gradebook, raport, students, teachers, and people permissions', function () {
    $school = School::factory()->create();

    app(RoleService::class)->createDefaultRolesForSchool($school->id);

    $schoolAdminRole = Role::where('school_id', $school->id)
        ->where('name', RoleName::SchoolAdmin->value)
        ->firstOrFail();

    $groups = $schoolAdminRole->permissions->pluck('group')->unique()->sort()->values()->all();

    expect($groups)->toEqual(['Assessment', 'Attendance', 'Forum', 'Gradebook', 'Media', 'People', 'Permissions', 'Raport', 'Roles', 'Sessions', 'Students', 'Syllabus', 'Teachers', 'Users']);
});
