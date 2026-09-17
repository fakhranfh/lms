<?php

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'Admin';
    case SchoolAdmin = 'School Admin';
    case Teacher = 'Teacher';
    case Student = 'Student';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::SchoolAdmin => 'School Admin',
            self::Teacher => 'Teacher',
            self::Student => 'Student',
        };
    }

    public function slug(): string
    {
        return match ($this) {
            self::Admin => 'admin',
            self::SchoolAdmin => 'school-admin',
            self::Teacher => 'teacher',
            self::Student => 'student',
        };
    }

    /**
     * Default permission names granted to this role by DefaultRoleSeeder.
     *
     * @return array<int, string>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Admin => [], // resolved by the seeder as "all permissions except settings.billing"
            self::SchoolAdmin => [], // resolved by the seeder via permissionGroups()
            self::Teacher => [
                'courses.create', 'courses.view', 'courses.edit', 'courses.delete',
                'analytics.view',
                'media.view', 'media.create', 'media.delete',
                'sessions.view', 'sessions.create', 'sessions.edit', 'sessions.delete',
                'syllabus.view', 'syllabus.edit',
                'forum.view', 'forum.create', 'forum.moderate',
                'assessment.view', 'assessment.create', 'assessment.edit', 'assessment.delete', 'assessment.grade', 'groups.manage',
                'attendance.view', 'attendance.manage',
                'gradebook.view', 'gradebook.manage',
                'people.view',
                'students.view', 'students.create', 'students.edit', 'students.delete', 'students.import',
            ],
            self::Student => [
                'courses.view',
                'sessions.view',
                'syllabus.view',
                'forum.view', 'forum.create',
                'assessment.view', 'assessment.submit',
                'attendance.view',
                'gradebook.view',
                'people.view',
            ],
        };
    }

    /**
     * Permission groups granted to this role by DefaultRoleSeeder, for roles
     * whose access is scoped by permission group rather than by name.
     *
     * @return array<int, string>
     */
    public function permissionGroups(): array
    {
        return match ($this) {
            self::SchoolAdmin => ['Users', 'Roles', 'Permissions', 'Media', 'Sessions', 'Syllabus', 'Forum', 'Assessment', 'Attendance', 'Gradebook', 'People'],
            default => [],
        };
    }
}
