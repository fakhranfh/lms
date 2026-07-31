@section('title', 'Import Users')

<div class="max-w-2xl space-y-space-lg">
    @if ($createdCount !== null)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $createdCount }} user(s) imported successfully.</p>
        </div>
    @endif

    @if (! empty($importErrors))
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg space-y-space-xs">
            <p class="font-label-md text-label-md text-error">{{ count($importErrors) }} row(s) could not be imported:</p>
            <ul class="list-disc list-inside font-body-sm text-body-sm text-error">
                @foreach ($importErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
        <div>
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Import Teachers &amp; Students</h2>
            <p class="font-body-sm text-body-sm text-secondary mt-space-xs">
                Upload a spreadsheet with columns <code>name</code>, <code>email</code>, <code>role</code>
                (Teacher or Student), and optionally <code>photo_filename</code>. Then select the matching
                photo files below — the filename in the spreadsheet must match the uploaded photo's filename.
                <a href="{{ asset('templates/users-import-template.csv') }}" class="text-primary hover:underline">Download template</a>.
            </p>
        </div>

        <form wire:submit="import" class="space-y-space-lg">
            <div>
                <label for="spreadsheet" class="block font-label-md text-label-md text-on-surface mb-space-xs">Spreadsheet (.xlsx or .csv)</label>
                <input type="file" wire:model="spreadsheet" id="spreadsheet" accept=".xlsx,.csv"
                    class="w-full px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md">
                @error('spreadsheet')
                    <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="photos" class="block font-label-md text-label-md text-on-surface mb-space-xs">Photos (optional, select multiple)</label>
                <input type="file" wire:model="photos" id="photos" multiple accept="image/jpeg,image/png,image/gif"
                    class="w-full px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md">
                @error('photos.*')
                    <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
                @enderror
                @if (! empty($photos))
                    <p class="mt-space-xs font-body-sm text-body-sm text-secondary">{{ count($photos) }} photo(s) selected.</p>
                @endif
            </div>

            <div class="flex items-center gap-space-md">
                <button type="submit" wire:loading.attr="disabled" wire:target="import,spreadsheet,photos"
                    class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center gap-space-sm">
                    <svg wire:loading wire:target="import" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="import">Import</span>
                    <span wire:loading wire:target="import">Importing...</span>
                </button>
                <a href="{{ route('users.index') }}" class="font-label-md text-label-md text-secondary hover:underline">Cancel</a>
            </div>
        </form>
    </div>
</div>
