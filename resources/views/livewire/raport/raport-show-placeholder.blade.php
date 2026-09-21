@section('title', $student->name)

<div wire:init="loadData" class="space-y-space-lg">
    <div class="flex items-start justify-between animate-pulse">
        <div class="h-8 bg-surface-container rounded w-56"></div>
        <div class="h-10 bg-surface-container rounded-lg w-40"></div>
    </div>

    <div class="space-y-space-xl animate-pulse">
        @for ($i = 0; $i < 2; $i++)
            <div class="space-y-space-sm">
                <div class="h-5 bg-surface-container rounded w-1/3"></div>
                <div class="h-24 bg-surface-container rounded-lg"></div>
            </div>
        @endfor
    </div>
</div>
