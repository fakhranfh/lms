@php
    $gradebookTypes = [
        ['key' => 'quiz', 'label' => 'Quiz', 'weight' => 15, 'score' => 82, 'expandable' => true, 'items' => [
            ['label' => 'Quiz: Concept Check', 'weight' => 8, 'score' => 75],
            ['label' => 'Quiz: Chapter Review', 'weight' => 7, 'score' => 90],
        ]],
        ['key' => 'assignment', 'label' => 'Assignment', 'weight' => 25, 'score' => 88, 'expandable' => true, 'items' => [
            ['label' => 'Assignment: Problem Set 1', 'weight' => 25, 'score' => 88],
        ]],
        ['key' => 'final_exam', 'label' => 'Final Exam', 'weight' => 30, 'score' => null, 'expandable' => true, 'items' => [
            ['label' => 'Final Exam: Theory', 'weight' => 30, 'score' => null],
        ]],
        ['key' => 'attendance', 'label' => 'Attendance', 'weight' => 20, 'score' => 95, 'expandable' => false, 'items' => []],
        ['key' => 'forum', 'label' => 'Forum Discussion', 'weight' => 10, 'score' => 100, 'expandable' => false, 'items' => []],
    ];

    $raportCourses = [
        ['title' => 'Introduction to Algebra', 'score' => 88, 'grade' => 'A', 'types' => [
            ['label' => 'Quiz', 'weight' => 15, 'score' => 82],
            ['label' => 'Assignment', 'weight' => 25, 'score' => 88],
            ['label' => 'Attendance', 'weight' => 20, 'score' => 95],
        ]],
        ['title' => 'World History', 'score' => 79, 'grade' => 'B', 'types' => [
            ['label' => 'Quiz', 'weight' => 20, 'score' => 74],
            ['label' => 'Forum Discussion', 'weight' => 10, 'score' => 100],
        ]],
    ];
@endphp

