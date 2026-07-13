<?php

namespace App\Console\Commands\Generators;

use App\Console\Commands\Stubs\ModelStubGenerator;
use Illuminate\Filesystem\Filesystem;

class ModelGenerator
{
    private Filesystem $filesystem;

    private ModelStubGenerator $stubGenerator;

    public function __construct()
    {
        $this->filesystem = new Filesystem;
        $this->stubGenerator = new ModelStubGenerator;
    }

    public function generate(string $name, array $columns, callable $callback): void
    {
        $modelPath = app_path("Models/{$name}.php");

        $this->filesystem->ensureDirectoryExists(app_path('Models'));

        if (! $this->filesystem->exists($modelPath)) {
            $this->filesystem->put($modelPath, $this->stubGenerator->generate($name, $columns));
            $callback("Model created: {$modelPath}", 'info');
        } else {
            $callback("Model already exists: {$modelPath}", 'warn');
        }
    }
}
