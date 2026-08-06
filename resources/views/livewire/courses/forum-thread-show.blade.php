@section('title', $thread->title)

<div class="space-y-space-lg" x-data="{ deleteCommentId: null, showDeleteCommentModal: false, showDeleteThreadModal: false }">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => null])

    <a href="{{ route('forum.index', [$course, 'session' => $thread->forum->session_id]) }}" wire:navigate class="inline-flex items-center gap-space-xs text-body-sm text-on-surface-variant hover:text-on-surface">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to Forum
    </a>

    <!-- Thread -->
    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
        @if ($editingThread)
            <div class="space-y-space-md">
                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Title</label>
                    <input
                        type="text"
                        wire:model="editThreadTitle"
                        class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                    />
                    @error('editThreadTitle') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Description</label>
                    <x-rich-text-editor id="edit-thread-{{ $thread->id }}" wire-model="editThreadDescription" :value="$editThreadDescription" />
                    @error('editThreadDescription') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-space-md">
                    <button wire:click="cancelEditThread" type="button" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">Cancel</button>
                    <button
                        wire:click="updateThread"
                        wire:loading.attr="disabled"
                        wire:target="updateThread"
                        type="button"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="updateThread">Save</span>
                        <span wire:loading wire:target="updateThread">Saving…</span>
                    </button>
                </div>
            </div>
        @else
            <div class="flex items-start justify-between gap-space-md">
                <h1 class="font-headline-md text-headline-md text-on-surface">{{ $thread->title }}</h1>

                <div class="flex items-center gap-space-xs flex-shrink-0">
                    @if ($thread->user_id === auth()->id() || $canModerate)
                        <button wire:click="startEditThread" type="button" class="p-space-sm text-on-surface-variant hover:text-primary transition">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </button>
                        <button @click="showDeleteThreadModal = true" type="button" class="p-space-sm text-on-surface-variant hover:text-error transition">
                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>
                    @endif
                </div>
            </div>
            <div class="text-body-md text-on-surface">{!! $thread->description !!}</div>
            <p class="text-body-xs text-on-surface-variant">
                {{ $thread->user->name }} &middot; {{ $thread->created_at_display->format('d M Y, H:i') }}
            </p>
        @endif
    </div>

    <!-- Comments -->
    <div wire:init="loadComments" class="space-y-space-md">
        <h2 class="font-headline-sm text-headline-sm text-on-surface">Comments ({{ $thread->comments_count }})</h2>

        @if ($canCreate)
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                <x-rich-text-editor id="new-comment" wire-model="newCommentBody" :value="$newCommentBody" />
                @error('newCommentBody') <p class="text-body-xs text-error">{{ $message }}</p> @enderror

                <button
                    wire:click="addComment"
                    wire:loading.attr="disabled"
                    wire:target="addComment"
                    type="button"
                    class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="addComment">Post Comment</span>
                    <span wire:loading wire:target="addComment">Posting…</span>
                </button>
            </div>
        @endif

        @if (! $commentsLoaded)
            <div class="space-y-space-md animate-pulse">
                @for ($i = 0; $i < 3; $i++)
                    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
                        <div class="h-3 bg-surface-container rounded w-1/4"></div>
                        <div class="h-3 bg-surface-container rounded w-full"></div>
                    </div>
                @endfor
            </div>
        @else
            @if ($comments->isEmpty())
                <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center">
                    <p class="text-body-md text-on-surface-variant">No comments yet.</p>
                </div>
            @else
                <div class="space-y-space-md">
                    @foreach ($comments as $comment)
                        <div wire:key="comment-{{ $comment->id }}" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
                            @if ($editingCommentId === $comment->id)
                                <div class="space-y-space-sm">
                                    <x-rich-text-editor id="edit-comment-{{ $comment->id }}" wire-model="editCommentBody" :value="$editCommentBody" />
                                    @error('editCommentBody') <p class="text-body-xs text-error">{{ $message }}</p> @enderror
                                    <div class="flex gap-space-sm">
                                        <button wire:click="cancelEditComment" type="button" class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition">Cancel</button>
                                        <button wire:click="updateComment" wire:loading.attr="disabled" wire:target="updateComment" type="button" class="px-space-md py-space-xs bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50">
                                            <span wire:loading.remove wire:target="updateComment">Save</span>
                                            <span wire:loading wire:target="updateComment">Saving…</span>
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-start justify-between gap-space-md">
                                    <p class="text-body-xs text-on-surface-variant">
                                        {{ $comment->user->name }} &middot; {{ $comment->created_at_display->format('d M Y, H:i') }}
                                    </p>

                                    <div class="flex items-center gap-space-xs flex-shrink-0">
                                        @if ($comment->user_id === auth()->id() || $canModerate)
                                            <button wire:click="startEditComment('{{ $comment->id }}')" type="button" class="text-on-surface-variant hover:text-primary transition">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </button>
                                            <button
                                                @click="deleteCommentId = @js($comment->id); showDeleteCommentModal = true"
                                                type="button"
                                                class="text-on-surface-variant hover:text-error transition"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="text-body-md text-on-surface">{!! $comment->body !!}</div>

                                <div class="flex items-center gap-space-lg">
                                    @if ($canCreate)
                                        <button
                                            x-data="{ liked: @js($likedCommentIds->contains($comment->id)), count: {{ $comment->likes_count }} }"
                                            @click="
                                                const next = !liked; const delta = next ? 1 : -1;
                                                liked = next; count += delta;
                                                fetch('{{ route('forum.comment.toggle-like', $comment->id) }}', {
                                                    method: 'POST',
                                                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                                }).catch(() => { liked = !next; count -= delta; });
                                            "
                                            type="button"
                                            class="inline-flex items-center gap-1 text-body-xs transition"
                                            :class="liked ? 'text-primary' : 'text-on-surface-variant hover:text-primary'"
                                        >
                                            <span class="material-symbols-outlined text-[16px]" :data-weight="liked ? 'fill' : ''">thumb_up</span>
                                            <span x-text="count"></span>
                                        </button>

                                        @if ($comment->parent_id === null)
                                            <button wire:click="startReply('{{ $comment->id }}')" type="button" class="text-body-xs text-on-surface-variant hover:text-primary transition">
                                                Reply
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            @endif

                            @if ($replyingToCommentId === $comment->id)
                                <div class="ml-space-lg space-y-space-sm border-l-2 border-outline-variant pl-space-md">
                                    <x-rich-text-editor id="reply-{{ $comment->id }}" wire-model="newReplyBody" :value="$newReplyBody" />
                                    @error('newReplyBody') <p class="text-body-xs text-error">{{ $message }}</p> @enderror
                                    <div class="flex gap-space-sm">
                                        <button wire:click="cancelReply" type="button" class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition">Cancel</button>
                                        <button wire:click="addReply" wire:loading.attr="disabled" wire:target="addReply" type="button" class="px-space-md py-space-xs bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50">
                                            <span wire:loading.remove wire:target="addReply">Reply</span>
                                            <span wire:loading wire:target="addReply">Posting…</span>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @if ($comment->replies->isNotEmpty())
                                <div class="ml-space-lg space-y-space-sm border-l-2 border-outline-variant pl-space-md">
                                    @foreach ($comment->replies as $reply)
                                        <div wire:key="reply-{{ $reply->id }}" class="space-y-space-xs">
                                            @if ($editingCommentId === $reply->id)
                                                <div class="space-y-space-sm">
                                                    <x-rich-text-editor id="edit-reply-{{ $reply->id }}" wire-model="editCommentBody" :value="$editCommentBody" />
                                                    @error('editCommentBody') <p class="text-body-xs text-error">{{ $message }}</p> @enderror
                                                    <div class="flex gap-space-sm">
                                                        <button wire:click="cancelEditComment" type="button" class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition">Cancel</button>
                                                        <button wire:click="updateComment" wire:loading.attr="disabled" wire:target="updateComment" type="button" class="px-space-md py-space-xs bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50">
                                                            <span wire:loading.remove wire:target="updateComment">Save</span>
                                                            <span wire:loading wire:target="updateComment">Saving…</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="flex items-start justify-between gap-space-md">
                                                    <p class="text-body-xs text-on-surface-variant">
                                                        {{ $reply->user->name }} &middot; {{ $reply->created_at_display->format('d M Y, H:i') }}
                                                    </p>

                                                    <div class="flex items-center gap-space-xs flex-shrink-0">
                                                        @if ($reply->user_id === auth()->id() || $canModerate)
                                                            <button wire:click="startEditComment('{{ $reply->id }}')" type="button" class="text-on-surface-variant hover:text-primary transition">
                                                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                                            </button>
                                                            <button
                                                                @click="deleteCommentId = @js($reply->id); showDeleteCommentModal = true"
                                                                type="button"
                                                                class="text-on-surface-variant hover:text-error transition"
                                                            >
                                                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="text-body-sm text-on-surface">{!! $reply->body !!}</div>

                                                @if ($canCreate)
                                                    <button
                                                        x-data="{ liked: @js($likedCommentIds->contains($reply->id)), count: {{ $reply->likes_count }} }"
                                                        @click="
                                                            const next = !liked; const delta = next ? 1 : -1;
                                                            liked = next; count += delta;
                                                            fetch('{{ route('forum.comment.toggle-like', $reply->id) }}', {
                                                                method: 'POST',
                                                                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                                            }).catch(() => { liked = !next; count -= delta; });
                                                        "
                                                        type="button"
                                                        class="inline-flex items-center gap-1 text-body-xs transition"
                                                        :class="liked ? 'text-primary' : 'text-on-surface-variant hover:text-primary'"
                                                    >
                                                        <span class="material-symbols-outlined text-[16px]" :data-weight="liked ? 'fill' : ''">thumb_up</span>
                                                        <span x-text="count"></span>
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    <!-- Delete Comment Modal -->
    <div x-show="showDeleteCommentModal" x-cloak class="fixed inset-0 z-50">
        <div @click="showDeleteCommentModal = false" class="fixed inset-0 bg-black bg-opacity-50"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-sm w-full p-space-lg space-y-space-lg">
                <p class="font-body-sm text-body-sm text-on-surface-variant text-center">Delete this comment? This action cannot be undone.</p>
                <div class="flex gap-space-md">
                    <button @click="showDeleteCommentModal = false" type="button" class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">Cancel</button>
                    <button @click="showDeleteCommentModal = false; $wire.call('deleteComment', deleteCommentId)" type="button" class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md transition-opacity">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Thread Modal -->
    <div x-show="showDeleteThreadModal" x-cloak class="fixed inset-0 z-50">
        <div @click="showDeleteThreadModal = false" class="fixed inset-0 bg-black bg-opacity-50"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-sm w-full p-space-lg space-y-space-lg">
                <p class="font-body-sm text-body-sm text-on-surface-variant text-center">Delete this thread and all its comments? This action cannot be undone.</p>
                <div class="flex gap-space-md">
                    <button @click="showDeleteThreadModal = false" type="button" class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">Cancel</button>
                    <button @click="showDeleteThreadModal = false; $wire.call('deleteThread')" type="button" class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md transition-opacity">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
