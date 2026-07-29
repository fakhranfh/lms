@if ($transaction->status === \App\Enums\PaymentStatus::Pending)
    <a href="{{ route('school.payment.index', $transaction) }}" class="inline-block px-space-md py-space-xs rounded-lg bg-primary text-on-primary font-label-sm text-label-sm hover:opacity-90 transition-opacity">
        Continue Payment
    </a>
    @if ($transaction->school)
        <button
            type="button"
            @click="cancelId = '{{ $transaction->id }}'; showModal = true"
            class="inline-block px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors"
        >
            Cancel
        </button>
    @endif
@else
    <span class="font-label-sm text-label-sm text-on-surface-variant">—</span>
@endif
