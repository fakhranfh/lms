<?php

namespace App\Console\Commands\Stubs;

class RepositoryStubGenerator
{
    public function generate(string $name, array $filterDefinitions = []): string
    {
        $filterConditions = $this->buildFilterConditions($filterDefinitions);

        return <<<PHP
<?php

namespace App\Repositories\\{$name};

use App\Models\\{$name};

class {$name}Repository implements {$name}RepositoryInterface
{
    public function query(array \$filters = [])
    {
        \$query = {$name}::query();

{$filterConditions}

        return \$query;
    }

    public function get(array \$filters = [], array \$with = [])
    {
        \$query = \$this->query(\$filters);

        return \$query->with(\$with)->get();
    }

    public function paginate(array \$filters = [], int \$perPage = 10, string \$sort = 'id', string \$direction = 'desc', array \$with = [])
    {
        \$query = \$this->query(\$filters);

        return \$query->with(\$with)->orderBy(\$sort, \$direction)->paginate(\$perPage)->withQueryString();
    }

    public function getAll()
    {
        return {$name}::all();
    }

    public function find(\$id)
    {
        return {$name}::find(\$id);
    }

    public function create(array \$data)
    {
        return {$name}::create(\$data);
    }

    public function update(\$id, array \$data)
    {
        \$model = {$name}::findOrFail(\$id);
        \$model->update(\$data);
        return \$model;
    }

    public function delete(\$id)
    {
        return {$name}::destroy(\$id);
    }
}
PHP;
    }

    private function buildFilterConditions(array $filterDefinitions): string
    {
        $lines = [];

        foreach ($filterDefinitions as $filter) {
            $key = $filter['key'];
            $dbColumn = $key === 'created' ? 'created_at' : $key;

            if ($filter['type'] === 'text') {
                $lines[] = "        if (! empty(\$filters['{$key}'])) {\n            \$query->where('{$dbColumn}', 'like', '%'.\$filters['{$key}'].'%');\n        }";
            } elseif ($filter['type'] === 'enum') {
                $lines[] = "        if (! empty(\$filters['{$key}'])) {\n            \$query->where('{$dbColumn}', \$filters['{$key}']);\n        }";
            } elseif ($filter['type'] === 'datetime') {
                $lines[] = "        if (! empty(\$filters['{$key}_from'])) {\n            \$query->whereDate('{$dbColumn}', '>=', \$filters['{$key}_from']);\n        }";
                $lines[] = "        if (! empty(\$filters['{$key}_to'])) {\n            \$query->whereDate('{$dbColumn}', '<=', \$filters['{$key}_to']);\n        }";
            }
        }

        return implode("\n\n", $lines);
    }
}
