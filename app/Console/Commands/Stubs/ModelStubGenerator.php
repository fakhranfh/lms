<?php

namespace App\Console\Commands\Stubs;

use Illuminate\Support\Str;

class ModelStubGenerator
{
    public function generate(string $name, array $columns = []): string
    {
        $singular = Str::singular($name);
        $tableName = Str::snake(Str::plural($name));

        // Filter columns: exclude id, timestamps, and soft deletes
        $exclude = ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token', 'password'];
        $fillables = array_filter(
            array_column($columns, 'name'),
            fn ($col) => ! in_array($col, $exclude)
        );

        $fillableList = ! empty($fillables) ? "'".implode("', '", $fillables)."'" : '';

        return <<<PHP
<?php

namespace App\Models;

use App\Models\Concerns\HasViewerTimezoneDates;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([$fillableList])]
class {$singular} extends Model
{
    /** @use HasFactory<\\Database\\Factories\\{$singular}Factory> */
    use HasFactory, HasViewerTimezoneDates;

    protected \$table = '{$tableName}';
}
PHP;
    }
}
