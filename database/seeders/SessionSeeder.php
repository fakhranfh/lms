<?php

namespace Database\Seeders;

use App\Enums\DeliveryMode;
use App\Enums\MaterialType;
use App\Models\Course;
use App\Models\MediaLibraryItem;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SessionSeeder extends Seeder
{
    /**
     * Seed sessions (with subtopics, video conferences, and media library
     * attachments) for existing courses that don't have any sessions yet.
     */
    public function run(): void
    {
        $courses = Course::doesntHave('sessions')->get();

        foreach ($courses as $course) {
            $this->seedCourseSessions($course);
        }
    }

    private function seedCourseSessions(Course $course): void
    {
        $mediaItems = $this->mediaItemsForSchool($course->school_id);

        $start = Carbon::parse('2026-02-23 00:00:00');

        foreach ($this->sessionBlueprints() as $index => $blueprint) {
            $dateStart = (clone $start)->addWeeks($index);
            $dateEnd = (clone $dateStart)->addDays(6)->endOfDay();

            $session = Session::create([
                'course_id' => $course->id,
                'title' => 'Session '.($index + 1).': '.$blueprint['title'],
                'learning_outcome' => $blueprint['learning_outcome'],
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'delivery_mode' => $blueprint['delivery_mode'],
            ]);

            foreach ($blueprint['subtopics'] as $order => $subtopic) {
                $session->subtopics()->create([
                    'subtopic' => $subtopic,
                    'order' => $order + 1,
                ]);
            }

            if ($blueprint['delivery_mode'] === DeliveryMode::Online) {
                $session->videoConferences()->create([
                    'title' => 'Main Meeting',
                    'scheduled_start_at' => $dateStart->copy()->setTime(9, 0),
                    'scheduled_end_at' => $dateStart->copy()->setTime(11, 0),
                    'meeting_url' => 'https://meet.example.com/'.$course->slug.'-session-'.($index + 1),
                    'required_duration_minutes' => 90,
                ]);
            }

            if ($mediaItems->isNotEmpty()) {
                $session->materials()->attach(
                    $mediaItems->random(min(2, $mediaItems->count()))->pluck('id')->values()
                        ->mapWithKeys(fn ($id, $order) => [$id => ['order' => $order + 1]])
                );
            }
        }
    }

    /**
     * Reuse existing media library items for the school, creating a small
     * pool of dummy ones if none exist yet.
     */
    private function mediaItemsForSchool(string $schoolId)
    {
        $existing = MediaLibraryItem::where('school_id', $schoolId)->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        return MediaLibraryItem::factory()
            ->count(4)
            ->state(['school_id' => $schoolId])
            ->sequence(
                ['type' => MaterialType::PDF, 'title' => 'Session Reading Material'],
                ['type' => MaterialType::Presentation, 'title' => 'Session Slides'],
                ['type' => MaterialType::Video, 'title' => 'Session Recap Video'],
                ['type' => MaterialType::Document, 'title' => 'Session Handout'],
            )
            ->create();
    }

    /**
     * @return array<int, array{title: string, learning_outcome: string, delivery_mode: DeliveryMode, subtopics: array<int, string>}>
     */
    private function sessionBlueprints(): array
    {
        return [
            [
                'title' => 'Introduction & Course Overview',
                'learning_outcome' => 'Understand the course structure, expectations, and grading policy.',
                'delivery_mode' => DeliveryMode::Online,
                'subtopics' => ['Course syllabus walkthrough', 'Learning outcomes overview', 'Grading and evaluation policy'],
            ],
            [
                'title' => 'Core Concepts',
                'learning_outcome' => 'Explain the fundamental concepts covered in this course.',
                'delivery_mode' => DeliveryMode::Online,
                'subtopics' => ['Key terminology', 'Foundational theory', 'Real-world examples'],
            ],
            [
                'title' => 'Hands-on Practice',
                'learning_outcome' => 'Apply core concepts through guided exercises.',
                'delivery_mode' => DeliveryMode::Offline,
                'subtopics' => ['Guided exercise walkthrough', 'Common pitfalls', 'Q&A'],
            ],
            [
                'title' => 'Case Study Discussion',
                'learning_outcome' => 'Analyze a real-world case study using concepts learned so far.',
                'delivery_mode' => DeliveryMode::Online,
                'subtopics' => ['Case study background', 'Group discussion', 'Key takeaways'],
            ],
            [
                'title' => 'Advanced Topics',
                'learning_outcome' => 'Explore advanced applications and edge cases.',
                'delivery_mode' => DeliveryMode::Offline,
                'subtopics' => ['Advanced techniques', 'Edge cases', 'Best practices'],
            ],
            [
                'title' => 'Review & Wrap-up',
                'learning_outcome' => 'Consolidate learning from the course and prepare for assessment.',
                'delivery_mode' => DeliveryMode::Online,
                'subtopics' => ['Recap of key topics', 'Sample questions', 'Final Q&A'],
            ],
        ];
    }
}
