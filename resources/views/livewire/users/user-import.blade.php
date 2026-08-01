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

    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg" x-data="{
            selectedFile: null, spreadsheetName: null,
            uploading: false, progress: 0,
            submit() {
                if (! this.selectedFile) { return }

                this.uploading = true;
                this.progress = 0;

                $wire.upload('spreadsheet', this.selectedFile,
                    () => { this.uploading = false; $wire.import(); },
                    () => { this.uploading = false; },
                    (event) => { this.progress = event.detail.progress; },
                );
            },
        }">
        <div>
            <h2 class="font-headline-sm text-headline-sm text-on-surface">
                {{ $role === 'teacher' ? 'Import Teachers' : 'Import Students' }}
            </h2>
            <p class="font-body-sm text-body-sm text-secondary mt-space-xs">
                Upload a spreadsheet with columns <code>Name</code> and <code>Email</code> — it must match the
                template exactly. Every row will be created as a {{ $role === 'teacher' ? 'Teacher' : 'Student' }}.
                Profile photos can be added afterwards from the
                <a href="{{ route('users.photos') }}" class="text-primary hover:underline">Bulk Upload Photos</a> page.
            </p>
            <a href="{{ $templateUrl }}"
                class="mt-space-sm inline-flex items-center gap-space-2xs font-label-md text-label-md text-primary hover:underline">
                <span class="material-symbols-outlined text-[18px]">download</span>
                Download {{ $role === 'teacher' ? 'teacher' : 'student' }} template (.xlsx)
            </a>
        </div>

        <div>
            <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Spreadsheet (.xlsx or .csv)</label>
            <label for="spreadsheet"
                class="flex flex-col items-center justify-center gap-space-xs px-space-lg py-space-xl border-2 border-dashed border-outline-variant rounded-lg cursor-pointer hover:border-primary hover:bg-primary/5 transition-colors text-center">
                <template x-if="!uploading">
                    <span class="material-symbols-outlined text-primary text-[32px]">upload_file</span>
                </template>
                <template x-if="uploading">
                    <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </template>
                <span class="font-label-md text-label-md text-primary" x-show="!spreadsheetName && !uploading">Click to choose a spreadsheet</span>
                <span class="font-label-md text-label-md text-on-surface" x-show="spreadsheetName && !uploading" x-text="spreadsheetName"></span>
                <span class="font-label-md text-label-md text-primary" x-show="uploading">Uploading... <span x-text="progress"></span>%</span>
                <span class="font-body-sm text-body-sm text-secondary" x-show="!uploading">.xlsx or .csv — not uploaded until you click Import</span>
                <div x-show="uploading" class="w-full max-w-[12rem] h-1.5 bg-surface-container rounded-full overflow-hidden">
                    <div class="h-full bg-primary transition-all" :style="`width: ${progress}%`"></div>
                </div>
            </label>
            <!-- Plain (non-wire:model) file input: picking a file only stores it in
                 Alpine state, it is not sent to the server until Import is clicked. -->
            <input type="file" id="spreadsheet" accept=".xlsx,.csv" class="hidden"
                @change="
                    const file = $event.target.files[0] ?? null;
                    selectedFile = file;
                    spreadsheetName = file ? file.name : null;
                ">
            @error('spreadsheet')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $message }}</p>
            @enderror
            @if ($columnError)
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ $columnError }}</p>
            @endif
        </div>

        <div class="flex items-center gap-space-md">
            <button type="button" @click="submit()" wire:loading.attr="disabled" wire:target="import"
                :disabled="!selectedFile || uploading"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md shadow-sm hover:opacity-90 hover:shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-space-sm">
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
    </div>
</div>
