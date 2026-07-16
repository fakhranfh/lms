<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentEngineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();

        foreach ($schools as $school) {
            $this->seedSchoolContent($school);
        }
    }

    /**
     * Seed content for a specific school.
     */
    private function seedSchoolContent(School $school): void
    {
        // Get or create instructors for the school
        $instructors = User::where('school_id', $school->id)
            ->limit(2)
            ->get();

        if ($instructors->isEmpty()) {
            $instructors = User::factory()
                ->for($school)
                ->count(2)
                ->create();
        }

        // Create sample courses
        $this->createSampleCourses($school, $instructors);
    }

    /**
     * Create sample courses with modules and lessons.
     *
     * @param  Collection<int, User>  $instructors
     */
    private function createSampleCourses(School $school, $instructors): void
    {
        $courses = [
            [
                'title' => 'Introduction to Web Development',
                'slug' => 'intro-web-development',
                'description' => 'Learn the fundamentals of web development including HTML, CSS, and JavaScript.',
                'is_published' => true,
                'modules' => [
                    [
                        'title' => 'Getting Started',
                        'description' => 'Set up your development environment',
                        'lessons' => [
                            [
                                'title' => 'What is Web Development?',
                                'content' => 'Web development is the process of building applications using web technologies like HTML, CSS, and JavaScript.',
                                'duration_minutes' => 10,
                            ],
                            [
                                'title' => 'Setting Up Your Environment',
                                'content' => 'In this lesson, we will set up VS Code, Node.js, and other essential tools.',
                                'duration_minutes' => 15,
                            ],
                        ],
                    ],
                    [
                        'title' => 'HTML Basics',
                        'description' => 'Learn HTML fundamentals and structure',
                        'lessons' => [
                            [
                                'title' => 'HTML Introduction',
                                'content' => 'Learn the basic structure of HTML documents and common tags.',
                                'duration_minutes' => 20,
                                'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                            ],
                            [
                                'title' => 'Forms and Input',
                                'content' => 'Master HTML forms and different input types.',
                                'duration_minutes' => 25,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Business Communication',
                'slug' => 'business-communication',
                'description' => 'Improve your professional communication skills.',
                'is_published' => true,
                'modules' => [
                    [
                        'title' => 'Written Communication',
                        'description' => 'Master email and document writing',
                        'lessons' => [
                            [
                                'title' => 'Professional Email Writing',
                                'content' => 'Learn how to write clear, concise, and professional emails.',
                                'duration_minutes' => 12,
                            ],
                            [
                                'title' => 'Business Proposals',
                                'content' => 'Create compelling business proposals that win clients.',
                                'duration_minutes' => 30,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Advanced Data Analysis',
                'slug' => 'advanced-data-analysis',
                'description' => 'Deep dive into data analysis techniques.',
                'is_published' => false,
                'modules' => [
                    [
                        'title' => 'Statistical Analysis',
                        'description' => 'Learn statistical concepts for data analysis',
                        'lessons' => [
                            [
                                'title' => 'Descriptive Statistics',
                                'content' => 'Understand mean, median, mode, and standard deviation.',
                                'duration_minutes' => 35,
                            ],
                        ],
                    ],
                ],
            ],
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
                ]);

                foreach ($lessons as $lessonOrder => $lessonData) {
                    $videoUrl = $lessonData['video_url'] ?? null;
                    unset($lessonData['video_url']);

                    Lesson::create([
                        'id' => Str::uuid(),
                        ...$lessonData,
                        'module_id' => $module->id,
                        'order' => $lessonOrder + 1,
                        'is_published' => true,
                        'video_embed_url' => $videoUrl,
                    ]);
                }
            }
        }
    }
}
