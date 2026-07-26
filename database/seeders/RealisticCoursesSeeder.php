<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RealisticCoursesSeeder extends Seeder
{
    public function run(): void
    {
        // Hapus semua courses
        Course::truncate();

        $schools = School::all();

        foreach ($schools as $school) {
            $this->seedSchoolCourses($school);
        }
    }

    private function seedSchoolCourses(School $school): void
    {
        $instructors = User::whereHas('memberSchools', function ($q) use ($school) {
            $q->where('schools.id', $school->id);
        })
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Instructor');
            })
            ->get();

        if ($instructors->isEmpty()) {
            $instructors = User::factory()
                ->forSchool($school)
                ->count(2)
                ->create();
            $instructors->each(fn ($user) => $user->assignRole('Instructor'));
        }

        $this->createRealisticCourses($school, $instructors);
    }

    private function createRealisticCourses(School $school, $instructors): void
    {
        $courses = [
            $this->phpDevelopmentCourse(),
            $this->laravel13MasteryCourse(),
            $this->frontendModernCourse(),
            $this->databaseDesignCourse(),
            $this->restApiCourse(),
        ];

        foreach ($courses as $courseData) {
            $instructor = $instructors->random();
            $modules = $courseData['modules'];
            unset($courseData['modules']);

            $course = Course::create([
                'id' => Str::uuid(),
                ...$courseData,
                'school_id' => $school->id,
                'created_by' => $instructor->id,
            ]);

            foreach ($modules as $moduleOrder => $moduleData) {
                $lessons = $moduleData['lessons'];
                unset($moduleData['lessons']);

                $module = Module::create([
                    'id' => Str::uuid(),
                    ...$moduleData,
                    'course_id' => $course->id,
                    'order' => $moduleOrder + 1,
                    'is_published' => true,
                ]);

                foreach ($lessons as $lessonOrder => $lessonData) {
                    Lesson::create([
                        'id' => Str::uuid(),
                        ...$lessonData,
                        'module_id' => $module->id,
                        'order' => $lessonOrder + 1,
                        'is_published' => true,
                    ]);
                }
            }
        }
    }

    private function phpDevelopmentCourse(): array
    {
        return [
            'title' => 'PHP Development Fundamentals',
            'slug' => 'php-development-fundamentals',
            'description' => 'Master PHP programming from basics to advanced OOP concepts. Learn modern PHP practices, error handling, and best practices for production-ready applications.',
            'is_published' => true,
            'modules' => [
                [
                    'title' => 'PHP Basics',
                    'description' => 'Get started with PHP syntax and fundamental concepts',
                    'lessons' => [
                        [
                            'title' => 'Introduction to PHP',
                            'content' => 'PHP is a server-side scripting language. Learn how PHP works, its history, and why it\'s widely used in web development. We\'ll cover the PHP runtime, syntax, and how to execute your first PHP script.',
                            'duration_minutes' => 15,
                        ],
                        [
                            'title' => 'Variables and Data Types',
                            'content' => 'Understand PHP variables, scalar types (int, float, string, bool), and compound types (array, object). Learn about type juggling and type casting in PHP.',
                            'duration_minutes' => 20,
                        ],
                        [
                            'title' => 'Operators and Control Flow',
                            'content' => 'Master arithmetic, comparison, logical, and assignment operators. Explore if/else, switch statements, and ternary operators for controlling program flow.',
                            'duration_minutes' => 25,
                        ],
                    ],
                ],
                [
                    'title' => 'Functions and Arrays',
                    'description' => 'Deep dive into PHP functions and array manipulation',
                    'lessons' => [
                        [
                            'title' => 'Function Basics',
                            'content' => 'Learn how to define and call functions in PHP. Explore parameters, return values, variable scope, and function best practices.',
                            'duration_minutes' => 20,
                        ],
                        [
                            'title' => 'Arrays and Collections',
                            'content' => 'Master indexed arrays, associative arrays, and multi-dimensional arrays. Learn array functions like array_map, array_filter, and array_reduce.',
                            'duration_minutes' => 30,
                        ],
                    ],
                ],
                [
                    'title' => 'Object-Oriented Programming',
                    'description' => 'Learn OOP principles and patterns in PHP',
                    'lessons' => [
                        [
                            'title' => 'Classes and Objects',
                            'content' => 'Understand classes, objects, properties, and methods. Learn about constructors, access modifiers (public, private, protected), and $this variable.',
                            'duration_minutes' => 25,
                        ],
                        [
                            'title' => 'Inheritance and Polymorphism',
                            'content' => 'Explore class inheritance, method overriding, and polymorphism. Learn about abstract classes, interfaces, and traits in PHP.',
                            'duration_minutes' => 28,
                        ],
                        [
                            'title' => 'Design Patterns',
                            'content' => 'Master common design patterns: Singleton, Factory, Observer, and Strategy. Learn when and how to use each pattern in real applications.',
                            'duration_minutes' => 35,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function laravel13MasteryCourse(): array
    {
        return [
            'title' => 'Laravel 13 Mastery Course',
            'slug' => 'laravel-13-mastery',
            'description' => 'Complete guide to Laravel 13 framework. Build modern web applications using Laravel\'s elegant syntax, powerful features, and best practices.',
            'is_published' => true,
            'modules' => [
                [
                    'title' => 'Laravel Fundamentals',
                    'description' => 'Getting started with Laravel 13',
                    'lessons' => [
                        [
                            'title' => 'Laravel Project Setup',
                            'content' => 'Install Laravel using Composer, understand project structure, configure your environment, and run your first Laravel application on localhost.',
                            'duration_minutes' => 18,
                        ],
                        [
                            'title' => 'Routing and Controllers',
                            'content' => 'Learn Laravel routing system, RESTful routes, controller basics, and how to handle HTTP requests and responses.',
                            'duration_minutes' => 22,
                        ],
                    ],
                ],
                [
                    'title' => 'Database & Eloquent ORM',
                    'description' => 'Database design and Eloquent ORM mastery',
                    'lessons' => [
                        [
                            'title' => 'Migrations and Schema',
                            'content' => 'Create and manage database migrations, define table schemas, modify tables, and handle rollbacks safely in production.',
                            'duration_minutes' => 24,
                        ],
                        [
                            'title' => 'Eloquent Models',
                            'content' => 'Define Eloquent models, set up relationships (hasMany, belongsTo, belongsToMany), use query builder, and optimize queries.',
                            'duration_minutes' => 32,
                        ],
                        [
                            'title' => 'Advanced Querying',
                            'content' => 'Eager loading with with(), lazy loading strategies, query optimization, and N+1 problem solutions.',
                            'duration_minutes' => 28,
                        ],
                    ],
                ],
                [
                    'title' => 'Authentication & Authorization',
                    'description' => 'Secure your Laravel applications',
                    'lessons' => [
                        [
                            'title' => 'Authentication Basics',
                            'content' => 'Implement user authentication with Laravel Fortify, understand middleware, and handle login/logout flows.',
                            'duration_minutes' => 26,
                        ],
                        [
                            'title' => 'Authorization with Permissions',
                            'content' => 'Role-based access control (RBAC), permission system, policy classes, and authorization gates in Laravel.',
                            'duration_minutes' => 30,
                        ],
                    ],
                ],
                [
                    'title' => 'Advanced Features',
                    'description' => 'Master advanced Laravel capabilities',
                    'lessons' => [
                        [
                            'title' => 'Livewire Components',
                            'content' => 'Build interactive web components with Livewire. Learn lifecycle hooks, data binding, validation, and real-time updates.',
                            'duration_minutes' => 35,
                        ],
                        [
                            'title' => 'Queues and Jobs',
                            'content' => 'Handle long-running tasks with queued jobs. Explore Redis and database queues, job scheduling, and monitoring.',
                            'duration_minutes' => 28,
                        ],
                        [
                            'title' => 'Testing Best Practices',
                            'content' => 'Write unit and feature tests with Pest. Mock dependencies, assert responses, and maintain high test coverage.',
                            'duration_minutes' => 32,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function frontendModernCourse(): array
    {
        return [
            'title' => 'Modern Frontend Development',
            'slug' => 'modern-frontend-development',
            'description' => 'Master modern frontend technologies: HTML5, CSS4, JavaScript ES2024, and responsive design. Build beautiful, performant web interfaces.',
            'is_published' => true,
            'modules' => [
                [
                    'title' => 'HTML5 & Semantic Markup',
                    'description' => 'Write semantic and accessible HTML',
                    'lessons' => [
                        [
                            'title' => 'HTML5 Essentials',
                            'content' => 'Semantic HTML elements, accessibility (a11y), ARIA attributes, and SEO best practices. Learn document structure and best practices.',
                            'duration_minutes' => 20,
                        ],
                        [
                            'title' => 'Forms and Validation',
                            'content' => 'Create accessible forms, use HTML5 validation attributes, implement custom validation, and handle form submission.',
                            'duration_minutes' => 18,
                        ],
                    ],
                ],
                [
                    'title' => 'CSS4 & Tailwind CSS',
                    'description' => 'Modern styling with CSS and utility frameworks',
                    'lessons' => [
                        [
                            'title' => 'CSS Fundamentals',
                            'content' => 'CSS selectors, box model, flexbox, CSS Grid, positioning, and modern layout techniques for responsive design.',
                            'duration_minutes' => 28,
                        ],
                        [
                            'title' => 'Tailwind CSS Mastery',
                            'content' => 'Utility-first CSS with Tailwind. Learn configuration, customization, creating reusable components, and optimization.',
                            'duration_minutes' => 25,
                        ],
                        [
                            'title' => 'Animations & Transitions',
                            'content' => 'CSS animations, transitions, transforms, and performance optimization. Create smooth, delightful user experiences.',
                            'duration_minutes' => 22,
                        ],
                    ],
                ],
                [
                    'title' => 'JavaScript ES2024',
                    'description' => 'Master modern JavaScript',
                    'lessons' => [
                        [
                            'title' => 'ES6+ Fundamentals',
                            'content' => 'Arrow functions, destructuring, spread operator, template literals, and modern syntax for cleaner code.',
                            'duration_minutes' => 24,
                        ],
                        [
                            'title' => 'Async JavaScript',
                            'content' => 'Promises, async/await, error handling, and asynchronous programming patterns for API calls and data fetching.',
                            'duration_minutes' => 26,
                        ],
                        [
                            'title' => 'DOM Manipulation',
                            'content' => 'Query selectors, event handling, dynamic DOM updates, event delegation, and performance optimization.',
                            'duration_minutes' => 23,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function databaseDesignCourse(): array
    {
        return [
            'title' => 'Database Design & SQL',
            'slug' => 'database-design-sql',
            'description' => 'Learn relational database design, SQL optimization, and best practices. Design efficient databases that scale.',
            'is_published' => true,
            'modules' => [
                [
                    'title' => 'Database Fundamentals',
                    'description' => 'Core concepts of relational databases',
                    'lessons' => [
                        [
                            'title' => 'Relational Database Concepts',
                            'content' => 'Tables, rows, columns, keys (primary, foreign), constraints, and database relationships. Understand ACID properties.',
                            'duration_minutes' => 20,
                        ],
                        [
                            'title' => 'Normalization & Schema Design',
                            'content' => 'Normal forms (1NF, 2NF, 3NF), denormalization trade-offs, and designing efficient database schemas.',
                            'duration_minutes' => 28,
                        ],
                    ],
                ],
                [
                    'title' => 'SQL Query Language',
                    'description' => 'Master SQL for data manipulation',
                    'lessons' => [
                        [
                            'title' => 'SELECT & Filtering',
                            'content' => 'SELECT queries, WHERE clauses, comparison operators, logical operators, and filtering techniques.',
                            'duration_minutes' => 22,
                        ],
                        [
                            'title' => 'JOINs & Aggregation',
                            'content' => 'INNER JOIN, LEFT JOIN, RIGHT JOIN, FULL OUTER JOIN, GROUP BY, HAVING, and aggregate functions.',
                            'duration_minutes' => 26,
                        ],
                        [
                            'title' => 'Subqueries & Advanced Queries',
                            'content' => 'Nested queries, correlated subqueries, window functions, and complex query optimization.',
                            'duration_minutes' => 30,
                        ],
                    ],
                ],
                [
                    'title' => 'Performance & Optimization',
                    'description' => 'Optimize database performance',
                    'lessons' => [
                        [
                            'title' => 'Indexing Strategies',
                            'content' => 'Index types, index selection, covering indexes, and how to identify when indexes improve performance.',
                            'duration_minutes' => 24,
                        ],
                        [
                            'title' => 'Query Optimization',
                            'content' => 'EXPLAIN plans, execution strategies, avoiding N+1 problems, and database query tuning techniques.',
                            'duration_minutes' => 28,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function restApiCourse(): array
    {
        return [
            'title' => 'Building RESTful APIs',
            'slug' => 'building-restful-apis',
            'description' => 'Design and build scalable RESTful APIs. Learn REST principles, API design patterns, authentication, versioning, and testing.',
            'is_published' => true,
            'modules' => [
                [
                    'title' => 'REST Principles',
                    'description' => 'Understanding REST architecture',
                    'lessons' => [
                        [
                            'title' => 'REST Fundamentals',
                            'content' => 'REST principles, HTTP methods (GET, POST, PUT, DELETE, PATCH), status codes, and RESTful conventions.',
                            'duration_minutes' => 18,
                        ],
                        [
                            'title' => 'Resource Design',
                            'content' => 'Designing resources, URI conventions, resource hierarchies, and proper REST semantics.',
                            'duration_minutes' => 16,
                        ],
                    ],
                ],
                [
                    'title' => 'API Development with Laravel',
                    'description' => 'Build APIs using Laravel',
                    'lessons' => [
                        [
                            'title' => 'Routing & Controllers',
                            'content' => 'API routes, resource controllers, route model binding, and structuring API endpoints.',
                            'duration_minutes' => 22,
                        ],
                        [
                            'title' => 'API Resources & Responses',
                            'content' => 'Laravel API Resources, JSON transformation, pagination, filtering, sorting, and consistent response formats.',
                            'duration_minutes' => 26,
                        ],
                        [
                            'title' => 'Validation & Error Handling',
                            'content' => 'Request validation, custom validation rules, error responses, and proper HTTP error handling.',
                            'duration_minutes' => 20,
                        ],
                    ],
                ],
                [
                    'title' => 'Security & Best Practices',
                    'description' => 'Secure your APIs',
                    'lessons' => [
                        [
                            'title' => 'API Authentication',
                            'content' => 'Token-based authentication (JWT, OAuth2), API keys, and securing endpoints.',
                            'duration_minutes' => 24,
                        ],
                        [
                            'title' => 'Rate Limiting & Security',
                            'content' => 'Rate limiting, CORS handling, CSRF protection, input validation, and security best practices.',
                            'duration_minutes' => 25,
                        ],
                        [
                            'title' => 'API Testing',
                            'content' => 'Testing API endpoints, mocking external services, and using tools like Postman and Pest.',
                            'duration_minutes' => 22,
                        ],
                    ],
                ],
            ],
        ];
    }
}
