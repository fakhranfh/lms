<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Course Management
            ['name' => 'courses.create', 'slug' => 'courses-create', 'label' => 'Create Course', 'group' => 'Courses'],
            ['name' => 'courses.view', 'slug' => 'courses-view', 'label' => 'View Courses', 'group' => 'Courses'],
            ['name' => 'courses.edit', 'slug' => 'courses-edit', 'label' => 'Edit Course', 'group' => 'Courses'],
            ['name' => 'courses.delete', 'slug' => 'courses-delete', 'label' => 'Delete Course', 'group' => 'Courses'],

            // Module Management
            ['name' => 'modules.create', 'slug' => 'modules-create', 'label' => 'Create Module', 'group' => 'Modules'],
            ['name' => 'modules.view', 'slug' => 'modules-view', 'label' => 'View Modules', 'group' => 'Modules'],
            ['name' => 'modules.edit', 'slug' => 'modules-edit', 'label' => 'Edit Module', 'group' => 'Modules'],
            ['name' => 'modules.delete', 'slug' => 'modules-delete', 'label' => 'Delete Module', 'group' => 'Modules'],

            // Lesson Management
            ['name' => 'lessons.create', 'slug' => 'lessons-create', 'label' => 'Create Lesson', 'group' => 'Lessons'],
            ['name' => 'lessons.view', 'slug' => 'lessons-view', 'label' => 'View Lessons', 'group' => 'Lessons'],
            ['name' => 'lessons.edit', 'slug' => 'lessons-edit', 'label' => 'Edit Lesson', 'group' => 'Lessons'],
            ['name' => 'lessons.delete', 'slug' => 'lessons-delete', 'label' => 'Delete Lesson', 'group' => 'Lessons'],

            // Assignment Management
            ['name' => 'assignments.create', 'slug' => 'assignments-create', 'label' => 'Create Assignment', 'group' => 'Assignments'],
            ['name' => 'assignments.view', 'slug' => 'assignments-view', 'label' => 'View Assignments', 'group' => 'Assignments'],
            ['name' => 'assignments.edit', 'slug' => 'assignments-edit', 'label' => 'Edit Assignment', 'group' => 'Assignments'],
            ['name' => 'assignments.delete', 'slug' => 'assignments-delete', 'label' => 'Delete Assignment', 'group' => 'Assignments'],

            // Submission Grading
            ['name' => 'submissions.view', 'slug' => 'submissions-view', 'label' => 'View Submissions', 'group' => 'Submissions'],
            ['name' => 'submissions.grade', 'slug' => 'submissions-grade', 'label' => 'Grade Submissions', 'group' => 'Submissions'],
            ['name' => 'submissions.override-grade', 'slug' => 'submissions-override-grade', 'label' => 'Override Grade', 'group' => 'Submissions'],

            // Role Management
            ['name' => 'roles.create', 'slug' => 'roles-create', 'label' => 'Create Role', 'group' => 'Roles'],
            ['name' => 'roles.view', 'slug' => 'roles-view', 'label' => 'View Roles', 'group' => 'Roles'],
            ['name' => 'roles.edit', 'slug' => 'roles-edit', 'label' => 'Edit Role', 'group' => 'Roles'],
            ['name' => 'roles.delete', 'slug' => 'roles-delete', 'label' => 'Delete Role', 'group' => 'Roles'],
            ['name' => 'roles.assign-permissions', 'slug' => 'roles-assign-permissions', 'label' => 'Assign Permissions to Role', 'group' => 'Roles'],

            // User Management
            ['name' => 'users.manage', 'slug' => 'users-manage', 'label' => 'Manage Users', 'group' => 'Users'],
            ['name' => 'users.assign-roles', 'slug' => 'users-assign-roles', 'label' => 'Assign Roles to Users', 'group' => 'Users'],

            // Permission Management
            ['name' => 'permissions.view', 'slug' => 'permissions-view', 'label' => 'View Permissions', 'group' => 'Permissions'],

            // Analytics
            ['name' => 'analytics.view', 'slug' => 'analytics-view', 'label' => 'View Analytics', 'group' => 'Analytics'],

            // School Settings
            ['name' => 'settings.school', 'slug' => 'settings-school', 'label' => 'Manage School Settings', 'group' => 'Settings'],
            ['name' => 'settings.billing', 'slug' => 'settings-billing', 'label' => 'Manage Billing', 'group' => 'Settings'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['slug' => $permission['slug'], 'label' => $permission['label'], 'group' => $permission['group']]
            );
        }
    }
}
