@section('title', $role === 'teacher' ? 'Import Teachers' : 'Import Students')

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
            <h2 class="font-headline-sm text-headline-sm text-on-surface">
                {{ $role === 'teacher' ? 'Import Teachers' : 'Import Students' }}
            </h2>
            <p class="font-body-sm text-body-sm text-secondary mt-space-xs">
                Upload a spreadsheet with columns <code>Name</code>, <code>Email</code>, and optionally
                <code>Photo Filename</code>. Every row will be created as a {{ $role === 'teacher' ? 'Teacher' : 'Student' }}.
                Then select the matching photo files below — the filename in the spreadsheet must match
                the uploaded photo's filename.
            </p>
            <a href="{{ $templateUrl }}"
                class="mt-space-sm inline-flex items-center gap-space-2xs font-label-md text-label-md text-primary hover:underline">
                <span class="material-symbols-outlined text-[18px]">download</span>
                Download {{ $role === 'teacher' ? 'teacher' : 'student' }} template (.xlsx)
            </a>
        </div>

        <form wire:submit="import" class="space-y-space-lg" x-data="{
            spreadsheetName: null,
            photoNames: [],
        }">
            <div>
                <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Spreadsheet (.xlsx or .csv)</label>
                <label for="spreadsheet"
                    class="flex flex-col items-center justify-center gap-space-xs px-space-lg py-space-xl border-2 border-dashed border-outline-variant rounded-lg cursor-pointer hover:border-primary hover:bg-primary/5 transition-colors text-center"
                    wire:loading.class="opacity-60 pointer-events-none" wire:target="spreadsheet">
                    <span class="material-symbols-outlined text-primary text-[32px]">upload_file</span>
                    <span class="font-label-md text-label-md text-primary" x-show="!spreadsheetName">Click to choose a spreadsheet</span>
                    <span class="font-label-md text-label-md text-on-surface" x-show="spreadsheetName" x-text="spreadsheetName"></span>
                    <span class="font-body-sm text-body-sm text-secondary">.xlsx or .csv</span>
                </label>
                <input type="file" wire:model="spreadsheet" id="spreadsheet" accept=".xlsx,.csv" class="hidden"
                    @change="spreadsheetName = $event.target.files.length ? $event.target.files[0].name : null">
                @error('spreadsheet')
                    <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Photos (optional, select multiple)</label>
                <label for="photos"
                    class="flex flex-col items-center justify-center gap-space-xs px-space-lg py-space-xl border-2 border-dashed border-outline-variant rounded-lg cursor-pointer hover:border-primary hover:bg-primary/5 transition-colors text-center"
                    wire:loading.class="opacity-60 pointer-events-none" wire:target="photos">
                    <span class="material-symbols-outlined text-primary text-[32px]">add_photo_alternate</span>
                    <span class="font-label-md text-label-md text-primary" x-show="photoNames.length === 0">Click to choose photos</span>
                    <span class="font-label-md text-label-md text-on-surface" x-show="photoNames.length > 0" x-text="photoNames.length + ' photo(s) selected'"></span>
                    <span class="font-body-sm text-body-sm text-secondary">JPG, GIF or PNG — filenames must match the spreadsheet</span>
                </label>
                <input type="file" wire:model="photos" id="photos" multiple accept="image/jpeg,image/png,image/gif" class="hidden"
                    @change="photoNames = Array.from($event.target.files).map(f => f.name)">
                @error('photos.*')
                    <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-space-md">
                <button type="submit" wire:loading.attr="disabled" wire:target="import,spreadsheet,photos"
                    class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md shadow-sm hover:opacity-90 hover:shadow-md transition-all disabled:opacity-70 disabled:cursor-not-allowed inline-flex items-center gap-space-sm">
                    <svg wire:loading wire:target="import" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="import" class="material-symbols-outlined text-[18px]">cloud_upload</span>
                    <span wire:loading.remove wire:target="import">Import</span>
                    <span wire:loading wire:target="import">Importing...</span>
                </button>
                <a href="{{ route('users.index') }}" class="font-label-md text-label-md text-secondary hover:underline">Cancel</a>
            </div>
        </form>
    </div>
</div>
