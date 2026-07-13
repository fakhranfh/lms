<?php

namespace App\Console\Commands\Stubs;

use App\Console\Commands\Helpers\SchemaHelper;
use Illuminate\Support\Str;

class LivewireCreateStubGenerator
{
    public function generate(string $name, string $label, string $plural, string $resource, array $columnInputTypes = []): string
    {
        $camelName = Str::camel($name);
        $foreignKeys = SchemaHelper::getForeignKeys($name);

        $properties = $this->buildProperties($columnInputTypes);
        [$foreignImports, $foreignParams, $foreignData] = $this->buildForeignData($name, $foreignKeys);

        return <<<PHP
<?php

namespace App\Livewire\\{$plural};

use App\Http\Requests\\{$name}\\Store{$name}Request;
use App\Services\\{$name}Service;
{$foreignImports}use Livewire\Component;

class {$name}Create extends Component
{
{$properties}

    protected function rules(): array
    {
        return (new Store{$name}Request)->rules();
    }

    public function store({$name}Service \${$camelName}Service): mixed
    {
        abort_unless(auth()->user()->can('{$resource}.create'), 403);

        \$validated = \$this->validate();

        \${$camelName}Service->create(\$validated);

        session()->flash('success', __('{$label} created successfully.'));

        return redirect()->route('{$resource}.index');
    }

    public function render({$foreignParams})
    {
        return view('livewire.{$this->kebabPlural($plural)}.{$this->kebabName($name)}-create', [
{$foreignData}
        ])
            ->extends('layouts.app', ['topbarTitle' => 'New {$label}'])
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

    private function buildProperties(array $columnInputTypes): string
    {
        if (empty($columnInputTypes)) {
            return '    //';
        }

        $lines = [];
        foreach ($columnInputTypes as $column => $inputType) {
            if ($inputType === 'skip') {
                continue;
            }

            $camel = Str::camel($column);
            $default = $inputType === 'checkbox' ? 'false' : "''";
            $type = $inputType === 'checkbox' ? 'bool' : 'string';
            $lines[] = "    public {$type} \${$camel} = {$default};";
        }

        return implode("\n\n", $lines) ?: '    //';
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function buildForeignData(string $name, array $foreignKeys): array
    {
        if (empty($foreignKeys)) {
            return ['', '', ''];
        }

        $imports = '';
        $params = [];
        $data = [];
        $imported = [];

        foreach ($foreignKeys as $fk) {
            $relatedTable = $fk['table'];
            $relatedModel = Str::studly(Str::singular($relatedTable));

            if ($relatedModel === $name || in_array($relatedModel, $imported, true)) {
                continue;
            }

            $camelRelated = Str::camel($relatedModel);
            $imports .= "use App\\Services\\{$relatedModel}Service;\n";
            $params[] = "{$relatedModel}Service \${$camelRelated}Service";
            $data[] = "            '{$relatedTable}' => \${$camelRelated}Service->getAll(),";
            $imported[] = $relatedModel;
        }

        return [$imports, implode(', ', $params), implode("\n", $data)];
    }
}
