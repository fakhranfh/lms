@section('title', 'Users')

<div class="space-y-space-lg">
    @if ($successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    <h1 class="font-headline-sm text-headline-sm text-on-surface">Users</h1>

    <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-outline-variant bg-surface-container-lowest">
                        <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">Name</th>
                        <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">Email</th>
                        <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">Roles</th>
                        <th scope="col" class="px-space-lg py-space-md text-right font-label-md text-label-md text-secondary uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-b border-outline-variant last:border-0">
                            <td class="px-space-lg py-space-md font-body-md text-body-md text-on-surface">{{ $user->name }}</td>
                            <td class="px-space-lg py-space-md font-body-md text-body-md text-on-surface">{{ $user->email }}</td>
                            <td class="px-space-lg py-space-md font-body-sm text-body-sm text-secondary">
                                {{ $user->roles->pluck('name')->join(', ') ?: '—' }}
                            </td>
                            <td class="px-space-lg py-space-md text-right whitespace-nowrap">
                                <a href="{{ route('users.roles.edit', $user) }}" class="font-label-md text-label-md text-primary hover:underline">Assign Roles</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-space-lg py-space-lg text-center font-body-md text-body-md text-secondary">No users yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
