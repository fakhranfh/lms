@section('title', 'Pricing Tiers')

<div class="space-y-space-lg" x-data="{ deleteId: null }" @confirm-delete.window="deleteId && Livewire.dispatch('action', { method: 'destroy', id: deleteId }); deleteId = null">
    @if ($successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Pricing Tiers</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">Manage subscription pricing tiers</p>
        </div>
        <a href="{{ route('admin.pricing-tiers.create') }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
            New Tier
        </a>
    </div>

    @if ($tiers->isEmpty())
        <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center">
            <p class="text-body-md text-on-surface-variant mb-4">No pricing tiers configured yet.</p>
            <a href="{{ route('admin.pricing-tiers.create') }}" class="text-primary font-medium hover:underline">
                Create your first tier
            </a>
        </div>
    @else
        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-outline-variant bg-surface-container-lowest">
                            <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">Name</th>
                            <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">Price</th>
                            <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">Features</th>
                            <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">Status</th>
                            <th scope="col" class="px-space-lg py-space-md text-right font-label-md text-label-md text-secondary uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tiers as $tier)
                            <tr class="border-b border-outline-variant last:border-0">
                                <td class="px-space-lg py-space-md">
                                    <p class="font-body-md text-body-md text-on-surface">{{ $tier->name }}</p>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">{{ $tier->slug }}</p>
                                </td>
                                <td class="px-space-lg py-space-md font-body-md text-body-md text-on-surface">
                                    Rp {{ number_format($tier->price, 0, '.', '.') }}
                                </td>
                                <td class="px-space-lg py-space-md font-body-sm text-body-sm text-secondary">
                                    {{ $tier->features->count() }} feature{{ $tier->features->count() !== 1 ? 's' : '' }}
                                </td>
                                <td class="px-space-lg py-space-md">
                                    @if ($tier->is_active)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-body-sm font-medium bg-success/10 border border-success/20 text-success">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-body-sm font-medium bg-surface-container text-on-surface-variant">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-space-lg py-space-md text-right space-x-space-sm whitespace-nowrap">
                                    <a href="{{ route('admin.pricing-tiers.edit', $tier) }}" class="font-label-md text-label-md text-primary hover:underline">Edit</a>
                                    <button type="button" @click="deleteId = {{ $tier->id }}; window.dispatchEvent(new CustomEvent('delete-modal-open'))" class="font-label-md text-label-md text-error hover:underline">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <x-delete-modal
        title="Delete Pricing Tier"
        message="Are you sure you want to delete this pricing tier? This action cannot be undone."
        resourceName="pricing tier"
    />
</div>

<script>
document.addEventListener('confirm-delete', function() {
    const deleteId = document.querySelector('[x-data]')?.__x?.deleteId;
    if (deleteId) {
        @this.call('destroy', deleteId);
    }
});
</script>
