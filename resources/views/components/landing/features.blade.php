<section class="border-t border-[--lp-outline] bg-[--lp-surface]">
    <div class="mx-auto max-w-6xl px-gutter py-space-xl lg:px-space-xl">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-balance font-headline-md text-headline-md text-[--lp-ink] tracking-[-0.02em]">Everything a school runs on, in one place</h2>
            <p class="mx-auto mt-space-sm max-w-lg text-pretty font-body-md text-body-md text-[--lp-muted]">From syllabus to report card, {{ config('app.name', 'Laravel') }} covers the academic year end to end &mdash; for admins, instructors, and students alike.</p>
        </div>

        <div class="mt-space-xl grid gap-space-lg lg:grid-cols-6">
            <div class="reveal flex flex-col justify-between gap-space-lg rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-4" style="animation-delay: 0.02s">
                <div>
                    <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Syllabus &amp; course builder</h3>
                    <p class="mt-space-xs max-w-md font-body-sm text-body-sm text-[--lp-muted]">Structure courses into modules and sessions, define learning outcomes and rubrics, and set class policies &mdash; then publish when ready.</p>
                </div>
                <div class="overflow-hidden rounded-lg border border-[--lp-outline] bg-[--lp-surface]">
                    <div class="space-y-space-xs px-space-md py-space-md">
                        <div class="flex items-center justify-between rounded bg-[--lp-bg] px-space-sm py-space-xs">
                            <span class="font-body-sm text-body-sm text-[--lp-ink]">Session 1 &middot; Linear Equations</span>
                            <span class="font-label-sm text-label-sm text-[--lp-muted]">Learning outcome set</span>
                        </div>
                        <div class="flex items-center justify-between rounded bg-[--lp-bg] px-space-sm py-space-xs">
                            <span class="font-body-sm text-body-sm text-[--lp-ink]">Session 2 &middot; Quadratic Functions</span>
                            <span class="font-label-sm text-label-sm text-[--lp-muted]">Rubric attached</span>
                        </div>
                        <div class="flex items-center justify-between rounded bg-[--lp-bg] px-space-sm py-space-xs">
                            <span class="font-body-sm text-body-sm text-[--lp-ink]">Class policy</span>
                            <span class="font-label-sm text-label-sm text-[--lp-muted]">Published</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-2" style="animation-delay: 0.06s">
                <div>
                    <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Assessments &amp; grading</h3>
                    <p class="mt-space-xs font-body-sm text-body-sm text-[--lp-muted]">Quizzes, assignments, and final exams with a queue-monitored scoring pipeline for instructors to review and finalize.</p>
                </div>
                <div class="flex items-center justify-between rounded border border-[--lp-outline] px-space-sm py-space-xs">
                    <span class="font-body-sm text-body-sm text-[--lp-ink]">Essay &middot; Photosynthesis</span>
                    <span class="font-label-sm text-label-sm text-[--lp-primary]">Scored</span>
                </div>
            </div>

            <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-2" style="animation-delay: 0.1s">
                <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Exam proctoring</h3>
                <p class="font-body-sm text-body-sm text-[--lp-muted]">Live sessions capture snapshots and integrity events during timed exams, so instructors can review anything flagged after the fact.</p>
            </div>

            <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-2" style="animation-delay: 0.14s">
                <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">QR &amp; geo attendance</h3>
                <p class="font-body-sm text-body-sm text-[--lp-muted]">Students check in per session by QR code, with location verification and automatic scoring against the attendance policy.</p>
            </div>

            <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-2" style="animation-delay: 0.18s">
                <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Gradebook &amp; report cards</h3>
                <p class="font-body-sm text-body-sm text-[--lp-muted]">Scores from assessments, attendance, and participation roll up automatically into a per-student gradebook and report card.</p>
            </div>

            <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-3" style="animation-delay: 0.22s">
                <div>
                    <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Video conferencing</h3>
                    <p class="mt-space-xs font-body-sm text-body-sm text-[--lp-muted]">Hold live classes with built-in video sessions and participation tracked per student &mdash; no third-party meeting link to juggle.</p>
                </div>
            </div>

            <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-3" style="animation-delay: 0.26s">
                <div>
                    <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Discussion forums</h3>
                    <p class="mt-space-xs font-body-sm text-body-sm text-[--lp-muted]">Threaded course discussions with read tracking and participation scoring, so forum activity can count toward a grade.</p>
                </div>
            </div>

            <div class="reveal flex flex-col justify-between gap-space-md rounded-xl border border-[--lp-outline] bg-[--lp-bg] p-space-lg lg:col-span-6" style="animation-delay: 0.3s">
                <div class="flex flex-wrap items-center justify-between gap-space-sm">
                    <div>
                        <h3 class="font-headline-sm text-headline-sm text-[--lp-ink]">Media library &amp; cloud storage</h3>
                        <p class="mt-space-xs max-w-lg font-body-sm text-body-sm text-[--lp-muted]">Upload and version lesson materials &mdash; video, PDF, audio, images &mdash; to cloud storage, with usage monitored per school.</p>
                    </div>
                    <div class="flex items-center gap-space-md">
                        <div class="h-2 w-32 overflow-hidden rounded-full bg-[--lp-outline]">
                            <div class="h-full w-3/5 rounded-full bg-[--lp-primary]"></div>
                        </div>
                        <span class="shrink-0 font-label-sm text-label-sm text-[--lp-muted]">Storage in use</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
