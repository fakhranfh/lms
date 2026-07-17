<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

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
        $instructors = User::where('school_id', $school->id)
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Instructor');
            })
            ->get();

        if ($instructors->isEmpty()) {
            $instructors = User::factory()
                ->for($school)
                ->count(2)
                ->create();
            $instructors->each(fn ($user) => $user->assignRole('Instructor'));
        }

        $this->createDemoCourses($school, $instructors);
    }

    private function createDemoCourses(School $school, $instructors): void
    {
        for ($i = 0; $i < 3; $i++) {
            $instructor = $instructors->random();
            $isPublished = $i < 2;

            $course = Course::factory()
                ->for($school)
                ->for($instructor, 'creator')
                ->state(['is_published' => $isPublished])
                ->create();

            $this->createModulesForCourse($course);
        }
    }

    private function createModulesForCourse(Course $course): void
    {
        $moduleCount = fake()->numberBetween(2, 3);

        for ($i = 0; $i < $moduleCount; $i++) {
            $module = Module::factory()
                ->for($course)
                ->state(['order' => $i + 1, 'is_published' => $course->is_published])
                ->create();

            $this->createLessonsForModule($module);
        }
    }

    private function createLessonsForModule(Module $module): void
    {
        $lessonCount = fake()->numberBetween(1, 2);

        for ($i = 0; $i < $lessonCount; $i++) {
            Lesson::factory()
                ->for($module)
                ->state([
                    'order' => $i + 1,
                    'is_published' => true,
                    'video_embed_url' => fake()->boolean(40) ? fake()->url() : null,
                ])
                ->create();
        }
    }
}
