<?php

namespace App\Console\Commands\Stubs;

use App\Console\Commands\Helpers\SchemaHelper;
use Illuminate\Support\Str;

class LivewireEditStubGenerator
{
    public function generate(string $name, string $label, string $plural, string $resource, array $columnInputTypes = []): string
    {
        $camelName = Str::camel($name);
        $foreignKeys = SchemaHelper::getForeignKeys($name);

        $properties = $this->buildProperties($columnInputTypes);
        $mountAssignments = $this->buildMountAssignments($columnInputTypes, $camelName);
        [$foreignImports, $foreignParams, $foreignData] = $this->buildForeignData($name, $foreignKeys);

        return <<<PHP
<?php

namespace App\Livewire\\{$plural};

use App\Http\Requests\\{$name}\\Update{$name}Request;
use App\Models\\{$name};
use App\Services\\{$name}Service;
{$foreignImports}use Livewire\Component;

class {$name}Edit extends Component
{
    public {$name} \${$camelName};
{$properties}

    public function mount({$name} \${$camelName}): void
    {
        \$this->{$camelName} = \${$camelName};
{$mountAssignments}
    }

    protected function rules(): array
    {
        return (new Update{$name}Request)->rules();
    }

    public function update({$name}Service \${$camelName}Service): mixed
    {
        abort_unless(auth()->user()->can('{$resource}.update'), 403);

        \$validated = \$this->validate();

        \${$camelName}Service->update(\$this->{$camelName}->id, \$validated);

        session()->flash('success', __('{$label} updated successfully.'));

        return redirect()->route('{$resource}.index');
    }

    public function render({$foreignParams})
    {
        return view('livewire.{$this->kebabPlural($plural)}.{$this->kebabName($name)}-edit', [
{$foreignData}
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Edit {$label}'])
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
            return '';
        }

        $lines = [];
        foreach ($columnInputTypes as $column => $inputType) {
            if ($inputType === 'skip') {
                continue;
            }

            $camel = Str::camel($column);
            $type = $inputType === 'checkbox' ? 'bool' : 'string';
            $lines[] = "\n    public {$type} \${$camel};";
        }

        return implode('', $lines);
    }

    private function buildMountAssignments(array $columnInputTypes, string $camelName): string
    {
        if (empty($columnInputTypes)) {
            return '';
        }

        $lines = [];
        foreach ($columnInputTypes as $column => $inputType) {
            if ($inputType === 'skip') {
                continue;
            }

            $camel = Str::camel($column);
            $lines[] = "        \$this->{$camel} = \${$camelName}->{$column};";
        }

        return implode("\n", $lines);
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
