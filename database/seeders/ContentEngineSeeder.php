<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentEngineSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::all();

        foreach ($schools as $school) {
            $this->seedSchoolCourses($school);
        }
    }

    private function seedSchoolCourses(School $school): void
    {
        $teachers = User::whereHas('memberSchools', function ($q) use ($school) {
            $q->where('schools.id', $school->id);
        })
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Teacher');
            })
            ->get();

        if ($teachers->isEmpty()) {
            $teachers = User::factory()
                ->forSchool($school)
                ->count(2)
                ->create();
            $teachers->each(fn ($user) => $user->assignRole('Teacher'));
        }

        $this->createDemoCourses($school, $teachers);
    }

    private function createDemoCourses(School $school, $teachers): void
    {
        $courses = [
            [
                'title' => 'Web Development Fundamentals',
                'description' => 'Learn the foundations of modern web development. Master HTML, CSS, and JavaScript to build responsive, interactive websites.',
                'is_published' => true,
            ],
            [
                'title' => 'Backend Development with Laravel',
                'description' => 'Build powerful backend applications using Laravel. Learn routing, databases, authentication, and API design patterns.',
                'is_published' => true,
            ],
            [
                'title' => 'Data Structures & Algorithms',
                'description' => 'Master fundamental data structures and algorithms. Improve problem-solving skills and write efficient code.',
                'is_published' => false,
            ],
        ];

        foreach ($courses as $courseData) {
            $teacher = $teachers->random();

            $course = Course::create([
                'id' => (string) Str::uuid(),
                'school_id' => $school->id,
                'created_by' => $teacher->id,
                'title' => $courseData['title'],
                'slug' => Str::slug($courseData['title']).'-'.Str::random(6),
                'description' => $courseData['description'],
                'is_published' => $courseData['is_published'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->createModulesForCourse($course);
        }
    }

    private function createModulesForCourse(Course $course): void
    {
        $modulesByTitle = $this->getModulesForCourse($course->title);

        foreach ($modulesByTitle as $index => $moduleData) {
            $module = Module::create([
                'id' => (string) Str::uuid(),
                'course_id' => $course->id,
                'title' => $moduleData['title'],
                'description' => $moduleData['description'],
                'order' => $index + 1,
                'is_published' => $course->is_published,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->createLessonsForModule($module, $moduleData['lessons']);
        }
    }

    private function createLessonsForModule(Module $module, array $lessons): void
    {
        foreach ($lessons as $index => $lessonData) {
            $lesson = Lesson::create([
                'id' => (string) Str::uuid(),
                'module_id' => $module->id,
                'title' => $lessonData['title'],
                'content' => $lessonData['content'],
                'order' => $index + 1,
                'is_published' => $module->is_published,
                'duration_minutes' => fake()->numberBetween(30, 120),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->createAssignmentForLesson($lesson);
        }
    }

    private function createAssignmentForLesson(Lesson $lesson): void
    {
        Assignment::create([
            'id' => (string) Str::uuid(),
            'lesson_id' => $lesson->id,
            'title' => "{$lesson->title} — Reflection Essay",
            'prompt_question' => "Based on what you learned in \"{$lesson->title}\", write a short essay explaining the key concepts and how you would apply them in a real project.",
            'rubric' => [
                ['criterion' => 'Understanding', 'weight' => 40, 'description' => 'Demonstrates clear understanding of the lesson concepts.', 'max_points' => 40],
                ['criterion' => 'Application', 'weight' => 30, 'description' => 'Explains realistic application of the concepts.', 'max_points' => 30],
                ['criterion' => 'Clarity', 'weight' => 30, 'description' => 'Writing is clear, organized, and well-structured.', 'max_points' => 30],
            ],
            'max_score' => 100.00,
            'passing_score' => 60.00,
            'is_published' => $lesson->is_published,
            'allow_multiple_submissions' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getModulesForCourse(string $courseTitle): array
    {
        return match ($courseTitle) {
            'Web Development Fundamentals' => [
                [
                    'title' => 'HTML & CSS Foundations',
                    'description' => 'Learn the building blocks of web pages with HTML and styling with CSS.',
                    'lessons' => [
                        [
                            'title' => 'Introduction to HTML',
                            'content' => 'HTML (HyperText Markup Language) is the standard markup language for creating web pages. This lesson covers semantic HTML, document structure, and best practices.',
                        ],
                        [
                            'title' => 'CSS Styling & Responsive Layout',
                            'content' => 'Cascading Style Sheets (CSS) control the visual presentation of HTML elements. Learn flexbox, grid, and responsive design principles for modern web layouts.',
                        ],
                    ],
                ],
                [
                    'title' => 'JavaScript Essentials',
                    'description' => 'Master JavaScript fundamentals and DOM manipulation for interactive web experiences.',
                    'lessons' => [
                        [
                            'title' => 'JavaScript Basics & Syntax',
                            'content' => 'JavaScript is the programming language of the web. Understand variables, data types, operators, control flow, and functions to build dynamic functionality.',
                        ],
                        [
                            'title' => 'DOM Manipulation & Events',
                            'content' => 'Learn how to interact with HTML elements through the Document Object Model. Handle user events and create interactive user interfaces.',
                        ],
                    ],
                ],
            ],
            'Backend Development with Laravel' => [
                [
                    'title' => 'Laravel Foundations',
                    'description' => 'Get started with Laravel framework fundamentals and core concepts.',
                    'lessons' => [
                        [
                            'title' => 'Routing & Controllers',
                            'content' => 'Laravel routing directs HTTP requests to controller methods. Learn how to define routes, use middleware, and structure controllers for clean architecture.',
                        ],
                        [
                            'title' => 'Database & Eloquent Models',
                            'content' => 'Master Laravel\'s ORM (Eloquent) for elegant database interactions. Learn migrations, model relationships, and query optimization techniques.',
                        ],
                    ],
                ],
                [
                    'title' => 'Advanced Patterns & APIs',
                    'description' => 'Build scalable backend systems with design patterns and RESTful APIs.',
                    'lessons' => [
                        [
                            'title' => 'Service Layer Architecture',
                            'content' => 'Implement clean architecture using service classes. Separate business logic from controllers for maintainable and testable code.',
                        ],
                        [
                            'title' => 'RESTful API Design',
                            'content' => 'Design and build RESTful APIs following best practices. Learn about resource naming, HTTP methods, status codes, and API versioning.',
                        ],
                    ],
                ],
            ],
            'Data Structures & Algorithms' => [
                [
                    'title' => 'Core Data Structures',
                    'description' => 'Understand fundamental data structures and their use cases.',
                    'lessons' => [
                        [
                            'title' => 'Arrays & Linked Lists',
                            'content' => 'Master sequential data structures. Compare array and linked list implementations, trade-offs, and operations like insertion, deletion, and traversal.',
                        ],
                        [
                            'title' => 'Trees & Graphs',
                            'content' => 'Learn hierarchical and network data structures. Understand binary trees, BST, graph representations, and traversal algorithms.',
                        ],
                    ],
                ],
                [
                    'title' => 'Algorithm Design & Optimization',
                    'description' => 'Develop efficient algorithms and analyze their complexity.',
                    'lessons' => [
                        [
                            'title' => 'Sorting & Searching Algorithms',
                            'content' => 'Explore sorting algorithms (bubble, quicksort, mergesort) and searching strategies. Analyze time complexity and choose algorithms for different scenarios.',
                        ],
                        [
                            'title' => 'Dynamic Programming',
                            'content' => 'Master optimization techniques using dynamic programming. Break down complex problems into subproblems and build efficient solutions.',
                        ],
                    ],
                ],
            ],
            default => [],
        };
    }
}
