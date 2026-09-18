{{--
    Organism: the drag-reorderable assessment table for a single type group
    (personal/team assignment, quiz, final exam). Relies on the ancestor
    x-data scope in assessment-index.blade.php for `selectedIds`,
    `deletingIds`, `deleteId` and `showDeleteModal`. Composed of the
    <x-ui.status-pill> atom.
--}}
@props([
    'group',
    'isStudent',
    'course',
])

<div
    x-data="{
        dragId: null,
        currentOrder() {
            return Array.from(this.$refs.assessmentList.querySelectorAll('[data-row]')).map(el => el.dataset.row);
        },
        onDrop(targetId) {
            const list = this.$refs.assessmentList;
            const dragEl = this.dragId ? list.querySelector(`[data-row='${this.dragId}']`) : null;
            const targetEl = list.querySelector(`[data-row='${targetId}']`);
            this.dragId = null;
            if (! dragEl || ! targetEl || dragEl === targetEl) return;

            const previousOrder = this.currentOrder();

            const rows = Array.from(list.querySelectorAll('[data-row]'));
            rows.indexOf(dragEl) < rows.indexOf(targetEl) ? targetEl.after(dragEl) : targetEl.before(dragEl);
            this.updateMoveButtons();

            $wire.call('reorderAssessments', @js($group['type']->value), this.currentOrder())
                .catch(() => this.restoreOrder(previousOrder));
        },
        moveRow(id, direction) {
            const rows = Array.from(this.$refs.assessmentList.querySelectorAll('[data-row]'));
            const index = rows.findIndex(el => el.dataset.row === id);
            const swapWith = index + direction;
            if (index === -1 || swapWith < 0 || swapWith >= rows.length) return;

            const previousOrder = this.currentOrder();

            direction === -1
                ? rows[index].parentNode.insertBefore(rows[index], rows[swapWith])
                : rows[index].parentNode.insertBefore(rows[swapWith], rows[index]);
            this.updateMoveButtons();

            $wire.call('moveAssessment', id, direction === -1 ? 'up' : 'down')
                .catch(() => this.restoreOrder(previousOrder));
        },
        // Puts rows back in a previously captured order, used to undo
        // the optimistic DOM move when the server-side reorder fails.
        restoreOrder(orderedIds) {
            const list = this.$refs.assessmentList;
            orderedIds.forEach(id => {
                const row = list.querySelector(`[data-row='${id}']`);
                if (row) list.appendChild(row);
            });
            this.updateMoveButtons();
        },
        // The disabled state on the up/down buttons marks a row's
        // position, not the row itself — after a drag or move it must
        // be recomputed from the new DOM order rather than waiting for
        // the wire:call round trip to re-render it.
        updateMoveButtons() {
            const rows = Array.from(this.$refs.assessmentList.querySelectorAll('[data-row]'));
            rows.forEach((row, index) => {
                const upButton = row.querySelector('[data-move-up]');
                const downButton = row.querySelector('[data-move-down]');
                if (upButton) upButton.disabled = index === 0;
                if (downButton) downButton.disabled = index === rows.length - 1;
            });
        },
    }"
