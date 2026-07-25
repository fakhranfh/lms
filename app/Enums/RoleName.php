<?php

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'Admin';
    case SchoolAdmin = 'School Admin';
    case Instructor = 'Instructor';
    case Student = 'Student';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::SchoolAdmin => 'School Admin',
            self::Instructor => 'Instructor',
            self::Student => 'Student',
        };
    }

    public function slug(): string
    {
        return match ($this) {
            self::Admin => 'admin',
            self::SchoolAdmin => 'school-admin',
            self::Instructor => 'instructor',
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
            self::SchoolAdmin => [], // resolved by the seeder as "all permissions except settings.billing", scoped to the school
            self::Instructor => [
                'courses.create', 'courses.view', 'courses.edit', 'courses.delete',
                'modules.create', 'modules.view', 'modules.edit', 'modules.delete',
                'lessons.create', 'lessons.view', 'lessons.edit', 'lessons.delete',
                'assignments.create', 'assignments.view', 'assignments.edit', 'assignments.delete',
                'submissions.view', 'submissions.grade', 'submissions.override-grade',
                'analytics.view',
            ],
            self::Student => [
                'courses.view', 'modules.view', 'lessons.view', 'assignments.view', 'submissions.view',
            ],
        };
    }
}
