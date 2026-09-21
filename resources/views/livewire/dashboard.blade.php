@section('title', 'Dashboard')

<div class="space-y-space-lg" @if($isStudent) wire:init="loadStudentData" @endif>

    <!-- Storage Quota Card -->
    @if($storageQuota)
    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg hover:border-outline transition-colors duration-150">
        <div class="space-y-space-md">
            <div class="flex items-center justify-between">
                <span class="text-label-md text-secondary uppercase font-label-md">Storage</span>
                <span class="material-symbols-outlined text-[20px]" style="color: {{ $storageQuota['percentage'] >= 80 ? 'var(--md-sys-color-error)' : 'var(--md-sys-color-tertiary)' }}">storage</span>
            </div>
            <div>
                <p class="font-headline-md text-headline-md text-on-surface">{{ \App\Services\R2StorageService::formatBytes($storageQuota['remaining']) }}</p>
                <div class="flex items-center gap-space-xs mt-space-sm">
                    <div class="flex-1 h-2 bg-surface-container-highest rounded-full overflow-hidden">
                        <div class="h-full bg-tertiary transition-all duration-300" style="width: {{ $storageQuota['percentage'] }}%; background-color: {{ $storageQuota['percentage'] >= 80 ? 'var(--md-sys-color-error)' : ($storageQuota['percentage'] >= 50 ? 'var(--md-sys-color-warning)' : 'var(--md-sys-color-tertiary)') }}"></div>
                    </div>
                    <p class="font-body-sm text-body-sm text-secondary whitespace-nowrap">{{ round($storageQuota['percentage'], 1) }}%</p>
                </div>
                <p class="font-body-sm text-body-sm text-secondary mt-space-xs">
                    {{ \App\Services\R2StorageService::formatBytes($storageQuota['used']) }} / {{ $storageQuota['limit_gb'] }} GB
                </p>
            </div>
        </div>
    </div>
    @endif

    @if($isStudent)
        @if(!$studentDataLoaded)
            <!-- Student dashboard skeleton -->
            <div class="space-y-space-lg animate-pulse">
                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="h-4 bg-surface-container rounded w-40"></div>
                    @for ($i = 0; $i < 3; $i++)
                        <div class="space-y-space-xs">
                            <div class="h-3 bg-surface-container rounded w-1/3"></div>
                            <div class="h-2 bg-surface-container rounded-full w-full"></div>
                        </div>
                    @endfor
                </div>
                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="h-4 bg-surface-container rounded w-32"></div>
                    @for ($i = 0; $i < 4; $i++)
                        <div class="h-8 bg-surface-container rounded w-full"></div>
                    @endfor
                </div>
                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="h-4 bg-surface-container rounded w-48"></div>
                    @for ($i = 0; $i < 3; $i++)
                        <div class="h-10 bg-surface-container rounded w-full"></div>
                    @endfor
                </div>
            </div>
        @else
            <livewire:dashboard.my-progress wire:key="dashboard-my-progress" />

            <livewire:dashboard.todo-list wire:key="dashboard-todo-list" />

            <livewire:dashboard.latest-forum-posts wire:key="dashboard-latest-forum-posts" />
        @endif
    @endif

</div>
