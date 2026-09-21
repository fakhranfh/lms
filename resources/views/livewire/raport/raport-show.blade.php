@section('title', $student->name)

<div class="space-y-space-lg">
    <div class="flex items-center justify-between gap-space-md">
        <div class="flex items-center gap-space-md min-w-0">
            <a
                href="{{ route('raport.index') }}"
                wire:navigate
                class="flex-shrink-0 px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors inline-flex items-center gap-space-xs"
            >
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Back
            </a>
            <div class="min-w-0">
                <h1 class="font-headline-md text-headline-md text-on-surface truncate">{{ $student->name }}</h1>
                <p class="text-body-sm text-on-surface-variant">Raport</p>
            </div>
        </div>

        <a
            href="{{ $isSelf ? route('raport.export.self') : route('raport.export.student', $student) }}"
            class="flex-shrink-0 px-space-md py-space-sm rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm"
        >
            <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
            Export PDF
        </a>
    </div>

    @if ($courseCards->isEmpty())
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg text-center text-body-sm text-on-surface-variant">
            No courses found.
        </div>
    @else
        <div class="rounded-lg p-space-lg bg-primary grid grid-cols-[1fr_4rem_4rem] gap-space-lg items-center">
            <p class="font-title-md text-title-md text-on-primary">Overall Final Score ({{ $courseCards->count() }} course{{ $courseCards->count() === 1 ? '' : 's' }})</p>
            <p class="text-center font-headline-sm text-headline-sm text-on-primary">{{ $overallScore !== null ? number_format($overallScore, 0) : '—' }}</p>
            <p class="text-center font-headline-sm text-headline-sm text-on-primary">{{ $overallGrade ?? '—' }}</p>
        </div>

        <div class="space-y-space-xl">
            @foreach ($courseCards as $card)
                <div wire:key="raport-course-{{ $card['course']->id }}" class="space-y-space-sm">
                    <h2 class="font-title-md text-title-md text-on-surface bg-surface-container px-space-md py-space-sm rounded-lg">{{ $card['course']->title }}</h2>

                    <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-surface-container-lowest">
                                    <th class="p-space-md font-label-sm text-label-sm text-on-surface-variant border-b border-outline-variant">Assessment Type</th>
                                    <th class="p-space-md font-label-sm text-label-sm text-on-surface-variant border-b border-outline-variant text-right">Weight</th>
                                    <th class="p-space-md font-label-sm text-label-sm text-on-surface-variant border-b border-outline-variant text-right">Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($card['typeRows'] as $typeRow)
                                    <tr wire:key="raport-type-{{ $card['course']->id }}-{{ $typeRow['key'] }}" class="border-b border-outline-variant">
                                        <td class="p-space-md font-body-sm text-body-sm text-on-surface">{{ $typeRow['label'] }}</td>
                                        <td class="p-space-md font-body-sm text-body-sm text-on-surface text-right">{{ $typeRow['weight'] !== null ? number_format($typeRow['weight'], 0).'%' : '—' }}</td>
                                        <td class="p-space-md font-body-sm text-body-sm text-on-surface text-right">{{ $typeRow['score'] !== null ? number_format($typeRow['score'], 0) : '—' }}</td>
                                    </tr>
                                @endforeach
                                <tr class="bg-surface-container/40">
                                    <td class="p-space-md font-title-sm text-title-sm text-on-surface">Final Score</td>
                                    <td class="p-space-md font-title-sm text-title-sm text-on-surface text-right">100%</td>
                                    <td class="p-space-md font-title-sm text-title-sm text-on-surface text-right">{{ $card['finalScore'] !== null ? number_format($card['finalScore'], 0) : '—' }} ({{ $card['finalGrade'] ?? '—' }})</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if ($card['finalLastUpdatedLabel'])
                        <p class="text-body-xs text-on-surface-variant">Last updated: {{ $card['finalLastUpdatedLabel'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
