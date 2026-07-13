<?php

namespace App\Console\Commands\Generators;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MigrationGenerator
{
    private Filesystem $filesystem;

    private string $migrationsPath;

    public function __construct()
    {
        $this->filesystem = new Filesystem;
        $this->migrationsPath = database_path('migrations');
    }

    public function generate(string $tableName, array $columns, callable $callback): void
    {
        $fileName = Carbon::now()->format('Y_m_d_His').'_create_'.Str::snake(Str::plural($tableName)).'_table.php';
        $filePath = "{$this->migrationsPath}/{$fileName}";

        $className = 'Create'.Str::studly(Str::plural($tableName)).'Table';
        $content = $this->generateContent($className, Str::snake(Str::plural($tableName)), $columns);

        $this->filesystem->put($filePath, $content);
        $callback("Migration created: {$filePath}", 'info');
    }

    private function generateContent(string $className, string $tableName, array $columns): string
    {
        $columnDefinitions = $this->generateColumnDefinitions($columns);

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
{$columnDefinitions}
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;
    }

    private function generateColumnDefinitions(array $columns): string
    {
        $definitions = [];

        foreach ($columns as $column) {
            $definition = '            $table->'.$this->buildColumnDefinition($column);
            $definitions[] = $definition;
        }

        return implode("\n", $definitions);
    }

    private function buildColumnDefinition(array $column): string
    {
        $type = $column['type'];
        $name = $column['name'];
        $definition = "{$type}('{$name}'";

        // Add type-specific parameters
        if ($type === 'string' && ! empty($column['length'])) {
            $definition = "{$type}('{$name}', {$column['length']}";
        } elseif ($type === 'decimal' && ! empty($column['precision']) && ! empty($column['scale'])) {
            $definition = "{$type}('{$name}', {$column['precision']}, {$column['scale']}";
        } elseif ($type === 'enum' && ! empty($column['values'])) {
            $values = "'".implode("', '", $column['values'])."'";
            $definition = "{$type}('{$name}', [{$values}]";
        }

        $definition .= ')';

        // Add modifiers
        if ($column['nullable'] ?? false) {
            $definition .= '->nullable()';
        }

        if (isset($column['default']) && $column['default'] !== null && $column['default'] !== '') {
            if (is_bool($column['default'])) {
                $default = $column['default'] ? 'true' : 'false';
            } elseif (is_numeric($column['default'])) {
                $default = $column['default'];
            } else {
                $default = "'".$column['default']."'";
            }
            $definition .= "->default({$default})";
        }

        if ($column['unique'] ?? false) {
            $definition .= '->unique()';
        }

        if ($column['index'] ?? false) {
            $definition .= '->index()';
        }

        $definition .= ';';

        return $definition;
    }
}
