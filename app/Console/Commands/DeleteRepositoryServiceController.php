<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DeleteRepositoryServiceController extends Command
{
    protected $signature = 'delete:rsc {name} {--migrations}';

    protected $description = 'Delete all generated files for a Repository, Service, and Controller';

    public function handle(): int
    {
        $name = $this->argument('name');
        $plural = Str::studly(Str::plural($name));
        $pluralKebab = Str::kebab(Str::plural($name));

        if (! $this->confirm("Delete all files for '{$name}'? This cannot be undone.", false)) {
            $this->info('Deletion cancelled.');

            return 0;
        }

        $deleted = false;

        // Delete repositories
        $repoPath = app_path("Repositories/{$name}");
        if (File::exists($repoPath)) {
            File::deleteDirectory($repoPath);
            $this->info("✓ Deleted: Repositories/{$name}");
            $deleted = true;
        }

        // Delete model
        $modelPath = app_path("Models/{$name}.php");
        if (File::exists($modelPath)) {
            File::delete($modelPath);
            $this->info("✓ Deleted: Models/{$name}.php");
            $deleted = true;
        }

        // Delete service
        $servicePath = app_path("Services/{$name}Service.php");
        if (File::exists($servicePath)) {
            File::delete($servicePath);
            $this->info("✓ Deleted: Services/{$name}Service.php");
            $deleted = true;
        }

        // Delete Livewire components
        $livewirePath = app_path("Livewire/{$plural}");
        if (File::exists($livewirePath)) {
            File::deleteDirectory($livewirePath);
            $this->info("✓ Deleted: Livewire/{$plural}");
            $deleted = true;
        }

        // Delete form requests
        $requestPath = app_path("Http/Requests/{$name}");
        if (File::exists($requestPath)) {
            File::deleteDirectory($requestPath);
            $this->info("✓ Deleted: Http/Requests/{$name}");
            $deleted = true;
        }

        // Delete views
        $viewPath = resource_path("views/livewire/{$pluralKebab}");
        if (File::exists($viewPath)) {
            File::deleteDirectory($viewPath);
            $this->info("✓ Deleted: views/livewire/{$pluralKebab}");
            $deleted = true;
        }

        // Delete migrations if option provided
        if ($this->option('migrations')) {
            $tableName = Str::snake(Str::plural($name));
            $migrationPath = database_path('migrations');
            $migrations = File::files($migrationPath);

            foreach ($migrations as $migration) {
                if (strpos($migration->getFilename(), $tableName) !== false) {
                    File::delete($migration->getPathname());
                    $this->info("✓ Deleted: migrations/{$migration->getFilename()}");
                    $deleted = true;
                }
            }
        }

        // Remove from routes
        $this->removeFromRoutes($name, $plural);

        // Remove from service provider
        $this->removeFromServiceProvider($name);

        // Remove sidebar item
        $this->removeFromSidebar(Str::snake(Str::plural($name)));

        Artisan::call('optimize');

        if ($deleted) {
            $this->info('All files have been deleted successfully.');
        } else {
            $this->warn("No files found to delete for '{$name}'.");
        }

        return 0;
    }

    private function removeFromRoutes(string $name, string $plural): void
    {
        $routesPath = base_path('routes/web.php');

        if (! File::exists($routesPath)) {
            return;
        }

        $content = File::get($routesPath);
        $resource = Str::snake(Str::plural($name));

        // Remove the Index/Create/Edit route lines
        $pattern = "/^\\s*Route::get\\(['\"]".preg_quote($resource, '/')."[^\n]*".preg_quote($name, '/')."(Index|Create|Edit)::class[^\n]*\n/m";
        $updated = preg_replace($pattern, '', $content);

        // Remove component imports
        $pattern = '/use App\\\\Livewire\\\\'.preg_quote($plural, '/').'\\\\'.preg_quote($name, '/')."(Index|Create|Edit);?\s*\n?/";
        $updated = preg_replace($pattern, '', $updated);

        if ($updated !== $content) {
            File::put($routesPath, $updated);
            $this->info("✓ Removed routes and imports for '{$resource}'");
        }
    }

    private function removeFromServiceProvider(string $name): void
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');

        if (! File::exists($providerPath)) {
            return;
        }

        $content = File::get($providerPath);

        // Remove binding using ::class syntax
        $pattern = "/\\\$this->app->bind\(".preg_quote($name)."RepositoryInterface::class,\s*".preg_quote($name)."Repository::class\);?\s*\n?/";
        $updated = preg_replace($pattern, '', $content);

        // Also remove the import statement for the repository interface and class
        $updated = preg_replace('/use App\\\\Repositories\\\\'.preg_quote($name, '/').'\\\\'.preg_quote($name, '/')."RepositoryInterface;?\s*\n?/", '', $updated);
        $updated = preg_replace('/use App\\\\Repositories\\\\'.preg_quote($name, '/').'\\\\'.preg_quote($name, '/')."Repository;?\s*\n?/", '', $updated);

        if ($updated !== $content) {
            File::put($providerPath, $updated);
            $this->info("✓ Removed service provider binding for '{$name}'");
        }
    }

    private function removeFromSidebar(string $routeName): void
    {
        $sidebarPath = config_path('sidebar.php');

        if (! File::exists($sidebarPath)) {
            return;
        }

        $content = File::get($sidebarPath);

        // Remove the array entry block whose 'route' value matches routeName.index
        $pattern = '/\n\s*\[\n[^\]]*?\'route\'\s*=>\s*\''.preg_quote($routeName, '/').'\.index\'[^\]]*?\],?/s';
        $updated = preg_replace($pattern, '', $content);

        if ($updated !== $content) {
            File::put($sidebarPath, $updated);
            $this->info("✓ Removed sidebar entry for '{$routeName}'");
        }
    }
}
