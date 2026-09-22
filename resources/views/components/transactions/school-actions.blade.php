{{-- TODO: the "Continue Payment" link (school.payment.index) was removed
     along with the school-payment checkout flow. Pending transactions can
     currently only be cancelled here, not resumed. --}}
@if ($transaction->status === \App\Enums\PaymentStatus::Pending)
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
