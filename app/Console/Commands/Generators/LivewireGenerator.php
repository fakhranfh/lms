<?php

namespace App\Console\Commands\Generators;

use App\Console\Commands\Helpers\SchemaHelper;
use App\Console\Commands\Stubs\LivewireCreateStubGenerator;
use App\Console\Commands\Stubs\LivewireCreateViewStubGenerator;
use App\Console\Commands\Stubs\LivewireEditStubGenerator;
use App\Console\Commands\Stubs\LivewireEditViewStubGenerator;
use App\Console\Commands\Stubs\LivewireIndexStubGenerator;
use App\Console\Commands\Stubs\LivewireIndexViewStubGenerator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class LivewireGenerator
{
    private Filesystem $filesystem;

    private LivewireIndexStubGenerator $indexStubGenerator;

    private LivewireCreateStubGenerator $createStubGenerator;

    private LivewireEditStubGenerator $editStubGenerator;

    private LivewireIndexViewStubGenerator $indexViewStubGenerator;

    private LivewireCreateViewStubGenerator $createViewStubGenerator;

    private LivewireEditViewStubGenerator $editViewStubGenerator;

    public function __construct()
    {
        $this->filesystem = new Filesystem;
        $this->indexStubGenerator = new LivewireIndexStubGenerator;
        $this->createStubGenerator = new LivewireCreateStubGenerator;
        $this->editStubGenerator = new LivewireEditStubGenerator;
        $this->indexViewStubGenerator = new LivewireIndexViewStubGenerator;
        $this->createViewStubGenerator = new LivewireCreateViewStubGenerator;
        $this->editViewStubGenerator = new LivewireEditViewStubGenerator;
    }

    public function generate(string $name, string $label, array $columnInputTypes, array $filterDefinitions, callable $callback): void
    {
        $plural = Str::studly(Str::plural($name));
        $pluralKebab = Str::kebab(Str::plural($name));
        $kebabName = Str::kebab($name);
        $resource = Str::snake(Str::plural($name));
        $foreignKeys = SchemaHelper::getForeignKeys($name);

        $componentDir = app_path("Livewire/{$plural}");
        $viewDir = resource_path("views/livewire/{$pluralKebab}");

        $this->filesystem->ensureDirectoryExists($componentDir);
        $this->filesystem->ensureDirectoryExists($viewDir);

        $this->putFile(
            "{$componentDir}/{$name}Index.php",
            $this->indexStubGenerator->generate($name, $label, $plural, $resource, $filterDefinitions),
            $callback
        );

        $this->putFile(
            "{$componentDir}/{$name}Create.php",
            $this->createStubGenerator->generate($name, $label, $plural, $resource, $columnInputTypes),
            $callback
        );

        $this->putFile(
            "{$componentDir}/{$name}Edit.php",
            $this->editStubGenerator->generate($name, $label, $plural, $resource, $columnInputTypes),
            $callback
        );

        $this->putFile(
            "{$viewDir}/{$kebabName}-index.blade.php",
            $this->indexViewStubGenerator->generate($name, $label, $resource, $columnInputTypes, $filterDefinitions),
            $callback
        );

        $this->putFile(
            "{$viewDir}/{$kebabName}-create.blade.php",
            $this->createViewStubGenerator->generate($label, $resource, $columnInputTypes, $foreignKeys),
            $callback
        );

        $this->putFile(
            "{$viewDir}/{$kebabName}-edit.blade.php",
            $this->editViewStubGenerator->generate($label, $resource, $columnInputTypes, $foreignKeys),
            $callback
        );

        $this->addSidebarItem($resource, $label, $callback);
    }

    private function putFile(string $path, string $content, callable $callback): void
    {
        if ($this->filesystem->exists($path)) {
            $callback("File already exists, skipped: {$path}", 'warn');

            return;
        }

        $this->filesystem->put($path, $content);
        $callback("File created: {$path}", 'info');
    }

    private function addSidebarItem(string $routeName, string $label, callable $callback): void
    {
        $configPath = config_path('sidebar.php');

        if (! $this->filesystem->exists($configPath)) {
            $callback("Sidebar config not found: {$configPath}", 'error');

            return;
        }

        $configContent = $this->filesystem->get($configPath);

        if (str_contains($configContent, "'{$routeName}.index'")) {
            $callback("Sidebar item for '{$label}' already exists", 'warn');

            return;
        }

        $icon = $this->getIconForRoute($routeName);
        $newItem = <<<PHP
            [
                'label' => '{$label}',
                'route' => '{$routeName}.index',
                'icon' => '{$icon}',
                'active_pattern' => '{$routeName}.*',
            ],
        PHP;

        $updatedContent = str_replace('];', "{$newItem}\n];", $configContent);

        $this->filesystem->put($configPath, $updatedContent);
        $callback("Sidebar item added for '{$label}'", 'info');
    }

    private function getIconForRoute(string $routeName): string
    {
        $iconMap = [
            'dashboard' => 'dashboard',
            'user' => 'person',
            'product' => 'shopping_cart',
            'order' => 'receipt',
            'category' => 'category',
            'setting' => 'settings',
            'report' => 'assessment',
            'profile' => 'account_circle',
        ];

        return $iconMap[$routeName] ?? 'folder';
    }
}
