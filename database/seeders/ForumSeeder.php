<?php

namespace Database\Seeders;

use App\Enums\CourseMembershipStatus;
use App\Enums\RoleInCourse;
use App\Models\Course;
use App\Models\CoursePerson;
use App\Models\Forum;
use App\Models\ForumComment;
use App\Models\ForumThread;
use App\Models\School;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ForumSeeder extends Seeder
{
    /**
     * Seed per-session forum threads (with comments, replies, and likes)
     * for existing courses that don't have any forum threads yet.
     */
    public function run(): void
    {
        $courses = Course::with('sessions')
            ->whereDoesntHave('forums.threads')
            ->get();

        foreach ($courses as $course) {
            $this->seedCourseForums($course);
        }
    }

    private function seedCourseForums(Course $course): void
    {
        $teacher = CoursePerson::where('course_id', $course->id)
            ->where('role_in_course', RoleInCourse::Teacher)
            ->first()
            ?->user;

        if (! $teacher) {
            return;
        }

        $students = $this->studentsForCourse($course);

        foreach ($course->sessions as $session) {
            $forum = Forum::firstOrCreate(
                ['course_id' => $course->id, 'session_id' => $session->id],
                ['title' => null, 'created_by' => $teacher->id]
            );

            $this->seedForumThreads($forum, $session, $teacher, $students, threadCount: random_int(2, 5));
        }
    }

    /**
     * Reuse existing enrolled students for the course, enrolling a small
     * demo pool of new ones if none exist yet.
     *
     * @return Collection<int, User>
     */
    private function studentsForCourse(Course $course): Collection
    {
        $studentIds = CoursePerson::where('course_id', $course->id)
            ->where('role_in_course', RoleInCourse::Student)
            ->pluck('user_id');

        $existing = User::whereIn('id', $studentIds)->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $school = School::findOrFail($course->school_id);
        $students = User::factory()->forSchool($school)->count(4)->create();

        $students->each(function (User $student) use ($course): void {
            $student->assignRole('Student');

            CoursePerson::create([
                'id' => (string) Str::uuid(),
                'course_id' => $course->id,
                'user_id' => $student->id,
                'role_in_course' => RoleInCourse::Student,
                'enrolled_at' => now(),
                'status' => CourseMembershipStatus::Active,
            ]);
        });

        return $students;
    }

    /**
     * @param  Collection<int, User>  $students
     */
    private function seedForumThreads(Forum $forum, Session $session, User $teacher, Collection $students, int $threadCount): void
    {
        $topic = $session->title;
        $titles = $this->threadTitlesForTopic($topic);

        for ($i = 0; $i < $threadCount; $i++) {
            $author = $i === 0 ? $teacher : $students->concat([$teacher])->random();

            $thread = ForumThread::create([
                'forum_id' => $forum->id,
                'user_id' => $author->id,
                'title' => $titles[$i % count($titles)],
                'description' => "<p>Let's discuss this topic related to {$topic}. Share your thoughts, questions, and examples below.</p>",
            ]);

            $this->seedComments($thread, $teacher, $students);
        }
    }

    /**
     * @param  Collection<int, User>  $students
     */
    private function seedComments(ForumThread $thread, User $teacher, Collection $students): void
    {
        $participants = $students->concat([$teacher]);
        $commentCount = random_int(3, 10);
        $commentBodies = $this->commentBodyBank();

        for ($i = 0; $i < $commentCount; $i++) {
            $author = $participants->random();

            $comment = $this->createComment($thread->id, null, $author->id, $commentBodies);
            $this->seedLikes($comment, $participants);

            $replyCount = random_int(0, 2);

            for ($r = 0; $r < $replyCount; $r++) {
                $replyAuthor = $participants->random();
                $reply = $this->createComment($thread->id, $comment->id, $replyAuthor->id, $commentBodies);
                $this->seedLikes($reply, $participants);
            }
        }

        $thread->update(['comments_count' => $thread->comments()->count()]);
    }

    /**
     * @param  array<int, string>  $commentBodies
     */
    private function createComment(string $threadId, ?string $parentId, string $userId, array $commentBodies): ForumComment
    {
        return ForumComment::create([
            'thread_id' => $threadId,
            'parent_id' => $parentId,
            'user_id' => $userId,
            'body' => '<p>'.$commentBodies[array_rand($commentBodies)].'</p>',
        ]);
    }

    /**
     * @param  Collection<int, User>  $participants
     */
    private function seedLikes(ForumComment $comment, Collection $participants): void
    {
        $otherParticipants = $participants->where('id', '!=', $comment->user_id)->values();
        $likers = $otherParticipants->random(min(random_int(0, 3), $otherParticipants->count()));

        foreach ($likers as $liker) {
            $comment->likes()->create(['user_id' => $liker->id]);
        }

        $comment->update(['likes_count' => $comment->likes()->count()]);
    }

    /**
     * @return array<int, string>
     */
    private function threadTitlesForTopic(string $topic): array
    {
        return [
            "Questions about {$topic}",
            "Key takeaways from {$topic}",
            "How does {$topic} apply in practice?",
            "Discussion: challenges with {$topic}",
            "Resources for {$topic}",
        ];
    }

    /**
     * @return array<int, string>
     */
    private function commentBodyBank(): array
    {
        return [
            'This makes a lot of sense, thanks for explaining it clearly.',
            "I'm still a bit confused about this part, could someone give another example?",
            'I ran into a similar issue in my project and this really helped.',
            'Great point! I hadn\'t considered that angle before.',
            'Does this apply to the assignment we submitted last week too?',
            'Thanks, this cleared up my confusion from the lecture.',
            'I found an additional resource that expands on this if anyone is interested.',
            'Can we go over this again in the next tutorial session?',
            'This is exactly what I needed to finish my assignment.',
            'Agreed, this is one of the trickier topics in the course.',
        ];
    }
}