>
    <x-ui.data-table-shell :bare="true">
        <x-slot:head>
            <thead>
            <tr class="border-b border-outline-variant bg-surface-container/50">
                @unless ($isStudent)
                    {{--
                        No x-data here on purpose: an inner x-data would shadow
                        the ancestor's `selectedIds` (Alpine's prototypal scope
                        rules mean assigning to it from a nested scope creates
                        a local copy instead of updating the shared array), so
                        the "select all" checkbox would silently select nothing.
                        The group's ids are inlined directly into each
                        expression instead of being cached in a local scope.
                    --}}
                    <th class="px-space-lg py-space-md w-10">
                        <input
                            type="checkbox"
                            :checked="@js($group['selectableAssessmentIds']).length > 0 && @js($group['selectableAssessmentIds']).every(id => selectedIds.includes(id))"
                            @change="selectedIds = $event.target.checked ? [...new Set([...selectedIds, ...@js($group['selectableAssessmentIds'])])] : selectedIds.filter(id => ! @js($group['selectableAssessmentIds']).includes(id))"
                            class="w-4 h-4 rounded border-outline"
                        />
                    </th>
                @endunless
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Title</th>
                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Assigned to</th>
                @if ($group['showExamType'])
                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Exam Type</th>
                @endif
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
        </x-slot:head>

        <tbody class="divide-y divide-outline-variant" x-ref="assessmentList">
            @foreach ($group['assessments'] as $item)
                <tr
                    wire:key="assessment-skeleton-{{ $item['data']->id }}"
                    x-show="deletingIds.includes(@js((string) $item['data']->id))"
                    x-cloak
                >
                    <td colspan="{{ $group['columnCount'] }}" class="px-space-lg py-space-md">
                        <x-ui.skeleton-box class="h-6 w-full rounded-full" />
                    </td>
                </tr>
                <tr
                    wire:key="assessment-{{ $item['data']->id }}"
                    data-row="{{ $item['data']->id }}"
                    x-show="! deletingIds.includes(@js((string) $item['data']->id))"
                    @if ($item['isReorderable'])
                        draggable="true"
                        @dragstart="dragId = @js((string) $item['data']->id)"
                        @dragover.prevent
                        @drop.prevent="onDrop(@js((string) $item['data']->id))"
                    @endif
                    @if ($item['row']['route'])
                        @click="window.location = '{{ $item['row']['route'] }}'"
                        class="hover:bg-surface-container/30 transition cursor-pointer"
                    @else
                        class="hover:bg-surface-container/30 transition"
                    @endif
                >
                    @unless ($isStudent)
                        <td class="px-space-lg py-space-md" @click.stop>
                            <div class="flex items-center gap-space-xs">
                                @if ($item['isReorderable'])
                                    <span class="material-symbols-outlined text-on-surface-variant cursor-grab select-none" title="Drag to reorder">drag_indicator</span>
                                @endif
                                @if ($item['row']['route'] && ! $item['isAutoProvisionedType'])
                                    <input
                                        type="checkbox"
                                        x-model="selectedIds"
                                        value="{{ $item['data']->id }}"
                                        class="w-4 h-4 rounded border-outline"
                                    />
                                @endif
                            </div>
                        </td>
                    @endunless
                    <td class="px-space-lg py-space-md">
                        @if ($item['sessionPosition'])
                            <p class="font-label-xs text-label-xs text-on-surface-variant">Session {{ $item['sessionPosition'] }}</p>
                        @endif
                        @if ($item['row']['route'])
                            <span class="text-on-surface font-label-md text-label-md">
                                {{ $item['data']->title }}
                            </span>
                        @else
                            <span class="text-on-surface-variant opacity-60 font-label-md text-label-md">
                                {{ $item['data']->title }}
                            </span>
                        @endif
                    </td>
                    <td class="px-space-lg py-space-md text-body-sm text-on-surface">
                        <span class="inline-flex items-center gap-space-xs">
                            <span class="material-symbols-outlined text-[16px]">
                                {{ $item['data']->assigned_to->value === 'individual' ? 'person' : 'groups' }}
                            </span>
                            {{ str($item['data']->assigned_to->value)->title() }}
                        </span>
                    </td>
                    @if ($group['showExamType'])
                        <td class="px-space-lg py-space-md text-body-sm text-on-surface">
                            {{ $item['examTypeLabel'] ?? '—' }}
                        </td>
                    @endif
                    <td class="px-space-lg py-space-md text-body-sm text-on-surface">
                        @if ($item['data']->start_date)
                            {{ $item['data']->start_date_display->format('M j, Y, H:i') }}
                        @else
                            <span class="text-on-surface-variant">—</span>
                        @endif
                    </td>
                    <td class="px-space-lg py-space-md">
                        <div class="flex items-center gap-space-xs">
                            @if ($item['data']->end_date)
                                <span class="text-body-sm text-on-surface">{{ $item['data']->end_date_display->format('M j, Y, H:i') }}</span>
                                @if ($item['row']['isExpired'])
                                    <x-ui.status-pill bg="bg-error/10" text="text-error" class="px-space-xs py-1 text-body-xs font-medium">
                                        Expired
                                    </x-ui.status-pill>
                                @endif
                            @else
                                <span class="text-on-surface-variant">—</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-space-lg py-space-md">
                        <x-ui.skeleton-box wire:loading.block wire:target="{{ $item['publishWireTargets'] }}" class="hidden h-6 w-full rounded-full" />
                        <x-ui.status-pill
                            wire:loading.remove
                            wire:target="{{ $item['publishWireTargets'] }}"
                            :bg="$item['row']['statusConfig']['bg']"
                            :text="$item['row']['statusConfig']['text']"
                            :icon="$item['row']['statusConfig']['icon']"
                            class="w-full justify-center"
                        >
                            {{ str($item['row']['status'])->replace('_', ' ')->title() }}
                        </x-ui.status-pill>
                    </td>
                    @if ($isStudent)
                        <td class="px-space-lg py-space-md text-body-sm text-on-surface">
                            @if ($item['row']['route'])
                                {{ $item['row']['attemptCount'] }} of {{ $item['row']['attemptLimit'] }}
                            @else
                                <span class="text-on-surface-variant">—</span>
                            @endif
                        </td>
                        <td class="px-space-lg py-space-md text-body-sm text-on-surface font-label-md">
                            @if ($item['row']['score'] !== null)
                                {{ number_format($item['row']['score'], 1) }}
                            @else
                                <span class="text-on-surface-variant">—</span>
                            @endif
                        </td>
                    @endif
                    @unless ($isStudent)
                        <td class="px-space-lg py-space-md" @click.stop>
                            <div class="flex gap-space-sm">
                                @if (! $item['isAutoProvisionedType'])
                                    <button
                                        type="button"
                                        data-move-up
                                        @click="moveRow(@js((string) $item['data']->id), -1)"
                                        @disabled($loop->first)
                                        class="p-2 hover:bg-surface-container rounded transition text-on-surface-variant disabled:opacity-30 disabled:pointer-events-none"
                                        title="Move up"
                                    >
                                        <span class="material-symbols-outlined">arrow_upward</span>
                                    </button>
                                    <button
                                        type="button"
                                        data-move-down
                                        @click="moveRow(@js((string) $item['data']->id), 1)"
                                        @disabled($loop->last)
                                        class="p-2 hover:bg-surface-container rounded transition text-on-surface-variant disabled:opacity-30 disabled:pointer-events-none"
                                        title="Move down"
                                    >
                                        <span class="material-symbols-outlined">arrow_downward</span>
                                    </button>
                                @endif

                                @if ($item['row']['route'])
                                    <a
                                        href="{{ $item['editRoute'] }}"
                                        class="p-2 hover:bg-surface-container rounded transition text-primary inline-flex"
                                        title="Edit assessment"
                                    >
                                        <span class="material-symbols-outlined">edit</span>
                                    </a>

                                    @if ($item['isAssignmentType'])
                                        @if ($item['isDraft'])
                                            <button
                                                type="button"
                                                wire:click="publishAssessment('{{ $item['data']->id }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="publishAssessment('{{ $item['data']->id }}')"
                                                class="p-2 hover:bg-surface-container rounded transition text-success disabled:opacity-50"
                                                title="Publish assessment"
                                            >
                                                <span class="material-symbols-outlined">publish</span>
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="unpublishAssessment('{{ $item['data']->id }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="unpublishAssessment('{{ $item['data']->id }}')"
                                                class="p-2 hover:bg-surface-container rounded transition text-on-surface-variant disabled:opacity-50"
                                                title="Unpublish assessment"
                                            >
                                                <span class="material-symbols-outlined">unpublished</span>
                                            </button>
                                        @endif
                                    @endif

                                    @if (! $item['isAutoProvisionedType'])
                                        <button
                                            type="button"
                                            @click.stop="deleteId = @js($item['data']->id); deleteMode = 'single'; showDeleteModal = true"
                                            class="p-2 hover:bg-surface-container rounded transition text-error"
                                        >
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    @endunless
                </tr>
            @endforeach
        </tbody>
    </x-ui.data-table-shell>
</div>
