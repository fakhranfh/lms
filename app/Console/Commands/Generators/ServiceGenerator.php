<?php

namespace App\Console\Commands\Generators;

use Illuminate\Filesystem\Filesystem;
use App\Console\Commands\Stubs\ServiceStubGenerator;

class ServiceGenerator
{
    private Filesystem $filesystem;
    private ServiceStubGenerator $stubGenerator;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->stubGenerator = new ServiceStubGenerator();
    }

    public function generate(string $name, string $label, callable $callback): void
    {
        $servicePath = app_path("Services/{$name}Service.php");

        $this->filesystem->ensureDirectoryExists(app_path('Services'));

        if (!$this->filesystem->exists($servicePath)) {
            $this->filesystem->put($servicePath, $this->stubGenerator->generate($name, $label));
            $callback("Service created: {$servicePath}", 'info');
        } else {
            $callback("Service already exists: {$servicePath}", 'warn');
        }
    }
}
