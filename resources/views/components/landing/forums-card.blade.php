@php
    $sessions = [
        [
            'label' => 'Session 1',
            'title' => 'Introduction & Course Overview',
            'start' => '16 Sep 2026, 00:00',
            'end' => '22 Sep 2026, 23:59',
            'unread' => 1,
            'threads' => [
                [
                    'name' => 'Demo Student', 'role' => 'Student', 'time' => '23 Sep 2026, 11:13',
                    'title' => "Question about this week's material #1", 'unread' => true,
                    'description' => "Can someone explain how the grading policy weighs quizzes versus the final exam? I want to make sure I'm prioritizing my study time correctly.",
                    'comments' => [
                        ['name' => 'Demo Teacher', 'role' => 'Instructor', 'time' => '23 Sep 2026, 12:02', 'body' => 'Quizzes are 15% total and the final exam is 30% — check the syllabus rubric for the full breakdown.', 'likes' => 3, 'liked' => false],
                        ['name' => 'Demo Student', 'role' => 'Student', 'time' => '23 Sep 2026, 12:20', 'body' => 'Thanks, that clears it up!', 'likes' => 1, 'liked' => false],
                    ],
                ],
                [
                    'name' => 'Demo Student', 'role' => 'Student', 'time' => '23 Sep 2026, 11:13',
                    'title' => 'Group assignment discussion #2', 'unread' => false,
                    'description' => "Looking for two more people to team up with for the practice set due next week.",
                    'comments' => [
                        ['name' => 'Demo Student', 'role' => 'Student', 'time' => '23 Sep 2026, 13:45', 'body' => "I'm in! I'll message you.", 'likes' => 0, 'liked' => false],
                    ],
                ],
            ],
        ],
        [
            'label' => 'Session 2',
            'title' => 'Linear Equations',
            'start' => '23 Sep 2026, 00:00',
            'end' => '29 Sep 2026, 23:59',
            'unread' => 0,
            'threads' => [
                [
                    'name' => 'Demo Student', 'role' => 'Student', 'time' => '24 Sep 2026, 09:40',
                    'title' => 'Difficulty understanding a basic concept #3', 'unread' => false,
                    'description' => "I keep mixing up slope-intercept and standard form. Any tips for converting between them quickly?",
                    'comments' => [],
                ],
            ],
        ],
        [
            'label' => 'Session 3',
            'title' => 'Quadratic Functions',
            'start' => '30 Sep 2026, 00:00',
            'end' => '6 Oct 2026, 23:59',
            'unread' => 0,
            'threads' => [],
        ],
    ];
@endphp

