<?php

namespace App\Console\Commands\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class RouteGenerator
{
    private Filesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem;
    }

    public function generate(string $name, callable $callback): void
    {
        $plural = Str::studly(Str::plural($name));
        $resource = Str::snake(Str::plural($name));
        $webRoutePath = base_path('routes/web.php');
        $webRouteContent = $this->filesystem->get($webRoutePath);

        $this->addComponentImports($name, $plural, $webRoutePath, $webRouteContent, $callback);
        $this->appendRouteGroup($name, $plural, $resource, $webRoutePath, $callback);
    }

    private function addComponentImports(string $name, string $plural, string $webRoutePath, string &$webRouteContent, callable $callback): void
    {
        $importLines = [
            "use App\\Livewire\\{$plural}\\{$name}Create;",
            "use App\\Livewire\\{$plural}\\{$name}Edit;",
            "use App\\Livewire\\{$plural}\\{$name}Index;",
        ];

        if (strpos($webRouteContent, "use App\\Livewire\\{$plural}\\{$name}Index;") !== false) {
            return;
        }

        $lines = explode("\n", $webRouteContent);
        $lastUseIndex = -1;
        foreach ($lines as $i => $line) {
            if (preg_match('/^use\s+[\w\\\\]+;/', $line)) {
                $lastUseIndex = $i;
            }
        }

        if ($lastUseIndex !== -1) {
            array_splice($lines, $lastUseIndex + 1, 0, $importLines);
        } else {
            foreach ($lines as $i => $line) {
                if (strpos($line, '<?php') !== false) {
                    array_splice($lines, $i + 1, 0, $importLines);
                    break;
                }
            }
        }

        $webRouteContent = implode("\n", $lines);
        $this->filesystem->put($webRoutePath, $webRouteContent);
        $callback("Imports for {$name} Livewire components added to routes/web.php.", 'info');
    }

    private function appendRouteGroup(string $name, string $plural, string $resource, string $webRoutePath, callable $callback): void
    {
        $webRouteContent = $this->filesystem->get($webRoutePath);
        $camelName = Str::camel($name);

        $routeStub = <<<PHP

        Route::get('{$resource}', {$name}Index::class)->middleware('permission:{$resource}.view')->name('{$resource}.index');
        Route::get('{$resource}/create', {$name}Create::class)->middleware(['permission:{$resource}.view', 'permission:{$resource}.create'])->name('{$resource}.create');
        Route::get('{$resource}/{{$camelName}}/edit', {$name}Edit::class)->middleware(['permission:{$resource}.view', 'permission:{$resource}.update'])->name('{$resource}.edit');

    PHP;

        $pattern = '/Route::middleware\(\s*\[([^\]]*)\]\s*\)->group\(function\s*\(\)\s*{([\s\S]*?)^\s*}\);/m';
        if (preg_match($pattern, $webRouteContent, $matches, PREG_OFFSET_CAPTURE)) {
            $middlewareArray = $matches[1][0];
            $groupBody = $matches[2][0];

            if (strpos($middlewareArray, 'auth') !== false) {
                if (strpos($groupBody, "{$name}Index") === false) {
                    $insertPos = $matches[2][1] + strlen($groupBody);
                    $newContent = substr($webRouteContent, 0, $insertPos).$routeStub.substr($webRouteContent, $insertPos);
                    $this->filesystem->put($webRoutePath, $newContent);
                    $callback("Routes for {$name} appended to routes/web.php.", 'info');
                } else {
                    $callback("Routes for {$name} already exist in routes/web.php. Skipping append.", 'warn');
                }
            }
        }
    }
}
