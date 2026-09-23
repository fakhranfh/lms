@php
    $sessions = [
        [
            'label' => 'Session 1',
            'title' => 'Introduction & Course Overview',
            'outcome' => 'Understand the course structure, expectations, and grading policy.',
            'subtopics' => ['Course syllabus walkthrough', 'Learning outcomes overview', 'Grading and evaluation policy'],
            'start' => '16 Sep 2026, 00:00',
            'end' => '22 Sep 2026, 23:59',
            'delivery' => 'Virtual Class',
        ],
        [
            'label' => 'Session 2',
            'title' => 'Linear Equations',
            'outcome' => 'Solve and graph linear equations in one and two variables.',
            'subtopics' => ['Slope-intercept form', 'Graphing lines', 'Word problems'],
            'start' => '23 Sep 2026, 00:00',
            'end' => '29 Sep 2026, 23:59',
            'delivery' => 'In-Person',
        ],
        [
            'label' => 'Session 3',
            'title' => 'Quadratic Functions',
            'outcome' => 'Analyze quadratic functions and interpret their graphs.',
            'subtopics' => ['Factoring', 'Vertex form', 'Real-world applications'],
            'start' => '30 Sep 2026, 00:00',
            'end' => '6 Oct 2026, 23:59',
            'delivery' => 'Virtual Class',
        ],
    ];
@endphp

