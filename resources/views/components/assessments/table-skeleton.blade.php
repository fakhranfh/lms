{{--
    Molecule: skeleton table mirroring <x-assessments.table>'s column layout
    (checkbox, Title, Assigned to, Start Date, Due Date, Status, and either
    Attempt/Score or Actions), used while the assessment list is loading.
    Composed of the <x-ui.skeleton-box> atom.
--}}
@props([
    'isStudent' => false,
    'rows' => 3,
])

<div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="border-b border-outline-variant bg-surface-container/50">
                @unless ($isStudent)
                    <th class="px-space-lg py-space-md w-10"></th>
                @endunless
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Title</th>
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Assigned to</th>
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Start Date</th>
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Due Date</th>
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Status</th>
                @if ($isStudent)
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Attempt</th>
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Score</th>
                @else
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
            @for ($i = 0; $i < $rows; $i++)
                <tr>
                    @unless ($isStudent)
                        <td class="px-space-lg py-space-md">
                            <x-ui.skeleton-box class="h-4 w-4 rounded" />
                        </td>
                    @endunless
                    <td class="px-space-lg py-space-md space-y-space-xs">
                        <x-ui.skeleton-box class="h-4 w-40" />
                        <x-ui.skeleton-box class="h-3 w-24" />
                    </td>
                    <td class="px-space-lg py-space-md">
                        <x-ui.skeleton-box class="h-4 w-20" />
                    </td>
                    <td class="px-space-lg py-space-md">
                        <x-ui.skeleton-box class="h-4 w-28" />
                    </td>
                    <td class="px-space-lg py-space-md">
                        <x-ui.skeleton-box class="h-4 w-28" />
                    </td>
                    <td class="px-space-lg py-space-md">
                        <x-ui.skeleton-box class="h-6 w-full rounded-full" />
                    </td>
                    @if ($isStudent)
                        <td class="px-space-lg py-space-md">
                            <x-ui.skeleton-box class="h-4 w-16" />
                        </td>
                        <td class="px-space-lg py-space-md">
                            <x-ui.skeleton-box class="h-4 w-10" />
                        </td>
                    @else
                        <td class="px-space-lg py-space-md">
                            <div class="flex gap-space-sm">
                                <x-ui.skeleton-box class="h-8 w-8 rounded" />
                                <x-ui.skeleton-box class="h-8 w-8 rounded" />
                                <x-ui.skeleton-box class="h-8 w-8 rounded" />
                            </div>
                        </td>
                    @endif
                </tr>
            @endfor
        </tbody>
    </table>
</div>
