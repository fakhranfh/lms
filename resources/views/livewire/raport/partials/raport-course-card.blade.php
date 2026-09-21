{{--
    Read-only per-course report card used by the student's Raport index —
    final score banner + Assessment Type breakdown, without the interactive
    session drill-down GradebookIndex/GradebookShow expose.
--}}
<div wire:key="raport-course-{{ $card['course']->id }}" class="space-y-space-sm">
    <h2 class="font-title-md text-title-md text-on-surface font-bold">{{ $card['course']->title }}</h2>

    <div class="rounded-lg p-space-lg bg-primary grid grid-cols-[1fr_4rem_4rem] gap-space-lg items-start">
        <div class="min-w-0">
            <p class="font-title-md text-title-md text-on-primary">Final Score</p>
            @if ($card['finalLastUpdatedLabel'])
                <p class="text-body-xs text-on-primary/80">Last updated: {{ $card['finalLastUpdatedLabel'] }}</p>
            @endif
        </div>
        <div class="text-center">
            <p class="text-body-xs text-on-primary/80">Score</p>
            <p class="font-headline-sm text-headline-sm text-on-primary mt-space-xs">{{ $card['finalScore'] !== null ? number_format($card['finalScore'], 0) : '—' }}</p>
        </div>
        <div class="text-center">
            <p class="text-body-xs text-on-primary/80">Grade</p>
            <p class="font-headline-sm text-headline-sm text-on-primary mt-space-xs">{{ $card['finalGrade'] ?? '—' }}</p>
        </div>
    </div>

    <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden divide-y divide-outline-variant">
        @forelse ($card['typeRows'] as $typeRow)
            <div wire:key="raport-type-{{ $card['course']->id }}-{{ $typeRow['key'] }}" class="p-space-lg grid grid-cols-[1fr_4rem_4rem] gap-space-lg items-center">
                <p class="font-title-sm text-title-sm text-on-surface min-w-0">{{ $typeRow['label'] }}</p>
                <div class="text-center">
                    <div class="w-11 h-11 mx-auto rounded-full bg-surface-container flex items-center justify-center">
                        <span class="font-label-sm text-label-sm text-on-surface">{{ $typeRow['weight'] !== null ? number_format($typeRow['weight'], 0).'%' : '—' }}</span>
                    </div>
                </div>
                <p class="text-center font-title-sm text-title-sm text-on-surface">{{ $typeRow['score'] !== null ? number_format($typeRow['score'], 0) : '—' }}</p>
            </div>
        @empty
            <p class="p-space-lg text-center text-body-sm text-on-surface-variant">No assessments yet.</p>
        @endforelse
    </div>
</div>
