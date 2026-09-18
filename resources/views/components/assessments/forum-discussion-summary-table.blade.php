{{--
    Molecule: per-session forum discussion summary shown to teachers for the
    auto-provisioned Forum Discussion assessment. Each row in `rows` is
    `['session', 'sessionIndex', 'metCount', 'totalStudents', 'href',
    'wireKey']`. Clicking a row opens the forum page filtered to that
    session.
--}}
@props([
    'rows',
    'emptyMessage',
])

<x-ui.livewire-data-table
    :columns="[
        ['key' => 'title', 'label' => 'Title', 'sortable' => false],
        ['key' => 'start_date', 'label' => 'Start Date', 'sortable' => false],
        ['key' => 'due_date', 'label' => 'Due Date', 'sortable' => false],
        ['key' => 'participation', 'label' => 'Participation', 'sortable' => false],
    ]"
    :items="$rows"
    :showPagination="false"
    :showActionsColumn="false"
>
    @forelse ($rows as $row)
        <tr
            wire:key="{{ $row['wireKey'] }}"
            @click="window.location = '{{ $row['href'] }}'"
            class="hover:bg-surface-container/30 transition cursor-pointer"
        >
            <td class="px-space-lg py-space-md">
                <p class="font-label-xs text-label-xs text-on-surface-variant">Session {{ $row['session']->order }} - {{ str($row['session']->delivery_mode->value)->replace('_', ' ')->title() }}</p>
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
                {{ $row['metCount'] }} of {{ $row['totalStudents'] }} students posted
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="4" class="px-space-lg py-space-lg text-center text-body-sm text-on-surface-variant">{{ $emptyMessage }}</td>
        </tr>
    @endforelse
</x-ui.livewire-data-table>
