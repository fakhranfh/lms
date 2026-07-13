<?php

namespace App\Console\Commands\Stubs;

class LivewireEditViewStubGenerator
{
    private LivewireFormFieldsBuilder $fieldsBuilder;

    public function __construct()
    {
        $this->fieldsBuilder = new LivewireFormFieldsBuilder;
    }

    public function generate(string $label, string $resource, array $columnInputTypes, array $foreignKeys = []): string
    {
        $fields = $this->fieldsBuilder->build($columnInputTypes, $foreignKeys);

        return <<<BLADE
@section('title', 'Edit {$label}')

<div class="max-w-2xl">
    <form wire:submit="update" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
{$fields}

        <div class="flex items-center gap-space-md">
            <button type="submit" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                Save
            </button>
            <a href="{{ route('{$resource}.index') }}" class="font-label-md text-label-md text-secondary hover:underline">Cancel</a>
        </div>
    </form>
</div>
BLADE;
    }
}
