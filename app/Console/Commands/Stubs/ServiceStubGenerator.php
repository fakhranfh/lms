<?php

namespace App\Console\Commands\Stubs;

use Illuminate\Support\Str;

class ServiceStubGenerator
{
    public function generate(string $name, string $label): string
    {
        $camelCaseName = Str::camel($name);
        $labelKebab = Str::kebab($label);

        return <<<PHP
<?php

namespace App\Services;

use App\Repositories\\{$name}\\{$name}RepositoryInterface;

class {$name}Service
{
    protected \${$camelCaseName}Repository;

    public function __construct({$name}RepositoryInterface \${$camelCaseName}Repository)
    {
        \$this->{$camelCaseName}Repository = \${$camelCaseName}Repository;
    }

    public function get(array \$filters = [], array \$with = [])
    {
        return \$this->{$camelCaseName}Repository->get(\$filters, \$with);
    }

    public function paginate(array \$filters = [], int \$perPage = 10, string \$sort = 'id', string \$direction = 'desc', array \$with = [])
    {
        return \$this->{$camelCaseName}Repository->paginate(\$filters, \$perPage, \$sort, \$direction, \$with);
    }

    public function getAll()
    {
        return \$this->{$camelCaseName}Repository->getAll();
    }

    public function find(\$id)
    {
        return \$this->{$camelCaseName}Repository->find(\$id);
    }

    public function create(array \$data)
    {
        return \$this->{$camelCaseName}Repository->create(\$data);
    }

    public function update(\$id, array \$data)
    {
        return \$this->{$camelCaseName}Repository->update(\$id, \$data);
    }

    public function delete(\$id)
    {
        return \$this->{$camelCaseName}Repository->delete(\$id);
    }
}
PHP;
    }
}
