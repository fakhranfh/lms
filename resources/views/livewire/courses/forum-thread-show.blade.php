@section('title', $thread->title)

<div class="space-y-space-lg" x-data="{ deleteCommentId: null, showDeleteCommentModal: false, showDeleteThreadModal: false, editingCommentId: null, replyingCommentId: null }" x-on:comment-updated.window="editingCommentId = null" x-on:reply-added.window="replyingCommentId = null">
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
        <h2 class="font-headline-sm text-headline-sm text-on-surface">Comments</h2>

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
            <div class="w-full space-y-space-md animate-pulse">
                @for ($i = 0; $i < 3; $i++)
                    <div class="w-full bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
                        <div class="flex items-start justify-between gap-space-md">
                            <div class="h-3 bg-surface-container rounded w-1/3"></div>
                            <div class="h-4 w-10 bg-surface-container rounded"></div>
                        </div>
                        <div class="h-4 bg-surface-container rounded w-full"></div>
                        <div class="h-4 bg-surface-container rounded w-2/3"></div>
                        <div class="h-3 w-16 bg-surface-container rounded"></div>
                    </div>
                @endfor
            </div>
        @else
            @if ($pagination && $pagination['total'] > 0)
                <div class="flex flex-wrap items-center justify-end gap-space-md pb-space-sm border-b border-outline-variant">
                    <div class="flex items-center gap-space-lg">
                        <label class="flex items-center gap-space-sm">
                            <span class="text-body-sm text-on-surface-variant">Sort by:</span>
                            <select wire:model.live="sortBy" class="h-[36px] px-2 rounded-lg border border-outline-variant bg-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                                @foreach ($sortOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="flex items-center gap-space-sm">
                            <span class="text-body-sm text-on-surface-variant">Show:</span>
                            <select wire:model.live="perPage" class="h-[36px] px-2 rounded-lg border border-outline-variant bg-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </label>

                        @if ($pagination['lastPage'] > 1)
                            <div class="flex items-center gap-space-xs">
                                <button wire:click="gotoPage(1)" type="button" class="w-8 h-8 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container disabled:opacity-40" @disabled($pagination['onFirstPage'])>«</button>
                                <button wire:click="gotoPage({{ $pagination['currentPage'] - 1 }})" type="button" class="w-8 h-8 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container disabled:opacity-40" @disabled($pagination['onFirstPage'])>‹</button>
                                <button wire:click="gotoPage({{ $pagination['currentPage'] + 1 }})" type="button" class="w-8 h-8 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container disabled:opacity-40" @disabled(! $pagination['hasMorePages'])>›</button>
                                <button wire:click="gotoPage({{ $pagination['lastPage'] }})" type="button" class="w-8 h-8 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container disabled:opacity-40" @disabled(! $pagination['hasMorePages'])>»</button>
                            </div>

                            <label class="flex items-center gap-space-sm">
                                <span class="text-body-sm text-on-surface-variant">Page:</span>
                                <select wire:model.live="page" class="h-[36px] px-2 rounded-lg border border-outline-variant bg-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    @for ($i = 1; $i <= $pagination['lastPage']; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </label>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Comment list skeleton while changing per-page or page -->
            <div wire:loading wire:target="gotoPage, perPage, page, sortBy" class="w-full space-y-space-md animate-pulse">
                @for ($i = 0; $i < 3; $i++)
                    <div class="w-full bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
                        <div class="flex items-start justify-between gap-space-md">
                            <div class="h-3 bg-surface-container rounded w-1/3"></div>
                            <div class="h-4 w-10 bg-surface-container rounded"></div>
                        </div>
                        <div class="h-4 bg-surface-container rounded w-full"></div>
                        <div class="h-4 bg-surface-container rounded w-2/3"></div>
                        <div class="h-3 w-16 bg-surface-container rounded"></div>
                    </div>
                @endfor
            </div>

            <div wire:loading.remove wire:target="gotoPage, perPage, page, sortBy">
            @if ($comments->isEmpty())
                <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center">
                    <p class="text-body-md text-on-surface-variant">No comments yet.</p>
                </div>
            @else
                <div class="space-y-space-md">
                    @foreach ($comments as $comment)
                        <div wire:key="comment-{{ $comment->id }}" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-sm">
                            <div wire:loading wire:target="deleteComment('{{ $comment->id }}')" class="w-full space-y-space-sm animate-pulse">
                                <div class="flex items-start justify-between gap-space-md">
                                    <div class="h-3 bg-surface-container rounded w-1/3"></div>
                                    <div class="flex items-center gap-space-xs">
                                        <div class="h-[18px] w-[18px] bg-surface-container rounded"></div>
                                        <div class="h-[18px] w-[18px] bg-surface-container rounded"></div>
                                    </div>
                                </div>
                                <div class="h-4 bg-surface-container rounded w-full"></div>
                                <div class="h-4 bg-surface-container rounded w-2/3"></div>
                                <div class="flex items-center gap-space-lg">
                                    <div class="h-3 w-10 bg-surface-container rounded"></div>
                                    <div class="h-3 w-12 bg-surface-container rounded"></div>
                                </div>
                            </div>
                            <div wire:loading.remove wire:target="deleteComment('{{ $comment->id }}')" class="space-y-space-sm">
                            <div x-show="editingCommentId === '{{ $comment->id }}'" x-cloak class="space-y-space-sm">
                                <x-rich-text-editor id="edit-comment-{{ $comment->id }}" wire-model="editCommentBody" :value="$comment->body" />
                                @error('editCommentBody') <p class="text-body-xs text-error">{{ $message }}</p> @enderror
                                <div class="flex gap-space-sm">
                                    <button @click="editingCommentId = null" type="button" class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition">Cancel</button>
                                    <button wire:click="updateComment('{{ $comment->id }}')" wire:loading.attr="disabled" wire:target="updateComment('{{ $comment->id }}')" type="button" class="px-space-md py-space-xs bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50">
                                        <span wire:loading.remove wire:target="updateComment('{{ $comment->id }}')">Save</span>
                                        <span wire:loading wire:target="updateComment('{{ $comment->id }}')">Saving…</span>
                                    </button>
                                </div>
                            </div>
                            <div x-show="editingCommentId !== '{{ $comment->id }}'">
                                <div class="flex items-start justify-between gap-space-md">
                                    <p class="text-body-xs text-on-surface-variant">
                                        {{ $comment->user->name }} &middot; {{ $comment->created_at_display->format('d M Y, H:i') }}
                                    </p>

                                    <div class="flex items-center gap-space-xs flex-shrink-0">
                                        @if ($comment->user_id === auth()->id() || $canModerate)
                                            <button @click="editingCommentId = '{{ $comment->id }}'" type="button" class="text-on-surface-variant hover:text-primary transition">
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

                                <div class="text-body-md text-on-surface mt-space-xs mb-space-sm">{!! $comment->body !!}</div>

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
                                            <button @click="replyingCommentId = '{{ $comment->id }}'" type="button" class="text-body-xs text-on-surface-variant hover:text-primary transition">
                                                Reply
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            @if ($comment->parent_id === null)
                                <div x-show="replyingCommentId === '{{ $comment->id }}'" x-cloak class="ml-space-lg space-y-space-sm border-l-2 border-outline-variant pl-space-md">
                                    <x-rich-text-editor id="reply-{{ $comment->id }}" wire-model="newReplyBody" :value="''" />
                                    @error('newReplyBody') <p class="text-body-xs text-error">{{ $message }}</p> @enderror
                                    <div class="flex gap-space-sm">
                                        <button @click="replyingCommentId = null" type="button" class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition">Cancel</button>
                                        <button wire:click="addReply('{{ $comment->id }}')" wire:loading.attr="disabled" wire:target="addReply('{{ $comment->id }}')" type="button" class="px-space-md py-space-xs bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50">
                                            <span wire:loading.remove wire:target="addReply('{{ $comment->id }}')">Reply</span>
                                            <span wire:loading wire:target="addReply('{{ $comment->id }}')">Posting…</span>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @if ($comment->replies->isNotEmpty())
                                <div class="ml-space-lg space-y-space-sm border-l-2 border-outline-variant pl-space-md">
                                    @foreach ($comment->replies as $reply)
                                        <div wire:key="reply-{{ $reply->id }}" class="space-y-space-xs">
                                            <div wire:loading wire:target="deleteComment('{{ $reply->id }}')" class="w-full space-y-space-xs animate-pulse">
                                                <div class="flex items-start justify-between gap-space-md">
                                                    <div class="h-3 bg-surface-container rounded w-1/3"></div>
                                                    <div class="flex items-center gap-space-xs">
                                                        <div class="h-4 w-4 bg-surface-container rounded"></div>
                                                        <div class="h-4 w-4 bg-surface-container rounded"></div>
                                                    </div>
                                                </div>
                                                <div class="h-4 bg-surface-container rounded w-full"></div>
                                                <div class="h-3 w-10 bg-surface-container rounded"></div>
                                            </div>
                                            <div wire:loading.remove wire:target="deleteComment('{{ $reply->id }}')" class="space-y-space-xs">
                                            <div x-show="editingCommentId === '{{ $reply->id }}'" x-cloak class="space-y-space-sm">
                                                <x-rich-text-editor id="edit-reply-{{ $reply->id }}" wire-model="editCommentBody" :value="$reply->body" />
                                                @error('editCommentBody') <p class="text-body-xs text-error">{{ $message }}</p> @enderror
                                                <div class="flex gap-space-sm">
                                                    <button @click="editingCommentId = null" type="button" class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition">Cancel</button>
                                                    <button wire:click="updateComment('{{ $reply->id }}')" wire:loading.attr="disabled" wire:target="updateComment('{{ $reply->id }}')" type="button" class="px-space-md py-space-xs bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50">
                                                        <span wire:loading.remove wire:target="updateComment('{{ $reply->id }}')">Save</span>
                                                        <span wire:loading wire:target="updateComment('{{ $reply->id }}')">Saving…</span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div x-show="editingCommentId !== '{{ $reply->id }}'">
                                                <div class="flex items-start justify-between gap-space-md">
                                                    <p class="text-body-xs text-on-surface-variant">
                                                        {{ $reply->user->name }} &middot; {{ $reply->created_at_display->format('d M Y, H:i') }}
                                                    </p>

                                                    <div class="flex items-center gap-space-xs flex-shrink-0">
                                                        @if ($reply->user_id === auth()->id() || $canModerate)
                                                            <button @click="editingCommentId = '{{ $reply->id }}'" type="button" class="text-on-surface-variant hover:text-primary transition">
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

                                                <div class="text-body-sm text-on-surface mt-space-xs mb-space-sm">{!! $reply->body !!}</div>

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
                                            </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            </div>
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
