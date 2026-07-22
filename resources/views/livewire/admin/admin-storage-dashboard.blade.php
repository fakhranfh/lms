@section('title', 'Storage Monitoring')

@php
    $pct = $globalSummary['percentage'];
    $barColor = match (true) {
        $pct >= 100 => 'bg-error',
        $pct >= 90 => 'bg-orange-500',
        $pct >= 80 => 'bg-yellow-500',
        default => 'bg-success',
    };
    $bannerColor = match (true) {
        $pct >= 100 => 'bg-error/10 border-error/20 text-error',
        $pct >= 90 => 'bg-orange-500/10 border-orange-500/20 text-orange-600',
        $pct >= 80 => 'bg-yellow-500/10 border-yellow-500/20 text-yellow-600',
        default => null,
    };
@endphp

<div class="space-y-space-lg">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Storage Monitoring</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">Global material storage usage and per-school breakdown</p>
        </div>
        <a href="{{ route('admin.storage.materials') }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
            Browse Materials
        </a>
    </div>

    @if ($bannerColor)
        <div class="px-gutter py-space-md {{ $bannerColor }} border rounded-lg">
            <p class="font-body-md text-body-md">
                @if ($pct >= 100)
                    ❌ Storage quota full. Uploads are blocked school-wide until space is freed.
                @elseif ($pct >= 90)
                    ⚠️ Storage at {{ number_format($pct, 1) }}%. Uploads may start failing soon.
                @else
                    Storage at {{ number_format($pct, 1) }}%. Consider planning an archival strategy.
                @endif
            </p>
        </div>
    @endif

    {{-- Global Storage Summary Card --}}
    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
        <div class="flex items-center justify-between">
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Global Usage</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">
                {{ $globalSummary['used_formatted'] }} / {{ $globalSummary['quota_formatted'] }}
            </p>
        </div>
        <div class="w-full h-3 bg-surface-container rounded-full overflow-hidden">
            <div class="h-full {{ $barColor }} rounded-full" style="width: {{ min(100, $pct) }}%"></div>
        </div>
        <p class="text-body-sm text-on-surface-variant">{{ number_format($pct, 2) }}% used</p>
    </div>

    {{-- 30-Day Usage Trend --}}
    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
        <h2 class="font-headline-sm text-headline-sm text-on-surface">Usage Trend (Last 30 Days)</h2>

        @if ($usageTrend->count() < 2)
            <p class="text-body-sm text-on-surface-variant py-space-lg text-center">
                Not enough data yet. This chart fills in as the hourly storage check runs over time.
            </p>
        @else
            @php
                $width = 600;
                $height = 160;
                $padding = 8;
                $maxIndex = $usageTrend->count() - 1;
                $points = $usageTrend->values()->map(function ($log, $i) use ($width, $height, $padding, $maxIndex) {
                    $x = $maxIndex > 0 ? $padding + ($i / $maxIndex) * ($width - 2 * $padding) : $padding;
                    $y = $padding + (1 - min(100, $log->usage_percent) / 100) * ($height - 2 * $padding);

                    return ['x' => round($x, 2), 'y' => round($y, 2), 'log' => $log];
                });
                $linePoints = $points->map(fn ($p) => "{$p['x']},{$p['y']}")->implode(' ');
                $areaPoints = "{$padding},{$height} {$linePoints} {$width}," . $height;
                $last = $points->last();
            @endphp

            <svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full h-40 text-primary" role="img" aria-label="Global storage usage percentage over the last 30 days">
                <polyline points="{{ $areaPoints }}" fill="currentColor" fill-opacity="0.08" stroke="none" />
                <polyline points="{{ $linePoints }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                @foreach ($points as $point)
                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="2.5" fill="currentColor">
                        <title>{{ \Illuminate\Support\Carbon::parse($point['log']->created_at)->format('M j, H:i') }} — {{ number_format($point['log']->usage_percent, 1) }}%</title>
                    </circle>
                @endforeach
            </svg>

            <div class="flex items-center justify-between text-body-sm text-on-surface-variant">
                <span>{{ \Illuminate\Support\Carbon::parse($points->first()['log']->created_at)->format('M j') }}</span>
                <span class="font-medium text-on-surface">Latest: {{ number_format($last['log']->usage_percent, 1) }}%</span>
                <span>{{ \Illuminate\Support\Carbon::parse($last['log']->created_at)->format('M j') }}</span>
            </div>
        @endif
    </div>

    {{-- Per-School Breakdown Table --}}
    <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-outline-variant bg-surface-container-lowest">
                        <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">
                            <button type="button" wire:click="sortByColumn('school')" class="hover:underline">School</button>
                        </th>
                        <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">
                            <button type="button" wire:click="sortByColumn('used_bytes')" class="hover:underline">Storage Used</button>
                        </th>
                        <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">
                            <button type="button" wire:click="sortByColumn('material_count')" class="hover:underline">Material Count</button>
                        </th>
                        <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">Largest Material</th>
                        <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">Last Upload</th>
                        <th scope="col" class="px-space-lg py-space-md text-right font-label-md text-label-md text-secondary uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schoolBreakdown as $row)
                        <tr wire:key="school-{{ $row['school']->id }}" class="border-b border-outline-variant last:border-0 hover:bg-surface-container-lowest">
                            <td class="px-space-lg py-space-md font-body-md text-body-md text-on-surface">{{ $row['school']->name }}</td>
                            <td class="px-space-lg py-space-md font-body-md text-body-md text-on-surface">{{ $formatBytes($row['used_bytes']) }}</td>
                            <td class="px-space-lg py-space-md font-body-md text-body-md text-on-surface">{{ $row['material_count'] }}</td>
                            <td class="px-space-lg py-space-md font-body-md text-body-md text-on-surface">{{ $formatBytes($row['largest_material_bytes']) }}</td>
                            <td class="px-space-lg py-space-md font-body-md text-body-md text-on-surface">
                                {{ $row['last_upload_at'] ? \Illuminate\Support\Carbon::parse($row['last_upload_at'])->diffForHumans() : '—' }}
                            </td>
                            <td class="px-space-lg py-space-md text-right">
                                <a href="{{ route('admin.storage.materials', ['school' => $row['school']->id]) }}" class="font-label-md text-label-md text-primary hover:underline">
                                    View Materials
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-space-lg py-space-lg text-center text-body-md text-on-surface-variant">No schools with materials yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
