<?php

namespace App\Console\Commands\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use App\Console\Commands\Helpers\SchemaHelper;

class FormRequestGenerator
{
    private Filesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function generate(string $name, callable $callback): void
    {
        $requestPath = app_path("Http/Requests/{$name}/Store{$name}Request.php");
        $updateRequestPath = app_path("Http/Requests/{$name}/Update{$name}Request.php");

        $columns = SchemaHelper::getTableColumns($name);
        $rules = $this->generateRules($name, $columns);
        $rulesExport = $this->formatRulesForExport($rules);

        $this->filesystem->ensureDirectoryExists(dirname($requestPath));

        $storeStub = $this->getStoreStub($name, $rulesExport);
        $this->filesystem->put($requestPath, $storeStub);

        $updateStub = $this->getUpdateStub($name, $rulesExport);
        $this->filesystem->ensureDirectoryExists(dirname($updateRequestPath));
        $this->filesystem->put($updateRequestPath, $updateStub);

        $callback("Request rules generated for: {$requestPath} and {$updateRequestPath}", 'info');
    }

    private function generateRules(string $name, array $columns): array
    {
        $exclude = ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token'];
        $rules = [];

        foreach ($columns as $col => $type) {
            if (in_array($col, $exclude)) {
                continue;
            }

            $rule = [];
            $rule[] = $this->getRequiredRule($name, $col);
            $foreignKeyRule = $this->getForeignKeyRule($name, $col);

            if ($foreignKeyRule) {
                $rule[] = $foreignKeyRule;
            }

            $rule[] = $this->getTypeRule($type);
            $rules[$col] = implode('|', array_filter($rule));
        }

        return $rules;
    }

    private function getRequiredRule(string $name, string $col): string
    {
        $modelClass = "App\\Models\\$name";
        if (!class_exists($modelClass)) {
            return 'nullable';
        }

        try {
            $model = new $modelClass;
            $table = $model->getTable();
            $connection = Schema::getConnection();

            if (!method_exists($connection, 'getDoctrineColumn')) {
                return 'required';
            }

            $columnInfo = $connection->getDoctrineColumn($table, $col);
            $nullable = $columnInfo->getNotnull() ? false : true;
            return $nullable ? 'nullable' : 'required';
        } catch (\Exception $e) {
            return 'required';
        }
    }

    private function getForeignKeyRule(string $name, string $col): ?string
    {
        $modelClass = "App\\Models\\$name";
        if (!class_exists($modelClass)) {
            return null;
        }

        try {
            $model = new $modelClass;
            $table = $model->getTable();
            $connection = Schema::getConnection();

            if (!method_exists($connection, 'getDoctrineSchemaManager')) {
                return null;
            }

            $sm = $connection->getDoctrineSchemaManager();
            $doctrineTable = $sm->introspectTable($table);

            foreach ($doctrineTable->getForeignKeys() as $fk) {
                if (in_array($col, $fk->getLocalColumns())) {
                    $foreignTable = $fk->getForeignTableName();
                    $foreignColumn = $fk->getForeignColumns()[0];
                    return "exists:$foreignTable,$foreignColumn";
                }
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    private function getTypeRule(string $type): string
    {
        return match ($type) {
            'integer', 'bigint', 'smallint', 'tinyint' => 'integer',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime', 'timestamp' => 'date',
            'float', 'double', 'decimal' => 'numeric',
            'string', 'text' => 'string',
            default => '',
        };
    }

    private function formatRulesForExport(array $rules): string
    {
        $rulesExport = var_export($rules, true);
        $rulesExport = str_replace(['array (', ')'], ['[', ']'], $rulesExport);
        $rulesExport = preg_replace("/=>\s+/", "=> ", $rulesExport);
        $rulesExport = preg_replace("/\s+/", " ", $rulesExport);
        $rulesExport = preg_replace('/\',/', "',\n\t\t\t", $rulesExport);
        $rulesExport = preg_replace('/\[/', "[\n\t\t\t", $rulesExport);
        $rulesExport = preg_replace('/,\s*\]/', "\n\t\t]", $rulesExport);

        return $rulesExport;
    }

    private function getStoreStub(string $name, string $rulesExport): string
    {
        return <<<PHP
<?php

namespace App\Http\Requests\\{$name};

use Illuminate\Foundation\Http\FormRequest;

class Store{$name}Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return $rulesExport;
    }
}
PHP;
    }

    private function getUpdateStub(string $name, string $rulesExport): string
    {
        return <<<PHP
<?php

namespace App\Http\Requests\\{$name};

use Illuminate\Foundation\Http\FormRequest;

class Update{$name}Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return $rulesExport;
    }
}
PHP;
    }
}
