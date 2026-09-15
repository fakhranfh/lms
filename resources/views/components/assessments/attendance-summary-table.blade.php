{{--
    Molecule: per-session attendance summary shown to teachers for the
    auto-provisioned Attendance assessment. Each row in `rows` is
    `['session', 'sessionIndex', 'attendedCount', 'totalStudents', 'href',
    'wireKey']`. Clicking a row opens the per-student attendance table on
    the Attendance page, where presence can be marked.
--}}
@props([
    'rows',
    'emptyMessage',
])

<div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="border-b border-outline-variant bg-surface-container/50">
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Title</th>
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Start Date</th>
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Due Date</th>
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Attendance</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
            @forelse ($rows as $row)
                <tr
                    wire:key="{{ $row['wireKey'] }}"
                    @click="window.location = '{{ $row['href'] }}'"
                    class="hover:bg-surface-container/30 transition cursor-pointer"
                >
                    <td class="px-space-lg py-space-md">
                        <p class="font-label-xs text-label-xs text-on-surface-variant">Session {{ $row['session']->order }}</p>
                    </td>
                    <td class="px-space-lg py-space-md text-body-sm text-on-surface">
                        {{ $row['session']->date_start_display?->format('d M Y,') }}<br>
                        {{ $row['session']->date_start_display?->format('H:i') }} {{ $row['session']->date_start_display?->format('T') }}
                    </td>
                    <td class="px-space-lg py-space-md">
                        <div class="text-body-sm text-on-surface">
                            {{ $row['session']->date_end_display?->format('d M Y,') }}<br>
                            {{ $row['session']->date_end_display?->format('H:i') }} {{ $row['session']->date_end_display?->format('T') }}
                        </div>
                        @if ($row['session']->date_end_display?->isPast())
                            <x-ui.status-pill bg="bg-on-surface-variant/20" text="text-on-surface" class="mt-1 px-space-xs py-1 text-body-xs font-medium">
                                Expired
                            </x-ui.status-pill>
                        @endif
                    </td>
                    <td class="px-space-lg py-space-md font-label-md text-label-md text-on-surface">
                        {{ $row['attendedCount'] }} of {{ $row['totalStudents'] }} students attended
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-space-lg py-space-lg text-center text-body-sm text-on-surface-variant">{{ $emptyMessage }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
