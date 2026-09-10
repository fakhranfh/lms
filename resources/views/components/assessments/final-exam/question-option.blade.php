{{--
    Atom: one multiple-choice option row. Green + check when it's the
    correct option; red + cancel when it's the (wrong) option the student
    picked; otherwise a plain unchecked marker.
--}}
@props(['option', 'selected' => false])

@php
    $isWrongSelection = $selected && ! $option->is_correct;
    $color = $option->is_correct ? 'text-success' : ($isWrongSelection ? 'text-error' : 'text-on-surface-variant');
    $icon = $option->is_correct ? 'check_circle' : ($isWrongSelection ? 'cancel' : 'radio_button_unchecked');
@endphp

<div {{ $attributes->class(['flex items-center gap-space-sm text-body-sm', $color, 'font-medium' => $option->is_correct || $isWrongSelection]) }}>
    <span class="material-symbols-outlined text-[16px]">{{ $icon }}</span>
    <span class="rte-content">{!! $option->label !!}</span>
    @if ($selected)
        <span class="text-body-xs {{ $color }}">({{ __('Student\'s answer') }})</span>
    @endif
</div>
