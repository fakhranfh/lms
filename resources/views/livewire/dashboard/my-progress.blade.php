<div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
    <h2 class="text-title-md font-title-md font-bold text-on-surface mb-space-md">My Progress</h2>

    @if($this->courseProgress->isEmpty())
        <p class="text-body-sm text-secondary">You are not enrolled in any courses yet.</p>
    @else
        <div class="space-y-space-md">
            @foreach($this->courseProgress as $progress)
                <div>
                    <div class="flex items-center justify-between mb-space-xs">
                        <span class="text-body-md font-body-md text-on-surface">{{ $progress['course']->title }}</span>
                        <span class="text-label-sm text-secondary">{{ $progress['percent'] }}%</span>
                    </div>
                    <div class="w-full h-2 bg-surface-container-highest rounded-full overflow-hidden">
                        <div class="h-full bg-primary transition-all duration-300" style="width: {{ $progress['percent'] }}%"></div>
                    </div>
                    <p class="text-body-sm text-secondary mt-space-xs">
                        {{ $progress['materials_done'] }}/{{ $progress['materials_total'] }} materials &middot;
                        {{ $progress['assessments_done'] }}/{{ $progress['assessments_total'] }} assessments
                    </p>
                </div>
            @endforeach
        </div>
    @endif
</div>
