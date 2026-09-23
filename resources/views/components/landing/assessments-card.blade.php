@php
    $groups = [
        [
            'label' => 'QUIZ: 15%',
            'kind' => 'assessment',
            'rows' => [
                ['title' => 'Quiz: Concept Check', 'due' => 'Sep 29, 2026', 'status' => 'Graded', 'score' => '75.0'],
                ['title' => 'Quiz: Chapter Review', 'due' => 'Oct 6, 2026', 'status' => 'Not started', 'score' => null],
            ],
        ],
        [
            'label' => 'ASSIGNMENT: 25%',
            'kind' => 'assessment',
            'rows' => [
                ['title' => 'Assignment: Problem Set 1', 'due' => 'Oct 3, 2026', 'status' => 'Submitted', 'score' => null],
            ],
        ],
        [
            'label' => 'FINAL EXAM: 30%',
            'kind' => 'assessment',
            'rows' => [
                ['title' => 'Final Exam: Theory', 'due' => 'Dec 12, 2026', 'status' => 'Not started', 'score' => null],
            ],
        ],
        [
            'label' => 'ATTENDANCE: 20%',
            'kind' => 'session',
            'rows' => [
                ['session' => 'Session 1 · Introduction & Course Overview', 'value' => 'Present'],
                ['session' => 'Session 2 · Linear Equations', 'value' => 'Present'],
                ['session' => 'Session 3 · Quadratic Functions', 'value' => 'Absent'],
            ],
        ],
        [
            'label' => 'FORUM DISCUSSION: 10%',
            'kind' => 'session',
            'rows' => [
                ['session' => 'Session 1 · Introduction & Course Overview', 'value' => '2 of 2 posts'],
                ['session' => 'Session 2 · Linear Equations', 'value' => '1 of 2 posts'],
            ],
        ],
    ];
@endphp

<div class="reveal flex flex-col justify-between gap-space-lg rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-6" style="animation-delay: 0.06s">
    <div>
        <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Assessments &amp; grading</h3>
        <p class="mt-space-xs font-body-sm text-body-sm text-[--lp-muted]">Quizzes, assignments, final exams, attendance, and forum discussion &mdash; grouped by weight, with a queue-monitored scoring pipeline for instructors to review and finalize.</p>
    </div>

    <div class="space-y-space-sm" x-data="{ openGroups: [0] }">
        @foreach ($groups as $index => $group)
            <div class="overflow-hidden rounded-lg border border-[--lp-outline]">
                <button
                    type="button"
                    @click="openGroups.includes({{ $index }}) ? openGroups = openGroups.filter(i => i !== {{ $index }}) : openGroups.push({{ $index }})"
                    class="flex w-full items-center justify-between gap-space-md bg-[--lp-surface] px-space-md py-space-sm transition-colors hover:bg-[--lp-bg]"
                >
                    <span class="flex items-center gap-space-sm">
                        <span
                            class="material-symbols-outlined text-[--lp-muted] transition-transform"
                            :class="openGroups.includes({{ $index }}) ? 'rotate-90' : ''"
                        >chevron_right</span>
                        <span class="font-label-sm text-label-sm text-[--lp-ink]">{{ $group['label'] }}</span>
                    </span>
                    <span class="font-label-sm text-label-sm text-[--lp-muted]">{{ count($group['rows']) }} item{{ count($group['rows']) !== 1 ? 's' : '' }}</span>
                </button>

                <div x-show="openGroups.includes({{ $index }})" x-cloak class="overflow-x-auto border-t border-[--lp-outline] bg-[--lp-bg]">
                    @if ($group['kind'] === 'assessment')
                        <table class="w-full table-fixed text-left">
                            <colgroup>
                                <col class="w-2/5">
                                <col class="w-1/5">
                                <col class="w-1/5">
                                <col class="w-1/5">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="px-space-md py-space-xs font-label-sm text-label-sm text-[--lp-muted]">Title</th>
                                    <th class="px-space-md py-space-xs font-label-sm text-label-sm text-[--lp-muted]">Due Date</th>
                                    <th class="px-space-md py-space-xs font-label-sm text-label-sm text-[--lp-muted]">Status</th>
                                    <th class="px-space-md py-space-xs font-label-sm text-label-sm text-[--lp-muted]">Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group['rows'] as $row)
                                    <tr class="border-t border-[--lp-outline]">
                                        <td class="truncate px-space-md py-space-xs font-body-sm text-body-sm text-[--lp-ink]">{{ $row['title'] }}</td>
                                        <td class="truncate px-space-md py-space-xs font-body-sm text-body-sm text-[--lp-ink]">{{ $row['due'] }}</td>
                                        <td class="px-space-md py-space-xs">
                                            @if ($row['status'] === 'Graded')
                                                <span class="inline-flex items-center gap-space-xs rounded-full bg-success-container px-space-sm py-space-xxs font-label-sm text-label-sm text-on-success-container">
                                                    <span aria-hidden="true">&check;</span>
                                                    Graded
                                                </span>
                                            @else
                                                <span class="font-label-sm text-label-sm text-[--lp-muted]">{{ $row['status'] }}</span>
                                            @endif
                                        </td>
                                        <td class="truncate px-space-md py-space-xs font-body-sm text-body-sm text-[--lp-ink]">{{ $row['score'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <table class="w-full table-fixed text-left">
                            <colgroup>
                                <col class="w-4/5">
                                <col class="w-1/5">
                            </colgroup>
                            <tbody>
                                @foreach ($group['rows'] as $row)
                                    <tr class="border-t border-[--lp-outline] first:border-t-0">
                                        <td class="truncate px-space-md py-space-xs font-body-sm text-body-sm text-[--lp-ink]">{{ $row['session'] }}</td>
                                        <td class="truncate px-space-md py-space-xs text-right font-label-sm text-label-sm {{ $row['value'] === 'Absent' ? 'text-error' : 'text-[--lp-primary]' }}">{{ $row['value'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
