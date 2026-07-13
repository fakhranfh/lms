<?php

namespace App\Console\Commands\Stubs;

use Illuminate\Support\Str;

class LivewireIndexStubGenerator
{
    public function generate(string $name, string $label, string $plural, string $resource, array $filterDefinitions = []): string
    {
        $camelName = Str::camel($name);
        $filterProperties = $this->buildFilterProperties($filterDefinitions);
        $filterAssignments = $this->buildFilterAssignments($filterDefinitions);
        $filterResets = $this->buildFilterResets($filterDefinitions);

        return <<<PHP
<?php

namespace App\Livewire\\{$plural};

use App\Services\\{$name}Service;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class {$name}Index extends Component
{
    use WithPagination;

    public ?string \$successMessage = null;

    public ?string \$errorMessage = null;

    public string \$search = '';

    public string \$sort = 'id';

    public string \$direction = 'desc';

    public int \$perPage = 10;
{$filterProperties}

    public function mount(): void
    {
        \$this->successMessage = session('success');
    }

    public function updating(string \$property): void
    {
        if (\$property !== 'sort' && \$property !== 'direction') {
            \$this->resetPage();
        }
    }

    public function sortBy(string \$field): void
    {
        if (\$this->sort === \$field) {
            \$this->direction = \$this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            \$this->sort = \$field;
            \$this->direction = 'asc';
        }
    }

    public function applyFilters(): void
    {
        \$this->resetPage();
    }

    public function resetFilters(): void
    {
{$filterResets}
        \$this->resetPage();
    }

    #[On('delete-confirmed')]
    public function destroy(int \$id, {$name}Service \${$camelName}Service): void
    {
        abort_unless(auth()->user()->can('{$resource}.delete'), 403);

        \$this->successMessage = null;
        \$this->errorMessage = null;

        try {
            \${$camelName}Service->delete(\$id);
            \$this->successMessage = __('{$label} deleted successfully.');
        } catch (ValidationException \$exception) {
            \$this->errorMessage = collect(\$exception->errors())->flatten()->first();
        }
    }

    public function render({$name}Service \${$camelName}Service)
    {
        \$filters = [
            'search' => \$this->search,
{$filterAssignments}
        ];

        return view('livewire.{$this->kebabPlural($plural)}.{$this->kebabName($name)}-index', [
            'items' => \${$camelName}Service->paginate(\$filters, \$this->perPage, \$this->sort, \$this->direction),
        ])
            ->extends('layouts.app', ['topbarTitle' => '{$label}'])
            ->section('app-content');
    }
}
PHP;
    }

    private function kebabPlural(string $plural): string
    {
        return Str::kebab($plural);
    }

    private function kebabName(string $name): string
    {
        return Str::kebab($name);
    }

    private function buildFilterProperties(array $filterDefinitions): string
    {
        $lines = [];

        foreach ($filterDefinitions as $filter) {
            $key = $filter['key'];
            $studly = Str::studly($key);

            if ($filter['type'] === 'datetime') {
                $lines[] = "\n    public string \$filter{$studly}From = '';";
                $lines[] = "\n    public string \$filter{$studly}To = '';";
            } else {
                $lines[] = "\n    public string \$filter{$studly} = '';";
            }
        }

        return implode('', $lines);
    }

    private function buildFilterAssignments(array $filterDefinitions): string
    {
        $lines = [];

        foreach ($filterDefinitions as $filter) {
            $key = $filter['key'];
            $studly = Str::studly($key);

            if ($filter['type'] === 'datetime') {
                $lines[] = "            '{$key}_from' => \$this->filter{$studly}From,";
                $lines[] = "            '{$key}_to' => \$this->filter{$studly}To,";
            } else {
                $lines[] = "            '{$key}' => \$this->filter{$studly},";
            }
        }

        return implode("\n", $lines);
    }

    private function buildFilterResets(array $filterDefinitions): string
    {
        $lines = [];

        foreach ($filterDefinitions as $filter) {
            $key = $filter['key'];
            $studly = Str::studly($key);

            if ($filter['type'] === 'datetime') {
                $lines[] = "        \$this->filter{$studly}From = '';";
                $lines[] = "        \$this->filter{$studly}To = '';";
            } else {
                $lines[] = "        \$this->filter{$studly} = '';";
            }
        }

        return implode("\n", $lines);
    }
}
