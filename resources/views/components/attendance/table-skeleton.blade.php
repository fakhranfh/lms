{{--
    Molecule: skeleton table mirroring the Attendance page's real table
    layout — student rows show Session/Delivery/Dates/Attend/Requirement,
    teacher rows show Student (with avatar)/Delivery/Requirement/Status and,
    when they can manage attendance, a Mark Attendance column with a stack
    of radio-row placeholders. Composed of the <x-ui.skeleton-box> atom.
--}}
@props([
    'isStudent' => false,
    'canManage' => false,
    'rows' => 5,
])

<div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="border-b border-outline-variant bg-surface-container/50">
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">{{ $isStudent ? 'Session' : 'Student' }}</th>
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Delivery</th>
                @if ($isStudent)
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Dates</th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Attend</th>
                @endif
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Attendance Requirement</th>
                @unless ($isStudent)
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Status</th>
                    @if ($canManage)
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Mark Attendance</th>
                    @endif
                @endunless
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
            @for ($i = 0; $i < $rows; $i++)
                <tr>
                    <td class="px-space-lg py-space-md">
                        <div class="flex items-center gap-space-sm">
                            @unless ($isStudent)
                                <x-ui.skeleton-box class="h-8 w-8 rounded-full flex-shrink-0" />
                            @endunless
                            <x-ui.skeleton-box class="h-4 w-32" />
                        </div>
                    </td>
                    <td class="px-space-lg py-space-md">
                        <x-ui.skeleton-box class="h-4 w-20" />
                    </td>
                    @if ($isStudent)
                        <td class="px-space-lg py-space-md">
                            <x-ui.skeleton-box class="h-4 w-28" />
                        </td>
                        <td class="px-space-lg py-space-md">
                            <x-ui.skeleton-box class="h-6 w-24 rounded-full" />
                        </td>
                    @endif
                    <td class="px-space-lg py-space-md">
                        <x-ui.skeleton-box class="h-3 w-24" />
                    </td>
                    @unless ($isStudent)
                        <td class="px-space-lg py-space-md">
                            <x-ui.skeleton-box class="h-6 w-24 rounded-full" />
                        </td>
                        @if ($canManage)
                            <td class="px-space-lg py-space-md space-y-1">
                                <x-ui.skeleton-box class="h-3 w-16" />
                                <x-ui.skeleton-box class="h-3 w-16" />
                                <x-ui.skeleton-box class="h-3 w-16" />
                                <x-ui.skeleton-box class="h-3 w-16" />
                            </td>
                        @endif
                    @endunless
                </tr>
            @endfor
        </tbody>
    </table>
</div>
