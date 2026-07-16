@section('title', 'Tier Change History - ' . $this->school->name)

<div class="space-y-space-lg">
    <div class="flex items-center justify-between">
        <h1 class="text-headline-lg font-headline-lg">Tier Change History</h1>
        <a href="{{ route('admin.schools.edit', $this->school) }}" class="px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors">
            Back to School
        </a>
    </div>

    <div class="bg-surface rounded-lg border border-outline-variant p-space-lg">
        <h2 class="text-title-md font-title-md mb-space-lg text-on-surface">{{ $this->school->name }}</h2>

        <!-- Timeline -->
        <div class="space-y-space-md">
            @forelse($this->tierChanges as $change)
                <div class="flex gap-space-lg pb-space-md border-b border-outline-variant last:border-b-0">
                    <!-- Timeline dot -->
                    <div class="flex flex-col items-center pt-space-xs">
                        <div class="w-4 h-4 rounded-full bg-primary border-4 border-surface"></div>
                    </div>

                    <!-- Change details -->
                    <div class="flex-1">
                        <div class="flex items-center gap-space-sm mb-space-xs">
                            <h3 class="font-label-md text-label-md">
                                @if($change->change_type->value === 'initial')
                                    <span class="text-primary">Initial Tier Assignment</span>
                                @elseif($change->change_type->value === 'upgrade')
                                    <span class="text-green-600">Upgrade</span>
                                @else
                                    <span class="text-orange-600">Downgrade</span>
                                @endif
                            </h3>
                            <span class="inline-block px-space-xs py-space-xxs rounded-full font-label-xs text-label-xs bg-surface-container text-on-surface-variant">
                                {{ ucfirst($change->change_type->value) }}
                            </span>
                        </div>

                        <p class="text-body-sm text-on-surface-variant mb-space-xs">
                            @if($change->change_type->value === 'initial')
                                Tier set to <strong>{{ $change->toTier->name }}</strong>
                            @else
                                Changed from <strong>{{ $change->fromTier?->name ?? 'N/A' }}</strong> to <strong>{{ $change->toTier->name }}</strong>
                            @endif
                        </p>

                        <p class="text-body-sm text-on-surface-variant">
                            {{ $change->changed_at_display->format('F d, Y h:i A') }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="text-center py-space-xl">
                    <p class="text-body-md text-on-surface-variant">No tier changes recorded yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
