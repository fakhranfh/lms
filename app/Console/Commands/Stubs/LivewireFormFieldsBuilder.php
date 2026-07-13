<?php

namespace App\Console\Commands\Stubs;

use Illuminate\Support\Str;

class LivewireFormFieldsBuilder
{
    /**
     * @param  array<string, array{table: string, column: string}>  $foreignKeys
     */
    public function build(array $columnInputTypes, array $foreignKeys = []): string
    {
        $fields = [];

        foreach ($columnInputTypes as $column => $inputType) {
            if ($inputType === 'skip') {
                continue;
            }

            $camel = Str::camel($column);
            $columnLabel = Str::title(str_replace('_', ' ', $column));

            if (isset($foreignKeys[$column])) {
                $fields[] = $this->buildForeignSelectField($camel, $columnLabel, $foreignKeys[$column]);

                continue;
            }

            $fields[] = match ($inputType) {
                'textarea' => $this->buildTextareaField($camel, $columnLabel),
                'checkbox' => $this->buildCheckboxField($camel, $columnLabel),
                'radio' => $this->buildRadioField($camel, $columnLabel),
                default => $this->buildInputField($camel, $columnLabel, $inputType),
            };
        }

        return implode("\n\n", $fields);
    }

    private function buildInputField(string $camel, string $label, string $inputType): string
    {
        $htmlType = in_array($inputType, ['date', 'datetime-local', 'email', 'password', 'url', 'tel', 'number'], true) ? $inputType : 'text';

        return <<<BLADE
        <div>
            <label for="{$camel}" class="block font-label-md text-label-md text-on-surface mb-space-xs">{$label}</label>
            <input type="{$htmlType}" wire:model="{$camel}" id="{$camel}"
                class="w-full px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md">
            @error('{$camel}')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ \$message }}</p>
            @enderror
        </div>
BLADE;
    }

    private function buildTextareaField(string $camel, string $label): string
    {
        return <<<BLADE
        <div>
            <label for="{$camel}" class="block font-label-md text-label-md text-on-surface mb-space-xs">{$label}</label>
            <textarea wire:model="{$camel}" id="{$camel}" rows="4"
                class="w-full px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md"></textarea>
            @error('{$camel}')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ \$message }}</p>
            @enderror
        </div>
BLADE;
    }

    private function buildCheckboxField(string $camel, string $label): string
    {
        return <<<BLADE
        <div>
            <label class="flex items-center gap-space-sm font-label-md text-label-md text-on-surface cursor-pointer">
                <input type="checkbox" wire:model="{$camel}" id="{$camel}">
                {$label}
            </label>
            @error('{$camel}')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ \$message }}</p>
            @enderror
        </div>
BLADE;
    }

    private function buildRadioField(string $camel, string $label): string
    {
        return <<<BLADE
        <div>
            <label class="block font-label-md text-label-md text-on-surface mb-space-xs">{$label}</label>
            <div class="flex items-center gap-space-lg">
                <label class="flex items-center gap-space-sm font-body-md text-body-md text-on-surface cursor-pointer">
                    <input type="radio" wire:model="{$camel}" id="{$camel}_yes" value="1"> Yes
                </label>
                <label class="flex items-center gap-space-sm font-body-md text-body-md text-on-surface cursor-pointer">
                    <input type="radio" wire:model="{$camel}" id="{$camel}_no" value="0"> No
                </label>
            </div>
            @error('{$camel}')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ \$message }}</p>
            @enderror
        </div>
BLADE;
    }

    /**
     * @param  array{table: string, column: string}  $foreignKey
     */
    private function buildForeignSelectField(string $camel, string $label, array $foreignKey): string
    {
        $relatedTable = $foreignKey['table'];
        $relatedColumn = $foreignKey['column'];

        return <<<BLADE
        <div>
            <label for="{$camel}" class="block font-label-md text-label-md text-on-surface mb-space-xs">{$label}</label>
            <select wire:model="{$camel}" id="{$camel}"
                class="w-full px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md">
                <option value="">-- Select {$label} --</option>
                @foreach (\${$relatedTable} as \$option)
                    <option value="{{ \$option->{$relatedColumn} }}">{{ \$option->name ?? \$option->{$relatedColumn} }}</option>
                @endforeach
            </select>
            @error('{$camel}')
                <p class="mt-space-xs font-body-sm text-body-sm text-error">{{ \$message }}</p>
            @enderror
        </div>
BLADE;
    }
}
