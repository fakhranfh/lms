<?php

namespace Database\Seeders;

use App\Enums\CourseMembershipStatus;
use App\Enums\RoleInCourse;
use App\Models\CoursePerson;
use App\Models\School;
use App\Models\User;
use App\Services\CourseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    /**
     * Seed demo courses for every school. Sessions (and their subtopics,
     * materials, video conferences) are seeded separately by SessionSeeder.
     */
    public function run(CourseService $courseService): void
    {
        foreach (School::all() as $school) {
            $this->seedSchoolCourses($school, $courseService);
        }
    }

    private function seedSchoolCourses(School $school, CourseService $courseService): void
    {
        $teachers = User::whereHas('memberSchools', fn ($q) => $q->where('schools.id', $school->id))
            ->whereHas('roles', fn ($q) => $q->where('name', 'Teacher'))
            ->get();

        if ($teachers->isEmpty()) {
            $teachers = User::factory()->forSchool($school)->count(2)->create();
            $teachers->each(fn ($user) => $user->assignRole('Teacher'));
        }

        foreach ($this->courseBlueprints() as $courseData) {
            $teacher = $teachers->random();
            $title = $courseData['title'];

            $course = $courseService->create([
                'id' => (string) Str::uuid(),
                'school_id' => $school->id,
                'created_by' => $teacher->id,
                'title' => $title,
                'description' => $courseData['description'],
            ]);

            CoursePerson::create([
                'id' => (string) Str::uuid(),
                'course_id' => $course->id,
                'user_id' => $teacher->id,
                'role_in_course' => RoleInCourse::Teacher,
                'enrolled_at' => now(),
                'status' => CourseMembershipStatus::Active,
            ]);
        }
    }

    /**
     * @return array<int, array{title: string, description: string}>
     */
    private function courseBlueprints(): array
    {
        return [
            [
                'title' => 'Web Development Fundamentals',
                'description' => 'Learn the foundations of modern web development. Master HTML, CSS, and JavaScript to build responsive, interactive websites.',
            ],
            [
                'title' => 'Backend Development with Laravel',
                'description' => 'Build powerful backend applications using Laravel. Learn routing, databases, authentication, and API design patterns.',
            ],
            [
                'title' => 'PHP Development Fundamentals',
                'description' => 'Master PHP programming from basics to advanced OOP concepts. Learn modern PHP practices, error handling, and best practices for production-ready applications.',
            ],
            [
                'title' => 'Modern Frontend Development',
                'description' => 'Master modern frontend technologies: HTML5, CSS4, JavaScript ES2024, and responsive design. Build beautiful, performant web interfaces.',
            ],
            [
                'title' => 'Database Design & SQL',
                'description' => 'Learn relational database design, SQL optimization, and best practices. Design efficient databases that scale.',
            ],
            [
                'title' => 'Building RESTful APIs',
                'description' => 'Design and build scalable RESTful APIs. Learn REST principles, API design patterns, authentication, versioning, and testing.',
            ],
            [
                'title' => 'Data Structures & Algorithms',
                'description' => 'Master fundamental data structures and algorithms. Improve problem-solving skills and write efficient code.',
            ],
        ];
    }
}
