{{-- Atom: final-score pill shown in a quiz review modal's header. --}}
@props(['total'])

<div class="inline-flex items-center gap-space-sm bg-primary rounded-lg px-space-md py-space-xs text-on-primary">
    <span class="text-body-xs opacity-90">Final Score</span>
    <span class="font-bold text-body-md">{{ $total !== null ? rtrim(rtrim(number_format($total, 1), '0'), '.') : '—' }} pts</span>
</div>
