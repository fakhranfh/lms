<?php

namespace App\Console\Commands\Generators;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class PermissionMigrationGenerator
{
    private Filesystem $filesystem;

    private string $migrationsPath;

    public function __construct()
    {
        $this->filesystem = new Filesystem;
        $this->migrationsPath = database_path('migrations');
    }

    public function generate(string $name, string $label, callable $callback): void
    {
        $resource = Str::snake(Str::plural($name));
        $group = Str::title(str_replace('_', ' ', Str::snake(Str::plural($label))));

        $fileName = Carbon::now()->format('Y_m_d_His').'_add_'.$resource.'_permissions_and_assign_to_admin.php';
        $filePath = "{$this->migrationsPath}/{$fileName}";

        $content = $this->generateContent($resource, $group);

        $this->filesystem->put($filePath, $content);
        $callback("Permission migration created: {$filePath}", 'info');
    }

    private function generateContent(string $resource, string $group): string
    {
        $labelBase = Str::title(str_replace('_', ' ', $resource));

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<string, array{label: string, group: string}>
     */
    private array \$permissions = [
        '{$resource}.view' => ['label' => 'View {$labelBase}', 'group' => '{$group}'],
        '{$resource}.create' => ['label' => 'Create {$labelBase}', 'group' => '{$group}'],
        '{$resource}.update' => ['label' => 'Update {$labelBase}', 'group' => '{$group}'],
        '{$resource}.delete' => ['label' => 'Delete {$labelBase}', 'group' => '{$group}'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        \$permissions = collect(\$this->permissions)
            ->map(fn (array \$attributes, string \$name) => Permission::updateOrCreate([
                'name' => \$name,
                'guard_name' => 'web',
            ], [
                'label' => \$attributes['label'],
                'group' => \$attributes['group'],
            ]));

        \$adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        \$adminRole->givePermissionTo(\$permissions);
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::whereIn('name', array_keys(\$this->permissions))->delete();
    }
};
PHP;
    }
}
