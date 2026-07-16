<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoLmsDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoSchools = School::whereRaw('LOWER(name) LIKE ?', ['%demo%'])->get();

        foreach ($demoSchools as $school) {
            $this->seedDemoDataForSchool($school);
        }
    }

    /**
     * Seed demo data for a specific school.
     */
    private function seedDemoDataForSchool(School $school): void
    {
        $this->command->info("Seeding demo data for school: {$school->name}");

        $subjects = ['Mathematics', 'Science', 'English Language', 'History', 'Physical Education'];

        foreach ($subjects as $subject) {
            $course = Course::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'code' => strtoupper(substr($subject, 0, 3)).'-'.random_int(100, 999),
                ],
                [
                    'name' => $subject,
                    'description' => "Sample $subject course for demo LMS. This is a demonstration course to showcase the LMS features.",
                    'is_published' => true,
                ]
            );

            $studentCount = random_int(10, 15);
            $students = User::factory()
                ->school($school)
                ->count($studentCount)
                ->create();

            $course->students()->syncWithoutDetaching($students->pluck('id'));

            $this->command->info("Created course: {$course->name} with {$studentCount} students");
        }
    }
}
