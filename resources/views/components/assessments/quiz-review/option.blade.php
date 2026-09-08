{{-- Atom: one multiple-choice/true-false option row in a quiz review modal. --}}
@props(['option', 'selected'])

<div class="flex items-center gap-space-md p-space-lg border rounded-lg {{ $selected ? ($option->is_correct ? 'border-success bg-success/5' : 'border-error bg-error/5') : 'border-outline' }}">
    <span class="w-5 h-5 rounded-full border-2 flex-shrink-0 flex items-center justify-center {{ $selected ? ($option->is_correct ? 'border-success bg-success' : 'border-error bg-error') : 'border-outline' }}">
        @if ($selected)
            <span class="material-symbols-outlined text-white text-[14px]" data-weight="fill">{{ $option->is_correct ? 'check' : 'close' }}</span>
        @endif
    </span>
    <span class="rte-content text-body-lg text-on-surface flex-1">{!! $option->label !!}</span>
    @if ($selected)
        <span class="text-body-sm font-medium flex-shrink-0 {{ $option->is_correct ? 'text-success' : 'text-error' }}">
            {{ $option->is_correct ? 'Correct' : 'Incorrect' }}
        </span>
    @endif
</div>
