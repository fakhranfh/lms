<?php

namespace App\Console\Commands\Stubs;

use Illuminate\Support\Str;

class LivewireIndexViewStubGenerator
{
    public function generate(string $name, string $label, string $resource, array $columnInputTypes = [], array $filterDefinitions = []): string
    {
        $columns = array_keys(array_filter($columnInputTypes, fn ($type) => $type !== 'skip'));
        $tableColumns = $this->buildTableColumns($columns);
        $bodyCells = $this->buildBodyCells($columns);
        $filterInputs = $this->buildFilterInputs($filterDefinitions);
        $colspan = count($columns) + 1;

        return <<<BLADE
@section('title', '{$label}')

<div class="space-y-space-lg">
    @if (\$successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ \$successMessage }}</p>
        </div>
    @endif

    @if (\$errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ \$errorMessage }}</p>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="font-headline-sm text-headline-sm text-on-surface">{$label}</h1>
        <a href="{{ route('{$resource}.create') }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
            New {$label}
        </a>
    </div>

    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg flex flex-wrap gap-space-md items-end">
{$filterInputs}
        <div class="flex items-center gap-space-sm">
            <button type="button" wire:click="applyFilters" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                Apply Filter
            </button>
            <button type="button" wire:click="resetFilters" class="px-space-lg py-space-sm border border-outline-variant rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container-lowest transition-colors">
                Reset
            </button>
        </div>
    </div>

    <x-ui.livewire-data-table
        :columns="[
{$tableColumns}
        ]"
        :items="\$items"
        :sort="\$sort"
        :direction="\$direction"
    >
        @forelse (\$items as \$item)
            <tr class="border-b border-outline-variant last:border-0">
{$bodyCells}
                <td class="px-space-lg py-space-md text-right space-x-space-sm whitespace-nowrap">
                    <a href="{{ route('{$resource}.edit', \$item) }}" class="font-label-md text-label-md text-primary hover:underline">Edit</a>
                    <button type="button" @click="\$dispatch('open-delete-confirm', { id: {{ \$item->id }} })" class="font-label-md text-label-md text-error hover:underline">Delete</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{$colspan}" class="px-space-lg py-space-lg text-center font-body-md text-body-md text-secondary">No records yet.</td>
            </tr>
        @endforelse
    </x-ui.livewire-data-table>
</div>
BLADE;
    }

    private function buildTableColumns(array $columns): string
    {
        $lines = [];
        foreach ($columns as $column) {
            $columnLabel = Str::title(str_replace('_', ' ', $column));
            $lines[] = "            ['key' => '{$column}', 'label' => '{$columnLabel}'],";
        }

        return implode("\n", $lines);
    }

    private function buildBodyCells(array $columns): string
    {
        $lines = [];
        foreach ($columns as $column) {
            $lines[] = "                            <td class=\"px-space-lg py-space-md font-body-md text-body-md text-on-surface\">{{ \$item->{$column} }}</td>";
        }

        return implode("\n", $lines);
    }

    private function buildFilterInputs(array $filterDefinitions): string
    {
        $lines = [];

        foreach ($filterDefinitions as $filter) {
            $key = $filter['key'];
            $studly = Str::studly($key);
            $filterLabel = $filter['label'];

            if ($filter['type'] === 'datetime') {
                $lines[] = "        <div>\n            <label class=\"block font-label-sm text-label-sm text-secondary mb-space-xs\">{$filterLabel} From</label>\n            <input type=\"date\" wire:model=\"filter{$studly}From\" class=\"px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md\">\n        </div>";
                $lines[] = "        <div>\n            <label class=\"block font-label-sm text-label-sm text-secondary mb-space-xs\">{$filterLabel} To</label>\n            <input type=\"date\" wire:model=\"filter{$studly}To\" class=\"px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md\">\n        </div>";
            } elseif ($filter['type'] === 'enum') {
                $options = [];
                foreach ($filter['options'] ?? [] as $value => $optionLabel) {
                    $options[] = "                <option value=\"{$value}\">{$optionLabel}</option>";
                }
                $optionsStr = implode("\n", $options);
                $lines[] = "        <div>\n            <label class=\"block font-label-sm text-label-sm text-secondary mb-space-xs\">{$filterLabel}</label>\n            <select wire:model=\"filter{$studly}\" class=\"px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md\">\n                <option value=\"\">All</option>\n{$optionsStr}\n            </select>\n        </div>";
            } else {
                $lines[] = "        <div>\n            <label class=\"block font-label-sm text-label-sm text-secondary mb-space-xs\">{$filterLabel}</label>\n            <input type=\"text\" wire:model=\"filter{$studly}\" wire:keydown.enter=\"applyFilters\" class=\"px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md\">\n        </div>";
            }
        }

        return implode("\n", $lines);
    }
}