<div class="reveal flex flex-col justify-between gap-space-lg rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-6" style="animation-delay: 0.02s">
    <div>
        <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Course builder</h3>
        <p class="mt-space-xs font-body-sm text-body-sm text-[--lp-muted]">Structure courses into sessions, define learning outcomes and rubrics, and set class policies &mdash; then publish when ready.</p>
    </div>

    <div class="overflow-hidden rounded-lg border border-[--lp-outline] bg-[--lp-surface]" x-data="{ active: 0, chip: 'material', sessions: @js($sessions) }">
        <div class="flex gap-space-xs overflow-x-auto border-b border-[--lp-outline] px-space-sm pt-space-sm">
            <template x-for="(session, index) in sessions" :key="index">
                <button
                    type="button"
                    @click="active = index"
                    class="flex-shrink-0 rounded-t-lg border-b-2 px-space-sm py-space-xs font-label-sm text-label-sm transition-colors"
                    :class="active === index ? 'border-[--lp-primary] bg-[--lp-primary] text-[--lp-on-primary]' : 'border-transparent text-[--lp-muted] hover:text-[--lp-ink]'"
                    x-text="session.label"
                ></button>
            </template>
        </div>

        <div class="relative px-space-lg py-space-lg">
            <template x-for="(session, index) in sessions" :key="index">
                <div x-show="active === index" x-cloak>
                    <div>
                        <p class="font-label-sm text-label-sm text-[--lp-muted]" x-text="session.label"></p>
                        <h4 class="mt-space-xxs font-headline-sm text-headline-sm text-[--lp-ink]" x-text="session.title"></h4>
                    </div>

                    <div class="mt-space-md">
                        <p class="font-label-sm text-label-sm text-[--lp-ink]">Learning Outcome</p>
                        <ul class="mt-space-xs space-y-space-xxs">
                            <li class="flex items-start gap-space-xs font-body-sm text-body-sm text-[--lp-primary]">
                                <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[--lp-primary]"></span>
                                <span x-text="session.outcome"></span>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-space-md">
                        <p class="font-label-sm text-label-sm text-[--lp-ink]">Sub Topic</p>
                        <ul class="mt-space-xs space-y-space-xxs">
                            <template x-for="subtopic in session.subtopics" :key="subtopic">
                                <li class="flex items-start gap-space-xs font-body-sm text-body-sm text-[--lp-primary]">
                                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-[--lp-primary]"></span>
                                    <span x-text="subtopic"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="mt-space-lg grid grid-cols-3 gap-space-md border-t border-[--lp-outline] pt-space-md">
                        <div>
                            <p class="font-label-sm text-label-sm uppercase text-[--lp-muted]">Start</p>
                            <p class="mt-space-xxs font-body-sm text-body-sm text-[--lp-ink]" x-text="session.start"></p>
                        </div>
                        <div>
                            <p class="font-label-sm text-label-sm uppercase text-[--lp-muted]">End</p>
                            <p class="mt-space-xxs font-body-sm text-body-sm text-[--lp-ink]" x-text="session.end"></p>
                        </div>
                        <div>
                            <p class="font-label-sm text-label-sm uppercase text-[--lp-muted]">Delivery Mode</p>
                            <p class="mt-space-xxs font-body-sm text-body-sm text-[--lp-ink]" x-text="session.delivery"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="border-t border-[--lp-outline] px-space-lg py-space-lg">
            <div class="flex items-center gap-space-xs">
                <p class="font-label-sm text-label-sm text-[--lp-ink]">Learning Progress</p>
                <span class="flex h-4 w-4 items-center justify-center rounded-full border border-[--lp-outline] font-label-sm text-label-sm text-[--lp-muted]" aria-hidden="true">i</span>
                <span class="ml-auto font-label-sm text-label-sm text-[--lp-ink]">0%</span>
            </div>

            <div class="mt-space-sm h-2 w-full overflow-hidden rounded-full bg-[--lp-outline]"></div>

            <div class="mt-space-md flex flex-wrap gap-space-sm">
                <button
                    type="button"
                    @click="chip = 'material'"
                    class="rounded-full border px-space-sm py-space-xxs font-label-sm text-label-sm transition-colors"
                    :class="chip === 'material' ? 'border-[--lp-primary] text-[--lp-ink]' : 'border-[--lp-outline] text-[--lp-muted] hover:text-[--lp-ink]'"
                    x-text="sessions[active].title + ' - Reading Material'"
                ></button>
                <button
                    type="button"
                    @click="chip = 'assessment'"
                    class="rounded-full border px-space-sm py-space-xxs font-label-sm text-label-sm transition-colors"
                    :class="chip === 'assessment' ? 'border-[--lp-primary] text-[--lp-ink]' : 'border-[--lp-outline] text-[--lp-muted] hover:text-[--lp-ink]'"
                >Assessment</button>
                <button
                    type="button"
                    @click="chip = 'forum'"
                    class="rounded-full border px-space-sm py-space-xxs font-label-sm text-label-sm transition-colors"
                    :class="chip === 'forum' ? 'border-[--lp-primary] text-[--lp-ink]' : 'border-[--lp-outline] text-[--lp-muted] hover:text-[--lp-ink]'"
                >Forum</button>
            </div>

            <div x-show="chip === 'material'" x-cloak class="border-t border-[--lp-outline] pt-space-md">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-space-xs font-label-sm text-label-sm text-[--lp-muted]">
                        <span aria-hidden="true">&#128196;</span>
                        PDF
                    </span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border border-[--lp-outline] text-[--lp-muted]" aria-hidden="true">&darr;</span>
                </div>

                <div class="mt-space-lg flex flex-col items-center gap-space-md py-space-md">
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-[--lp-primary]/10">
                        <span class="material-symbols-outlined text-[--lp-primary] text-[40px]">school</span>
                    </div>
                    <button type="button" class="rounded-lg bg-[--lp-primary] px-space-xl py-space-sm font-label-md text-label-md text-[--lp-on-primary]">
                        Start Learning
                    </button>
                </div>
            </div>

            <div x-show="chip === 'assessment'" x-cloak class="border-t border-[--lp-outline] pt-space-md">
                <div class="overflow-hidden rounded-lg border border-[--lp-outline]">
                    <div class="flex items-center justify-between bg-[--lp-bg] px-space-md py-space-sm">
                        <span class="flex items-center gap-space-xs font-label-sm text-label-sm uppercase text-[--lp-ink]">
                            <span aria-hidden="true">&lsaquo;</span>
                            Quiz: 15%
                        </span>
                        <span class="font-label-sm text-label-sm text-[--lp-muted]">1 assessment</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-t border-[--lp-outline] bg-[--lp-bg]/60">
                                    <th class="px-space-md py-space-sm font-label-sm text-label-sm text-[--lp-muted]">Title</th>
                                    <th class="px-space-md py-space-sm font-label-sm text-label-sm text-[--lp-muted]">Assigned to</th>
                                    <th class="px-space-md py-space-sm font-label-sm text-label-sm text-[--lp-muted]">Start Date</th>
                                    <th class="px-space-md py-space-sm font-label-sm text-label-sm text-[--lp-muted]">Due Date</th>
                                    <th class="px-space-md py-space-sm font-label-sm text-label-sm text-[--lp-muted]">Status</th>
                                    <th class="px-space-md py-space-sm font-label-sm text-label-sm text-[--lp-muted]">Attempt</th>
                                    <th class="px-space-md py-space-sm font-label-sm text-label-sm text-[--lp-muted]">Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-t border-[--lp-outline]">
                                    <td class="whitespace-nowrap px-space-md py-space-sm font-body-sm text-body-sm text-[--lp-ink]">Quiz: Concept Check</td>
                                    <td class="whitespace-nowrap px-space-md py-space-sm font-body-sm text-body-sm text-[--lp-ink]">Individual</td>
                                    <td class="whitespace-nowrap px-space-md py-space-sm font-body-sm text-body-sm text-[--lp-ink]">Sep 23, 2026, 00:00</td>
                                    <td class="whitespace-nowrap px-space-md py-space-sm font-body-sm text-body-sm text-[--lp-ink]">Sep 29, 2026, 23:59</td>
                                    <td class="whitespace-nowrap px-space-md py-space-sm">
                                        <span class="inline-flex items-center gap-space-xs rounded-full bg-success-container px-space-sm py-space-xxs font-label-sm text-label-sm text-on-success-container">
                                            <span aria-hidden="true">&check;</span>
                                            Graded
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-space-md py-space-sm font-body-sm text-body-sm text-[--lp-ink]">1 of unlimited</td>
                                    <td class="whitespace-nowrap px-space-md py-space-sm font-body-sm text-body-sm text-[--lp-ink]">75.0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="chip === 'forum'" x-cloak class="space-y-space-md border-t border-[--lp-outline] pt-space-md">
                <div class="flex flex-wrap gap-space-xl">
                    <div>
                        <p class="font-label-sm text-label-sm text-[--lp-muted]">Start</p>
                        <p class="font-body-sm text-body-sm text-[--lp-ink]" x-text="sessions[active].start"></p>
                    </div>
                    <div>
                        <p class="font-label-sm text-label-sm text-[--lp-muted]">End</p>
                        <p class="font-body-sm text-body-sm text-[--lp-ink]" x-text="sessions[active].end"></p>
                    </div>
                    <div>
                        <p class="font-label-sm text-label-sm text-[--lp-muted]">Total Post</p>
                        <p class="font-body-sm text-body-sm text-[--lp-ink]">8</p>
                    </div>
                    <div>
                        <p class="font-label-sm text-label-sm text-[--lp-muted]">My Post</p>
                        <p class="flex items-center gap-space-xs font-body-sm text-body-sm text-[--lp-ink]">
                            2 of 2
                            <span class="flex h-4 w-4 items-center justify-center rounded-full bg-success text-[10px] text-white" aria-hidden="true">&check;</span>
                        </p>
                    </div>
                </div>

                <button type="button" class="rounded-lg bg-[--lp-primary] px-space-md py-space-xs font-label-sm text-label-sm text-[--lp-on-primary]">
                    Create New Thread
                </button>

                <div class="flex items-center justify-between border-t border-[--lp-outline] pt-space-sm">
                    <p class="font-body-sm text-body-sm text-[--lp-muted]">8 Results</p>
                    <label class="flex items-center gap-space-xs font-body-sm text-body-sm text-[--lp-muted]">
                        Show:
                        <span class="rounded border border-[--lp-outline] px-space-xs py-space-xxs font-body-sm text-body-sm text-[--lp-ink]">5</span>
                    </label>
                </div>

                @php
                    $threads = [
                        ['title' => "Question about this week's material #1", 'time' => '23 Sep 2026, 11:13', 'comments' => 5],
                        ['title' => 'Group assignment discussion #2', 'time' => '23 Sep 2026, 11:13', 'comments' => 0],
                        ['title' => 'Difficulty understanding a basic concept #3', 'time' => '23 Sep 2026, 11:13', 'comments' => 0],
                    ];
                @endphp

                <div class="overflow-hidden rounded-lg border border-[--lp-outline] divide-y divide-[--lp-outline]">
                    @foreach ($threads as $thread)
                        <div class="flex items-start gap-space-md p-space-md">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[--lp-primary] font-label-md text-label-md text-[--lp-on-primary]">D</div>
                            <div class="min-w-0 flex-1">
                                <p class="font-body-sm text-body-sm text-[--lp-ink]">
                                    <span class="font-medium">Demo Student</span>
                                    <span class="text-[--lp-muted]">&middot;</span>
                                    <span class="text-[--lp-primary]">Student</span>
                                </p>
                                <p class="font-label-sm text-label-sm text-[--lp-muted]">{{ $thread['time'] }}</p>
                                <p class="mt-space-xxs font-body-sm text-body-sm text-[--lp-ink]">{{ $thread['title'] }}</p>
                            </div>
                            <span class="flex shrink-0 items-center gap-space-xxs font-label-sm text-label-sm text-[--lp-muted]">
                                <span aria-hidden="true">&#128172;</span>
                                {{ $thread['comments'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
