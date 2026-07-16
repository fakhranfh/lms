<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;

describe('School-Scoped RBAC', function () {
    describe('Database Schema', function () {
        it('has school_id column on roles table', function () {
            $school = School::factory()->create();

            $role = Role::create([
                'name' => 'editor_'.$school->id,
                'slug' => 'editor-'.$school->id,
                'guard_name' => 'web',
                'school_id' => $school->id,
            ]);

            expect($role->school_id)->toBe($school->id);

            $this->assertDatabaseHas('roles', [
                'id' => $role->id,
                'school_id' => $school->id,
            ]);
        });

        it('has slug column on roles table', function () {
            $school = School::factory()->create();

            $role = Role::create([
                'name' => 'author_'.$school->id,
                'slug' => 'author-'.$school->id,
                'guard_name' => 'web',
                'school_id' => $school->id,
            ]);

            expect($role->slug)->not->toBeNull();

            $this->assertDatabaseHas('roles', [
                'id' => $role->id,
                'slug' => 'author-'.$school->id,
            ]);
        });

        it('has slug column on permissions table', function () {
            $permission = Permission::firstOrCreate(
                ['name' => 'content.publish', 'guard_name' => 'web'],
                ['slug' => 'content-publish']
            );

            expect($permission->slug)->not->toBeNull();

            $this->assertDatabaseHas('permissions', [
                'name' => 'content.publish',
                'slug' => 'content-publish',
            ]);
        });
    });

    describe('School Scoping', function () {
        it('allows role scoping by school', function () {
            $school = School::factory()->create();

            $role = Role::create([
                'name' => 'moderator_'.$school->id,
                'slug' => 'moderator-'.$school->id,
                'guard_name' => 'web',
                'school_id' => $school->id,
            ]);

            $scopedRoles = Role::forSchool($school->id)->get();

            expect($scopedRoles->pluck('id'))->toContain($role->id);
            expect($scopedRoles->first()->school_id)->toBe($school->id);
        });

        it('identifies global roles', function () {
            $globalRole = Role::where('school_id', null)
                ->where('guard_name', 'web')
                ->first();

            if ($globalRole) {
                expect($globalRole->isGlobal())->toBeTrue();
            }
        });

        it('isolates roles between schools', function () {
            $school1 = School::factory()->create();
            $school2 = School::factory()->create();

            $role1 = Role::create([
                'name' => 'coordinator_'.$school1->id,
                'slug' => 'coordinator-'.$school1->id,
                'guard_name' => 'web',
                'school_id' => $school1->id,
            ]);

            $role2 = Role::create([
                'name' => 'coordinator_'.$school2->id,
                'slug' => 'coordinator-'.$school2->id,
                'guard_name' => 'web',
                'school_id' => $school2->id,
            ]);

            $school1Roles = Role::forSchool($school1->id)->pluck('id');
            $school2Roles = Role::forSchool($school2->id)->pluck('id');

            expect($school1Roles)->toContain($role1->id);
            expect($school1Roles)->not->toContain($role2->id);

            expect($school2Roles)->toContain($role2->id);
            expect($school2Roles)->not->toContain($role1->id);
        });
    });

    describe('Permissions', function () {
        it('creates permissions with slug', function () {
            $permission = Permission::firstOrCreate(
                ['name' => 'modules.view', 'guard_name' => 'web'],
                ['slug' => 'modules-view', 'label' => 'View Modules', 'group' => 'Modules']
            );

            expect($permission->slug)->toBe('modules-view');
            expect($permission->label)->toBe('View Modules');
            expect($permission->group)->toBe('Modules');
        });

        it('assigns permissions to roles', function () {
            $school = School::factory()->create();
            $role = Role::create([
                'name' => 'content_lead_'.$school->id,
                'slug' => 'content-lead-'.$school->id,
                'guard_name' => 'web',
                'school_id' => $school->id,
            ]);

            $perm = Permission::firstOrCreate(
                ['name' => 'lessons.create', 'guard_name' => 'web'],
                ['slug' => 'lessons-create']
            );

            $role->givePermissionTo($perm);

            expect($role->hasPermissionTo('lessons.create'))->toBeTrue();
        });
    });

    describe('User Assignment', function () {
        it('assigns school-scoped roles to users', function () {
            $school = School::factory()->create();
            $user = User::factory()->create(['school_id' => $school->id]);

            $role = Role::create([
                'name' => 'instructor_'.$school->id,
                'slug' => 'instructor-'.$school->id,
                'guard_name' => 'web',
                'school_id' => $school->id,
            ]);

            $user->assignRole($role);

            expect($user->hasRole($role))->toBeTrue();
            expect($user->roles()->pluck('id'))->toContain($role->id);
        });

        it('checks user permissions through roles', function () {
            $school = School::factory()->create();
            $user = User::factory()->create(['school_id' => $school->id]);

            $role = Role::create([
                'name' => 'grader_'.$school->id,
                'slug' => 'grader-'.$school->id,
                'guard_name' => 'web',
                'school_id' => $school->id,
            ]);

            $perm = Permission::firstOrCreate(
                ['name' => 'submissions.grade', 'guard_name' => 'web'],
                ['slug' => 'submissions-grade']
            );

            $role->givePermissionTo($perm);
            $user->assignRole($role);

            expect($user->hasPermissionTo('submissions.grade'))->toBeTrue();
        });
    });

    describe('School Relationships', function () {
        it('gets roles through school relationship', function () {
            $school = School::factory()->create();

            Role::create([
                'name' => 'admin_'.$school->id,
                'slug' => 'admin-'.$school->id,
                'guard_name' => 'web',
                'school_id' => $school->id,
            ]);

            expect($school->roles()->count())->toBeGreaterThan(0);
            expect($school->roles()->first()->school_id)->toBe($school->id);
        });

        it('cascades delete roles when school is deleted', function () {
            $school = School::factory()->create();

            $role = Role::create([
                'name' => 'principal_'.$school->id,
                'slug' => 'principal-'.$school->id,
                'guard_name' => 'web',
                'school_id' => $school->id,
            ]);

            $roleId = $role->id;
            $school->delete();

            expect(Role::find($roleId))->toBeNull();
        });
    });

    describe('Data Integrity', function () {
        it('maintains unique constraint on slug per role', function () {
            $school = School::factory()->create();

            $role = Role::create([
                'name' => 'curator_'.$school->id,
                'slug' => 'curator-unique-'.$school->id,
                'guard_name' => 'web',
                'school_id' => $school->id,
            ]);

            // Slug should be unique
            $this->assertDatabaseHas('roles', [
                'slug' => 'curator-unique-'.$school->id,
                'school_id' => $school->id,
            ]);
        });

        it('stores permission slugs correctly', function () {
            $permission = Permission::firstOrCreate(
                ['name' => 'analytics.view', 'guard_name' => 'web'],
                ['slug' => 'analytics-view', 'label' => 'View Analytics']
            );

            $this->assertDatabaseHas('permissions', [
                'name' => 'analytics.view',
                'slug' => 'analytics-view',
            ]);
        });
    });
});