<div class="reveal flex flex-col gap-space-lg rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-6" style="animation-delay: 0.26s">
    <div>
        <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Discussion forums</h3>
        <p class="mt-space-xs font-body-sm text-body-sm text-[--lp-muted]">Threaded course discussions with read tracking and participation scoring, so forum activity can count toward a grade.</p>
    </div>

    <div
        class="overflow-hidden rounded-lg border border-[--lp-outline] bg-[--lp-surface]"
        x-data="{
            active: 0,
            sessions: @js($sessions),
            showForm: false,
            newTitle: '',
            viewingThread: null,
            newComment: '',
            createThread() {
                if (! this.newTitle.trim()) { return; }
                this.sessions[this.active].threads.unshift({
                    name: 'Demo Student', role: 'Student', time: 'Just now',
                    title: this.newTitle.trim(), unread: false,
                    description: '', comments: [],
                });
                this.newTitle = '';
                this.showForm = false;
            },
            removeThread(index) { this.sessions[this.active].threads.splice(index, 1); },
            openThread(index) { this.viewingThread = index; this.sessions[this.active].threads[index].unread = false; },
            backToForum() { this.viewingThread = null; this.newComment = ''; },
            get thread() { return this.viewingThread !== null ? this.sessions[this.active].threads[this.viewingThread] : null; },
            toggleLike(comment) { comment.liked = ! comment.liked; comment.likes += comment.liked ? 1 : -1; },
            removeComment(cIndex) { this.thread.comments.splice(cIndex, 1); },
            postComment() {
                if (! this.newComment.trim()) { return; }
                this.thread.comments.unshift({ name: 'Demo Student', role: 'Student', time: 'Just now', body: this.newComment.trim(), likes: 0, liked: false });
                this.newComment = '';
            },
        }"
    >
        <div class="flex gap-space-xs overflow-x-auto border-b border-[--lp-outline] px-space-sm pt-space-sm">
            <template x-for="(session, index) in sessions" :key="index">
                <button
                    type="button"
                    @click="active = index; showForm = false; viewingThread = null"
                    class="relative flex-shrink-0 rounded-t-lg border-b-2 px-space-sm py-space-xs text-left transition-colors"
                    :class="active === index ? 'border-[--lp-primary] bg-[--lp-primary] text-[--lp-on-primary]' : 'border-transparent text-[--lp-muted] hover:text-[--lp-ink]'"
                >
                    <span class="block font-label-sm text-label-sm" x-text="session.label"></span>
                    <span
                        class="block font-label-sm text-label-sm"
                        :class="active === index ? 'text-[--lp-on-primary]/80' : 'text-error'"
                        x-show="session.unread > 0"
                        x-text="session.unread + ' unread'"
                    ></span>
                </button>
            </template>
        </div>

        <template x-for="(session, sIndex) in sessions" :key="sIndex">
            <div x-show="active === sIndex" x-cloak>
                <!-- Thread list -->
                <div x-show="viewingThread === null" class="space-y-space-md p-space-lg">
                    <div>
                        <h4 class="font-headline-sm text-headline-sm text-[--lp-ink]" x-text="session.title"></h4>
                        <div class="mt-space-xs flex flex-wrap gap-space-xl">
                            <div>
                                <p class="font-label-sm text-label-sm text-[--lp-muted]">Start</p>
                                <p class="font-body-sm text-body-sm text-[--lp-ink]" x-text="session.start"></p>
                            </div>
                            <div>
                                <p class="font-label-sm text-label-sm text-[--lp-muted]">End</p>
                                <p class="font-body-sm text-body-sm text-[--lp-ink]" x-text="session.end"></p>
                            </div>
                            <div>
                                <p class="font-label-sm text-label-sm text-[--lp-muted]">Total Post</p>
                                <p class="font-body-sm text-body-sm text-[--lp-ink]" x-text="session.threads.length"></p>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="showForm = true"
                        x-show="! showForm"
                        class="rounded-lg bg-[--lp-primary] px-space-lg py-space-sm font-label-md text-label-md text-[--lp-on-primary]"
                    >
                        Create New Thread
                    </button>

                    <div x-show="showForm" x-cloak class="space-y-space-sm rounded-lg border border-[--lp-outline] bg-[--lp-bg] p-space-md">
                        <input
                            type="text"
                            x-model="newTitle"
                            placeholder="Thread title"
                            class="w-full rounded-lg border border-[--lp-outline] bg-[--lp-surface] px-space-md py-space-sm font-body-sm text-body-sm text-[--lp-ink] focus:outline-none"
                        />
                        <div class="flex gap-space-md">
                            <button type="button" @click="showForm = false; newTitle = ''" class="rounded-lg border border-[--lp-outline] px-space-lg py-space-xs font-label-sm text-label-sm text-[--lp-ink]">Cancel</button>
                            <button type="button" @click="createThread()" class="rounded-lg bg-[--lp-primary] px-space-lg py-space-xs font-label-sm text-label-sm text-[--lp-on-primary]">Post Thread</button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-[--lp-outline] pt-space-sm">
                        <p class="font-body-sm text-body-sm text-[--lp-muted]"><span x-text="session.threads.length"></span> Result<span x-show="session.threads.length !== 1">s</span></p>
                        <label class="flex items-center gap-space-xs font-body-sm text-body-sm text-[--lp-muted]">
                            Show:
                            <span class="rounded border border-[--lp-outline] px-space-xs py-space-xxs font-body-sm text-body-sm text-[--lp-ink]">10</span>
                        </label>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-[--lp-outline]">
                        <template x-if="session.threads.length === 0">
                            <p class="p-space-lg text-center font-body-sm text-body-sm text-[--lp-muted]">No threads yet.</p>
                        </template>

                        <template x-for="(thread, tIndex) in session.threads" :key="tIndex">
                            <div class="flex items-start gap-space-md border-t border-[--lp-outline] p-space-md first:border-t-0 hover:bg-[--lp-bg] transition-colors">
                                <button type="button" @click="openThread(tIndex)" class="flex min-w-0 flex-1 items-start gap-space-md text-left">
                                    <div class="relative shrink-0">
                                        <span x-show="thread.unread" x-cloak class="absolute -left-0.5 -top-0.5 z-10 h-2.5 w-2.5 rounded-full border-2 border-[--lp-surface] bg-error"></span>
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[--lp-primary] font-label-md text-label-md text-[--lp-on-primary]">D</div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-body-sm text-body-sm text-[--lp-ink]">
                                            <span class="font-medium" x-text="thread.name"></span>
                                            <span class="text-[--lp-muted]">&middot;</span>
                                            <span class="text-[--lp-primary]" x-text="thread.role"></span>
                                        </p>
                                        <p class="font-label-sm text-label-sm text-[--lp-muted]" x-text="thread.time"></p>
                                        <p class="mt-space-xxs font-body-sm text-body-sm text-[--lp-ink]" :class="thread.unread ? 'font-semibold' : 'font-normal'" x-text="thread.title"></p>
                                    </div>
                                </button>
                                <div class="flex shrink-0 items-center gap-space-sm">
                                    <span class="flex items-center gap-space-xxs font-label-sm text-label-sm text-[--lp-muted]">
                                        <span aria-hidden="true">&#128172;</span>
                                        <span x-text="thread.comments.length"></span>
                                    </span>
                                    <button type="button" @click="removeThread(tIndex)" class="text-[--lp-muted] hover:text-error" title="Delete thread" aria-label="Delete thread">
                                        <span aria-hidden="true">&#128465;</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Thread detail -->
                <div x-show="viewingThread !== null && thread" x-cloak class="space-y-space-md p-space-lg">
                    <button type="button" @click="backToForum()" class="flex items-center gap-space-xs font-label-sm text-label-sm text-[--lp-muted] hover:text-[--lp-ink]">
                        <span aria-hidden="true">&larr;</span>
                        Back to Forum
                    </button>

                    <template x-if="thread">
                        <div class="space-y-space-md">
                            <div class="rounded-lg border border-[--lp-outline] bg-[--lp-bg] p-space-md">
                                <div class="flex items-center gap-space-sm border-b border-[--lp-outline] pb-space-sm">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[--lp-primary] font-label-md text-label-md text-[--lp-on-primary]">D</div>
                                    <div>
                                        <p class="font-body-sm text-body-sm text-[--lp-ink]">
                                            <span class="font-medium" x-text="thread.name"></span>
                                            <span class="text-[--lp-muted]">&middot;</span>
                                            <span class="text-[--lp-primary]" x-text="thread.role"></span>
                                        </p>
                                        <p class="font-label-sm text-label-sm text-[--lp-muted]" x-text="thread.time"></p>
                                    </div>
                                </div>
                                <h4 class="mt-space-sm font-headline-sm text-headline-sm text-[--lp-ink]" x-text="thread.title"></h4>
                                <p class="mt-space-xs font-body-sm text-body-sm text-[--lp-muted]" x-text="thread.description"></p>
                            </div>

                            <p class="font-label-sm text-label-sm text-[--lp-ink]">Comments (<span x-text="thread.comments.length"></span>)</p>

                            <div class="space-y-space-sm rounded-lg border border-[--lp-outline] bg-[--lp-bg] p-space-md">
                                <textarea
                                    x-model="newComment"
                                    rows="2"
                                    placeholder="Write a comment…"
                                    class="w-full resize-none rounded-lg border border-[--lp-outline] bg-[--lp-surface] px-space-md py-space-sm font-body-sm text-body-sm text-[--lp-ink] focus:outline-none"
                                ></textarea>
                                <button type="button" @click="postComment()" class="rounded-lg bg-[--lp-primary] px-space-lg py-space-xs font-label-sm text-label-sm text-[--lp-on-primary]">Post Comment</button>
                            </div>

                            <template x-if="thread.comments.length === 0">
                                <p class="rounded-lg border border-[--lp-outline] p-space-lg text-center font-body-sm text-body-sm text-[--lp-muted]">No comments yet.</p>
                            </template>

                            <div class="space-y-space-sm">
                                <template x-for="(comment, cIndex) in thread.comments" :key="cIndex">
                                    <div class="rounded-lg border border-[--lp-outline] p-space-md">
                                        <div class="flex items-start justify-between gap-space-md">
                                            <div class="flex items-center gap-space-sm">
                                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[--lp-primary] font-label-sm text-label-sm text-[--lp-on-primary]" x-text="comment.name.charAt(0)"></div>
                                                <div>
                                                    <p class="font-body-sm text-body-sm text-[--lp-ink]">
                                                        <span class="font-medium" x-text="comment.name"></span>
                                                        <span class="text-[--lp-muted]">&middot;</span>
                                                        <span class="text-[--lp-primary]" x-text="comment.role"></span>
                                                    </p>
                                                    <p class="font-label-sm text-label-sm text-[--lp-muted]" x-text="comment.time"></p>
                                                </div>
                                            </div>
                                            <button type="button" @click="removeComment(cIndex)" class="shrink-0 text-[--lp-muted] hover:text-error" title="Delete comment" aria-label="Delete comment">
                                                <span aria-hidden="true">&#128465;</span>
                                            </button>
                                        </div>
                                        <p class="mt-space-sm font-body-sm text-body-sm text-[--lp-ink]" x-text="comment.body"></p>
                                        <button
                                            type="button"
                                            @click="toggleLike(comment)"
                                            class="mt-space-sm flex items-center gap-space-xxs font-label-sm text-label-sm transition-colors"
                                            :class="comment.liked ? 'text-[--lp-primary]' : 'text-[--lp-muted] hover:text-[--lp-primary]'"
                                        >
                                            <span aria-hidden="true">&#128077;</span>
                                            <span x-text="comment.likes"></span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>