<div class="reveal flex flex-col gap-space-lg rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-6" style="animation-delay: 0.18s">
    <div>
        <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Gradebook &amp; report cards</h3>
        <p class="mt-space-xs font-body-sm text-body-sm text-[--lp-muted]">Scores from assessments, attendance, and participation roll up automatically into a per-student gradebook and report card.</p>
    </div>

    <div class="grid gap-space-lg lg:grid-cols-2">
        <!-- Preview 1: Gradebook (student view) -->
        <div>
            <p class="font-label-sm text-label-sm text-[--lp-ink]">Gradebook</p>
            <p class="mt-space-xxs font-body-sm text-body-sm text-[--lp-muted]">Students see their own final score broken down by assessment type, with sessions available to drill into.</p>

            <div class="mt-space-sm space-y-space-md rounded-lg border border-[--lp-outline] bg-[--lp-surface] p-space-md" x-data="{ types: @js($gradebookTypes), expanded: null }">
                <div class="grid grid-cols-[1fr_3rem_3rem] items-start gap-space-sm rounded-lg bg-[--lp-primary] p-space-md">
                    <div>
                        <p class="font-label-sm text-label-sm text-[--lp-on-primary]">Final Score</p>
                        <p class="font-label-sm text-label-sm text-[--lp-on-primary]">Weight 100%</p>
                    </div>
                    <div class="text-center">
                        <p class="font-label-sm text-label-sm text-[--lp-on-primary]">Score</p>
                        <p class="font-headline-sm text-headline-sm text-[--lp-on-primary]">85</p>
                    </div>
                    <div class="text-center">
                        <p class="font-label-sm text-label-sm text-[--lp-on-primary]">Grade</p>
                        <p class="font-headline-sm text-headline-sm text-[--lp-on-primary]">A</p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-[--lp-outline]">
                    <template x-for="(type, index) in types" :key="type.key">
                        <div class="border-t border-[--lp-outline] first:border-t-0">
                            <button
                                type="button"
                                @click="type.expandable && (expanded = expanded === index ? null : index)"
                                class="flex w-full items-center gap-space-sm p-space-md text-left"
                                :class="type.expandable ? 'cursor-pointer hover:bg-[--lp-bg]' : ''"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="flex items-center gap-space-xs font-body-sm text-body-sm text-[--lp-ink]">
                                        <span x-text="type.label"></span>
                                        <span x-show="type.expandable" class="text-[--lp-muted]" x-text="expanded === index ? '▲' : '▼'"></span>
                                    </p>
                                </div>
                                <span class="w-10 shrink-0 text-center font-label-sm text-label-sm text-[--lp-muted]" x-text="type.weight + '%'"></span>
                                <span class="w-10 shrink-0 text-center font-body-sm text-body-sm text-[--lp-ink]" x-text="type.score ?? '—'"></span>
                            </button>

                            <div x-show="expanded === index" x-cloak class="space-y-space-xxs border-t border-[--lp-outline] bg-[--lp-bg] px-space-md py-space-sm">
                                <template x-for="(item, iIndex) in type.items" :key="iIndex">
                                    <div class="flex items-center gap-space-sm py-space-xxs">
                                        <p class="min-w-0 flex-1 font-label-sm text-label-sm text-[--lp-ink]" x-text="item.label"></p>
                                        <span class="w-10 shrink-0 text-center font-label-sm text-label-sm text-[--lp-muted]" x-text="item.weight + '%'"></span>
                                        <span class="w-10 shrink-0 text-center font-label-sm text-label-sm text-[--lp-ink]" x-text="item.score ?? '—'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Preview 2: Raport (student view) -->
        <div>
            <p class="font-label-sm text-label-sm text-[--lp-ink]">Raport</p>
            <p class="mt-space-xxs font-body-sm text-body-sm text-[--lp-muted]">A read-only report card across every enrolled course, exportable as a PDF.</p>

            <div class="mt-space-sm space-y-space-md rounded-lg border border-[--lp-outline] bg-[--lp-surface] p-space-md" x-data="{ exported: false, courses: @js($raportCourses) }">
                <div class="flex items-center justify-between gap-space-md rounded-lg bg-[--lp-primary] p-space-md">
                    <p class="font-label-sm text-label-sm text-[--lp-on-primary]">Overall Final Score (<span x-text="courses.length"></span> courses)</p>
                    <div class="flex items-center gap-space-sm">
                        <span class="font-headline-sm text-headline-sm text-[--lp-on-primary]">84</span>
                        <span class="font-headline-sm text-headline-sm text-[--lp-on-primary]">A</span>
                    </div>
                </div>

                <button
                    type="button"
                    @click="exported = true; setTimeout(() => exported = false, 2000)"
                    class="flex w-full items-center justify-center gap-space-xs rounded-lg bg-[--lp-primary] px-space-md py-space-xs font-label-sm text-label-sm text-[--lp-on-primary]"
                >
                    <span x-show="!exported">Export PDF</span>
                    <span x-show="exported" x-cloak>Exported &#10003;</span>
                </button>

                <template x-for="(course, cIndex) in courses" :key="cIndex">
                    <div class="space-y-space-xs">
                        <p class="font-label-sm text-label-sm text-[--lp-ink]" x-text="course.title"></p>
                        <div class="flex items-center justify-between gap-space-sm rounded-lg bg-[--lp-bg] px-space-md py-space-sm">
                            <span class="font-label-sm text-label-sm text-[--lp-muted]">Final Score</span>
                            <div class="flex items-center gap-space-md">
                                <span class="font-body-sm text-body-sm text-[--lp-ink]" x-text="course.score"></span>
                                <span class="font-body-sm text-body-sm text-[--lp-primary]" x-text="course.grade"></span>
                            </div>
                        </div>
                        <div class="overflow-hidden rounded-lg border border-[--lp-outline]">
                            <template x-for="(type, tIndex) in course.types" :key="tIndex">
                                <div class="flex items-center gap-space-sm border-t border-[--lp-outline] px-space-md py-space-xs first:border-t-0">
                                    <p class="min-w-0 flex-1 font-label-sm text-label-sm text-[--lp-ink]" x-text="type.label"></p>
                                    <span class="w-10 shrink-0 text-center font-label-sm text-label-sm text-[--lp-muted]" x-text="type.weight + '%'"></span>
                                    <span class="w-10 shrink-0 text-center font-label-sm text-label-sm text-[--lp-ink]" x-text="type.score"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
