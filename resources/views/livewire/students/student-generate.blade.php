@section('title', 'Generate Students')

<div class="w-full space-y-space-lg">
    @if (! empty($generatedStudents))
        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
            <table class="w-full">
                <thead class="bg-surface-container border-b border-outline-variant">
                    <tr>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Name</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Email</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Login Link</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @foreach ($generatedStudents as $student)
                        <tr>
                            <td class="px-space-lg py-space-md text-body-md text-on-surface">{{ $student['name'] }}</td>
                            <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ $student['email'] }}</td>
                            <td class="px-space-lg py-space-md">
                                <div class="flex items-center gap-space-sm" x-data="{ copied: false }">
                                    <input type="text" readonly value="{{ $student['loginUrl'] }}" x-ref="url"
                                        class="w-full px-space-sm py-space-xs border border-outline-variant rounded font-body-sm text-body-sm">
                                    <button type="button"
                                        @click="navigator.clipboard.writeText($refs.url.value); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="px-space-sm py-space-xs bg-primary text-on-primary rounded font-label-sm text-label-sm hover:opacity-90 transition-opacity whitespace-nowrap">
                                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
        <div>
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Generate Students</h2>
            <p class="font-body-sm text-body-sm text-secondary mt-space-xs">
                Creates fake student accounts (for demo/testing), each with a one-time login link.
            </p>
        </div>

        <div>
            <label for="count" class="block font-label-md text-label-md text-on-surface mb-space-xs">Number of students</label>
            <input type="number" wire:model="count" id="count" min="1" max="200"
                class="w-full px-space-md py-space-sm border rounded-lg font-body-md text-body-md border-outline-variant">
            @error('count')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="loginLinkTtlDays" class="block font-label-md text-label-md text-on-surface mb-space-xs">
                Login link valid for (days)
            </label>
            <input type="number" wire:model="loginLinkTtlDays" id="loginLinkTtlDays" min="1" max="365"
                class="w-full px-space-md py-space-sm border rounded-lg font-body-md text-body-md border-outline-variant">
            @error('loginLinkTtlDays')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-space-md">
            <button type="button" wire:click="generate" wire:loading.attr="disabled" wire:target="generate"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center gap-space-sm">
                <svg wire:loading wire:target="generate" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="generate">Generate</span>
                <span wire:loading wire:target="generate">Generating...</span>
            </button>
            <a href="{{ route('students.index') }}" class="font-label-md text-label-md text-secondary hover:underline">Cancel</a>
        </div>
    </div>
</div>
