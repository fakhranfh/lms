@section('title', 'Raport')

<div wire:init="loadData" class="space-y-space-lg">
    <div class="flex items-start justify-between animate-pulse">
        <div class="h-8 bg-surface-container rounded w-40"></div>
        @if ($isStudent || $isLocalEnv)
            <div class="h-10 bg-surface-container rounded-lg w-40"></div>
        @endif
    </div>

    <div class="space-y-space-lg animate-pulse">
        @if ($isStudent)
            <div class="rounded-lg p-space-lg bg-surface-container grid grid-cols-[1fr_4rem_4rem] gap-space-lg items-center">
                <div class="h-4 bg-surface rounded w-48"></div>
                <div class="h-6 bg-surface rounded w-10 mx-auto"></div>
                <div class="h-6 bg-surface rounded w-10 mx-auto"></div>
            </div>

            @for ($i = 0; $i < 2; $i++)
                <div class="space-y-space-sm">
                    <div class="h-5 bg-surface-container rounded w-1/3"></div>

                    <div class="rounded-lg p-space-lg bg-surface-container grid grid-cols-[1fr_4rem_4rem] gap-space-lg items-center">
                        <div class="h-4 bg-surface rounded w-24"></div>
                        <div class="h-6 bg-surface rounded w-10 mx-auto"></div>
                        <div class="h-6 bg-surface rounded w-10 mx-auto"></div>
                    </div>

                    <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden divide-y divide-outline-variant">
                        @for ($j = 0; $j < 6; $j++)
                            <div class="p-space-lg grid grid-cols-[1fr_4rem_4rem] gap-space-lg items-center">
                                <div class="h-4 bg-surface-container rounded w-1/3"></div>
                                <div class="w-11 h-11 mx-auto rounded-full bg-surface-container"></div>
                                <div class="h-4 bg-surface-container rounded w-8 mx-auto"></div>
                            </div>
                        @endfor
                    </div>
                </div>
            @endfor
        @else
            <div class="flex items-center gap-space-sm flex-wrap">
                <div class="h-4 bg-surface-container rounded w-10"></div>
                <div class="flex items-center gap-space-xs">
                    @for ($i = 0; $i < 5; $i++)
                        <div class="w-7 h-7 bg-surface-container rounded-full"></div>
                    @endfor
                </div>
            </div>

            <div class="space-y-space-md">
                <div class="h-9 bg-surface-container rounded-lg w-56"></div>

                <div class="flex items-center justify-between gap-space-md flex-wrap">
                    <div class="h-4 bg-surface-container rounded w-40"></div>
                    <div class="flex items-center gap-space-md">
                        <div class="h-9 bg-surface-container rounded-lg w-48"></div>
                        <div class="h-9 bg-surface-container rounded-lg w-24"></div>
                    </div>
                </div>
            </div>

            <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
                <x-ui.person-grid-skeleton :rows="9" />
            </div>
        @endif
    </div>
</div>
