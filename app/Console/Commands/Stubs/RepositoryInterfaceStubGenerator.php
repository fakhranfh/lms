<?php

namespace App\Console\Commands\Stubs;

class RepositoryInterfaceStubGenerator
{
    public function generate(string $name): string
    {
        return <<<PHP
<?php

namespace App\Repositories\\{$name};

interface {$name}RepositoryInterface
{
    public function query(array \$filters = []);

    public function get(array \$filters = [], array \$with = []);

    public function paginate(array \$filters = [], int \$perPage = 10, string \$sort = 'id', string \$direction = 'desc', array \$with = []);

    public function getAll();

    public function find(\$id);

    public function create(array \$data);

    public function update(\$id, array \$data);

    public function delete(\$id);
}
PHP;
    }
}
