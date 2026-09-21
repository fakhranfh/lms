<div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
    <h2 class="text-title-md font-title-md font-bold text-on-surface mb-space-md">Latest Forum Posts</h2>

    @if($this->forumPosts->isEmpty())
        <p class="text-body-sm text-secondary">No forum activity yet.</p>
    @else
        <ul class="divide-y divide-outline-variant">
            @foreach($this->forumPosts as $thread)
                <li class="py-space-sm">
                    <a href="{{ route('forum.thread.show', ['course' => $thread->forum->course_id, 'thread' => $thread->id]) }}"
                       class="block hover:opacity-80 transition-opacity duration-150">
                        <div class="flex items-center justify-between gap-space-md">
                            <p class="text-body-md text-on-surface truncate">{{ $thread->title }}</p>
                            <span class="text-label-sm text-secondary shrink-0">{{ $thread->created_at_display?->diffForHumans() }}</span>
                        </div>
                        <p class="text-body-sm text-secondary truncate">
                            {{ $thread->forum->course->title ?? '' }} &middot; {{ $thread->user->name ?? 'Unknown' }}
                        </p>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
