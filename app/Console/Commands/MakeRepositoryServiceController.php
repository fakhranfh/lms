<?php

namespace App\Console\Commands;

use App\Console\Commands\Generators\FormRequestGenerator;
use App\Console\Commands\Generators\LivewireGenerator;
use App\Console\Commands\Generators\MigrationGenerator;
use App\Console\Commands\Generators\ModelGenerator;
use App\Console\Commands\Generators\PermissionMigrationGenerator;
use App\Console\Commands\Generators\RepositoryGenerator;
use App\Console\Commands\Generators\RouteGenerator;
use App\Console\Commands\Generators\ServiceGenerator;
use App\Console\Commands\Generators\ServiceProviderBindingGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeRepositoryServiceController extends Command
{
    protected $signature = 'make:rsc {name} {--label=} {--repository-service-only}';

    protected $description = 'Generate Repository, Service, and Controller for a given name';

    private array $columnInputTypes = [];

    private array $generatedFiles = [];

    private array $columns = [];

    private array $filterDefinitions = [];

    public function handle(): int
    {
        try {
            $name = $this->argument('name');
            $label = $this->option('label') ?: $name;

            // Record initial file state for rollback on error
            $fileSnapshot = $this->captureFileSnapshot($name);

            if ($this->confirm('Do you want to create a migration?', true)) {
                $this->generateMigration($name);
            }

            $this->generateModel($name, $this->columns);

            $this->generatePermissionMigration($name, $label);

            $this->generateService($name, $label);
            $this->bindToServiceProvider($name);

            if ($this->option('repository-service-only')) {
                return 0;
            }

            $this->generateFormRequests($name);

            // Configure form input types if not already done via migration
            if (empty($this->columnInputTypes)) {
                $this->configureFormInputTypes($name);
            }

            // Build filter definitions after all column info is available
            $this->filterDefinitions = $this->buildFilterDefinitions();

            $this->generateRepository($name);
            $this->generateLivewire($name, $label);
            $this->generateRoutes($name);

            // Clear cache after routes change
            Artisan::call('route:clear');
            $this->info('Route cache cleared.');

            Artisan::call('optimize');
            $this->info('php artisan optimize executed.');

            $this->info('');
            $this->info('<fg=green>✓ Generation completed successfully!</>');

            return 0;
        } catch (\Exception $e) {
            $this->error("Error occurred: {$e->getMessage()}");
            $this->error('Stack trace: '.$e->getTraceAsString());
            $this->warn('Cleaning up generated files...');
            $this->cleanupGeneratedFiles($name ?? null);
            $this->error('Generation failed and files have been cleaned up.');

            return 1;
        }
    }

    private function generateModel(string $name, array $columns = []): void
    {
        $generator = new ModelGenerator;
        $generator->generate($name, $columns, function (string $message, string $type) {
            $this->$type($message);
        });
    }

    private function generateRepository(string $name): void
    {
        $generator = new RepositoryGenerator;
        $generator->generate($name, $this->filterDefinitions, function (string $message, string $type) {
            $this->$type($message);
        });
    }

    private function generatePermissionMigration(string $name, string $label): void
    {
        $generator = new PermissionMigrationGenerator;
        $generator->generate($name, $label, function (string $message, string $type) {
            $this->$type($message);
        });

        if ($this->confirm('Run permission migration now?', true)) {
            Artisan::call('migrate');
            $this->info('Permission migration executed successfully.');
        } else {
            $this->info('Run <comment>php artisan migrate</comment> to execute the permission migration later.');
        }
    }

    private function generateService(string $name, string $label): void
    {
        $generator = new ServiceGenerator;
        $generator->generate($name, $label, function (string $message, string $type) {
            $this->$type($message);
        });
    }

    private function generateFormRequests(string $name): void
    {
        $generator = new FormRequestGenerator;
        $generator->generate($name, function (string $message, string $type) {
            $this->$type($message);
        });
    }

    private function generateRoutes(string $name): void
    {
        $generator = new RouteGenerator;
        $generator->generate($name, function (string $message, string $type) {
            $this->$type($message);
        });
    }

    private function bindToServiceProvider(string $name): void
    {
        $generator = new ServiceProviderBindingGenerator;
        $generator->generate($name, function (string $message, string $type) {
            $this->$type($message);
        });
    }

    private function generateLivewire(string $name, string $label): void
    {
        try {
            $generator = new LivewireGenerator;
            $generator->generate($name, $label, $this->columnInputTypes, $this->filterDefinitions, function (string $message, string $type) {
                $this->$type($message);
            });
            $this->info("✓ Livewire Index/Create/Edit components generated for {$name}");
        } catch (\Exception $e) {
            $this->error("Failed to generate Livewire components: {$e->getMessage()}");
            throw $e;
        }
    }

    private function buildFilterDefinitions(): array
    {
        $filters = [];

        if (! empty($this->columns)) {
            foreach ($this->columns as $col) {
                if ($col['type'] === 'enum') {
                    $options = array_combine($col['values'], array_map(
                        fn ($v) => Str::title(str_replace('_', ' ', $v)),
                        $col['values']
                    ));
                    $filters[] = [
                        'key' => $col['name'],
                        'label' => Str::title(str_replace('_', ' ', $col['name'])),
                        'type' => 'enum',
                        'options' => $options,
                    ];

                    continue;
                }

                $filterType = match ($col['type']) {
                    'string', 'text', 'longText', 'integer', 'bigInteger', 'smallInteger', 'decimal', 'float' => 'text',
                    'date', 'dateTime', 'timestamp' => 'datetime',
                    default => null,
                };

                if ($filterType !== null) {
                    $filters[] = [
                        'key' => $col['name'],
                        'label' => Str::title(str_replace('_', ' ', $col['name'])),
                        'type' => $filterType,
                    ];
                }
            }
        } elseif (! empty($this->columnInputTypes)) {
            foreach ($this->columnInputTypes as $colName => $inputType) {
                $filterType = match ($inputType) {
                    'text', 'email', 'url', 'tel', 'number', 'textarea' => 'text',
                    'date', 'datetime-local' => 'datetime',
                    default => null,
                };

                if ($filterType !== null) {
                    $filters[] = [
                        'key' => $colName,
                        'label' => Str::title(str_replace('_', ' ', $colName)),
                        'type' => $filterType,
                    ];
                }
            }
        }

        $filters[] = ['key' => 'created', 'label' => 'Created', 'type' => 'datetime'];

        return $filters;
    }

    private function generateMigration(string $name): void
    {
        $columns = [];

        $this->info('Define your table columns (type "done" when finished):');
        $this->info('Tip: You can type "back" in most prompts to undo the last column');

        while (true) {
            $this->newLine();
            $columnName = $this->ask('Column name (or "done" to finish, "back" to remove last column)');

            if (strtolower($columnName) === 'done') {
                break;
            }

            if (strtolower($columnName) === 'back') {
                if (empty($columns)) {
                    $this->warn('No columns to remove.');

                    continue;
                }

                $removed = array_pop($columns);
                $this->warn("Column '{$removed['name']}' removed.");

                continue;
            }

            if (empty($columnName)) {
                $this->warn('Column name cannot be empty.');

                continue;
            }

            $columnDetails = $this->askColumnDetails($columnName);
            if ($columnDetails === null) {
                continue;
            }

            $columns[] = $columnDetails;
            $this->info("<fg=green>✓ Column '{$columnName}' added</>");
        }

        if (empty($columns)) {
            $this->warn('No columns defined. Skipping migration creation.');

            return;
        }

        // Store columns and input types
        $this->columns = $columns;
        $this->columnInputTypes = array_reduce($columns, function ($carry, $column) {
            $carry[$column['name']] = $column['input_type'];

            return $carry;
        }, []);

        $generator = new MigrationGenerator;
        $generator->generate($name, $columns, function (string $message, string $type) {
            $this->$type($message);
        });

        if ($this->confirm('Run migration now?', true)) {
            $tableName = Str::snake(Str::plural($name));

            // Check if table already exists and drop it
            if (Schema::hasTable($tableName)) {
                $this->warn("Table '{$tableName}' already exists.");
                if ($this->confirm('Drop and recreate the table?', true)) {
                    Schema::drop($tableName);
                    $this->info("Table '{$tableName}' dropped.");
                } else {
                    $this->info('Skipping migration.');

                    return;
                }
            }

            Artisan::call('migrate');
            $this->info('Migration executed successfully.');
        } else {
            $this->info('Run <comment>php artisan migrate</comment> to execute the migration later.');
        }
    }

    private function askColumnDetails(string $columnName): ?array
    {
        $column = [
            'name' => $columnName,
        ];

        // Ask for column type
        $typeOptions = [
            'string',
            'integer',
            'bigInteger',
            'smallInteger',
            'decimal',
            'float',
            'boolean',
            'text',
            'longText',
            'date',
            'dateTime',
            'timestamp',
            'json',
            'enum',
        ];

        $columnType = $this->choice(
            'Column type',
            $typeOptions
        );

        if ($columnType === null) {
            return null;
        }

        $column['type'] = $columnType;

        // Type-specific options
        if ($columnType === 'string') {
            $length = $this->ask('String length (press Enter for default 255)', 255);
            $column['length'] = (int) $length;
        } elseif ($columnType === 'decimal') {
            $precision = $this->ask('Precision (total digits)', 8);
            $scale = $this->ask('Scale (decimal places)', 2);
            $column['precision'] = (int) $precision;
            $column['scale'] = (int) $scale;
        } elseif ($columnType === 'enum') {
            $values = [];
            $this->info('Enter enum values one by one. Leave blank to finish.');
            while (true) {
                $value = $this->ask('Enum value #'.(count($values) + 1).' (leave blank to finish)');
                if ($value === null || trim($value) === '') {
                    break;
                }
                $trimmed = trim($value);
                if (in_array($trimmed, $values)) {
                    $this->warn("Value \"{$trimmed}\" already added, skipping.");

                    continue;
                }
                $values[] = $trimmed;
            }
            $column['values'] = $values;
        }

        // Common options
        $column['nullable'] = $this->confirm('Nullable?', false);

        if ($this->confirm('Add default value?', false)) {
            $default = $this->ask('Default value');
            if ($columnType === 'boolean') {
                $column['default'] = strtolower($default) === 'true' || $default === '1';
            } else {
                $column['default'] = $default;
            }
        }

        if ($columnType !== 'json' && $columnType !== 'text' && $columnType !== 'longText') {
            $column['index'] = $this->confirm('Add index?', false);
        }

        if ($columnType === 'string') {
            $column['unique'] = $this->confirm('Add unique constraint?', false);
        }

        // Ask for form input type
        $column['input_type'] = $this->askForInputType($columnType, $columnName);

        // Verify column configuration
        $this->newLine();
        $this->info("Column configuration for '{$columnName}':");
        $this->info("  Type: {$column['type']}");
        if (isset($column['length'])) {
            $this->info("  Length: {$column['length']}");
        }
        if (isset($column['precision'])) {
            $this->info("  Precision: {$column['precision']} | Scale: {$column['scale']}");
        }
        if (isset($column['values'])) {
            $this->info('  Values: '.implode(', ', $column['values']));
        }
        $this->info('  Nullable: '.($column['nullable'] ? 'Yes' : 'No'));
        if (isset($column['default'])) {
            $this->info("  Default: {$column['default']}");
        }
        if (isset($column['index'])) {
            $this->info('  Index: '.($column['index'] ? 'Yes' : 'No'));
        }
        if (isset($column['unique'])) {
            $this->info('  Unique: '.($column['unique'] ? 'Yes' : 'No'));
        }
        $this->info("  Input Type: {$column['input_type']}");

        if (! $this->confirm('Confirm column configuration?', true)) {
            $this->warn("Column '{$columnName}' discarded.");

            return null;
        }

        return $column;
    }

    private function askForInputType(string $columnType, string $columnName): string
    {
        $inputTypeOptions = $this->getInputTypeOptionsForColumnType($columnType);

        return $this->choice(
            "Input type for '{$columnName}' field",
            $inputTypeOptions
        );
    }

    private function getColumnTypes(string $tableName): array
    {
        $columnTypes = [];

        try {
            $connection = Schema::getConnection();
            if (method_exists($connection, 'getDoctrineSchemaManager')) {
                $sm = $connection->getDoctrineSchemaManager();
                $doctrineTable = $sm->introspectTable($tableName);
                foreach ($doctrineTable->getColumns() as $column) {
                    $columnTypes[$column->getName()] = $column->getType()->getName();
                }
            }
        } catch (\Exception) {
            // If we can't get types from Doctrine, return empty array
        }

        return $columnTypes;
    }

    private function getInputTypeOptionsForColumnType(string $columnType): array
    {
        $options = match ($columnType) {
            'boolean' => ['radio', 'checkbox'],
            'text', 'longText' => ['textarea'],
            'date' => ['date'],
            'dateTime', 'timestamp' => ['datetime-local'],
            'integer', 'smallInteger', 'bigInteger' => ['number'],
            'decimal', 'float' => ['number'],
            'json' => ['textarea'],
            'enum' => ['select'],
            'string' => ['text', 'email', 'password', 'url', 'tel'],
            default => ['text'],
        };

        return array_merge(['skip'], $options);
    }

    private function configureFormInputTypes(string $name): void
    {
        $tableName = Str::snake(Str::plural($name));

        // Check if table exists in database
        if (! Schema::hasTable($tableName)) {
            $this->warn("Table '{$tableName}' not found in database. Skipping input type configuration.");

            return;
        }

        $this->info('Configure form input types for columns:');
        $this->info('Tip: You can change input type if you make a mistake');
        $columns = Schema::getColumnListing($tableName);
        $exclude = ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token', 'password'];

        // Get column types
        $columnTypes = [];
        $columnTypesMap = $this->getColumnTypes($tableName);
        foreach ($columns as $col) {
            if (! in_array($col, $exclude)) {
                $columnTypes[$col] = $columnTypesMap[$col] ?? 'string';
            }
        }

        // Ask for input type for each column with confirmation
        foreach ($columnTypes as $col => $type) {
            $colLabel = Str::title(str_replace('_', ' ', $col));

            while (true) {
                $inputType = $this->choice(
                    "Input type for '{$colLabel}' field",
                    $this->getInputTypeOptionsForColumnType($type)
                );

                if ($this->confirm("Set input type to '{$inputType}' for '{$colLabel}'?", true)) {
                    $this->columnInputTypes[$col] = $inputType;

                    break;
                }
            }
        }
    }

    private function captureFileSnapshot(string $name): array
    {
        $snapshot = [];
        $plural = Str::studly(Str::plural($name));
        $pluralKebab = Str::kebab(Str::plural($name));

        // Models
        $modelPath = app_path("Models/{$name}.php");
        if (File::exists($modelPath)) {
            $snapshot['models'] = [$modelPath];
        }

        // Repositories
        $repoPath = app_path("Repositories/{$name}");
        if (File::exists($repoPath)) {
            $snapshot['repositories'] = File::files($repoPath);
        }

        // Services
        $servicePath = app_path('Services');
        if (File::exists($servicePath)) {
            $snapshot['services'] = File::files($servicePath);
        }

        // Livewire components
        $livewirePath = app_path("Livewire/{$plural}");
        if (File::exists($livewirePath)) {
            $snapshot['livewire'] = File::files($livewirePath);
        }

        // Requests
        $requestPath = app_path("Http/Requests/{$name}");
        if (File::exists($requestPath)) {
            $snapshot['requests'] = File::files($requestPath);
        }

        // Views
        $viewsPath = resource_path("views/livewire/{$pluralKebab}");
        if (File::exists($viewsPath)) {
            $snapshot['views'] = File::files($viewsPath);
        }

        return $snapshot;
    }

    private function cleanupGeneratedFiles(?string $name): void
    {
        if (! $name) {
            return;
        }

        $plural = Str::studly(Str::plural($name));
        $pluralKebab = Str::kebab(Str::plural($name));

        // Delete model
        $modelPath = app_path("Models/{$name}.php");
        if (File::exists($modelPath)) {
            File::delete($modelPath);
            $this->info("Deleted: {$modelPath}");
        }

        // Delete repositories
        $repoPath = app_path("Repositories/{$name}");
        if (File::exists($repoPath)) {
            File::deleteDirectory($repoPath);
            $this->info("Deleted: {$repoPath}");
        }

        // Delete service
        $servicePath = app_path("Services/{$name}Service.php");
        if (File::exists($servicePath)) {
            File::delete($servicePath);
            $this->info("Deleted: {$servicePath}");
        }

        // Delete Livewire components
        $livewirePath = app_path("Livewire/{$plural}");
        if (File::exists($livewirePath)) {
            File::deleteDirectory($livewirePath);
            $this->info("Deleted: {$livewirePath}");
        }

        // Delete form requests
        $requestPath = app_path("Http/Requests/{$name}");
        if (File::exists($requestPath)) {
            File::deleteDirectory($requestPath);
            $this->info("Deleted: {$requestPath}");
        }

        // Delete views
        $viewsPath = resource_path("views/livewire/{$pluralKebab}");
        if (File::exists($viewsPath)) {
            File::deleteDirectory($viewsPath);
            $this->info("Deleted: {$viewsPath}");
        }
    }
}
