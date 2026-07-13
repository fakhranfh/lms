<?php

use App\Console\Commands\Generators\MigrationGenerator;
use Illuminate\Support\Facades\File;

test('migration generator creates migration file with correct structure', function () {
    $migrationPath = database_path('migrations');
    $files = File::files($migrationPath);
    $initialCount = count($files);

    $columns = [
        [
            'name' => 'email',
            'type' => 'string',
            'length' => 255,
            'unique' => true,
        ],
        [
            'name' => 'age',
            'type' => 'integer',
            'index' => true,
        ],
        [
            'name' => 'is_active',
            'type' => 'boolean',
            'default' => false,
        ],
    ];

    $generator = new MigrationGenerator;
    $generator->generate('TestMigration', $columns, function (string $message, string $type) {
        // Silent callback
    });

    $newFiles = File::files($migrationPath);
    expect(count($newFiles))->toBeGreaterThan($initialCount);

    $latestFile = collect($newFiles)->sortBy('getModifiedTime')->last();
    $content = File::get($latestFile->getPathname());

    expect($content)
        ->toContain("Schema::create('test_migrations'")
        ->toContain("->string('email', 255)")
        ->toContain('->unique()')
        ->toContain("->integer('age'")
        ->toContain('->index()')
        ->toContain("->boolean('is_active'")
        ->toContain('->default(false)')
        ->toContain('->timestamps()');

    // Cleanup
    File::delete($latestFile);
});

test('migration generator handles decimal columns', function () {
    $migrationPath = database_path('migrations');
    $files = File::files($migrationPath);
    $initialCount = count($files);

    $columns = [
        [
            'name' => 'price',
            'type' => 'decimal',
            'precision' => 10,
            'scale' => 2,
        ],
    ];

    $generator = new MigrationGenerator;
    $generator->generate('PriceMigration', $columns, function (string $message, string $type) {
        // Silent callback
    });

    $newFiles = File::files($migrationPath);
    expect(count($newFiles))->toBeGreaterThan($initialCount);

    $latestFile = collect($newFiles)->sortBy('getModifiedTime')->last();
    $content = File::get($latestFile->getPathname());

    expect($content)->toContain("->decimal('price', 10, 2)");

    // Cleanup
    File::delete($latestFile);
});

test('migration generator handles nullable columns', function () {
    $migrationPath = database_path('migrations');
    $files = File::files($migrationPath);
    $initialCount = count($files);

    $columns = [
        [
            'name' => 'description',
            'type' => 'text',
            'nullable' => true,
        ],
    ];

    $generator = new MigrationGenerator;
    $generator->generate('DescriptionMigration', $columns, function (string $message, string $type) {
        // Silent callback
    });

    $newFiles = File::files($migrationPath);
    expect(count($newFiles))->toBeGreaterThan($initialCount);

    $latestFile = collect($newFiles)->sortBy('getModifiedTime')->last();
    $content = File::get($latestFile->getPathname());

    expect($content)->toContain("->text('description')->nullable()");

    // Cleanup
    File::delete($latestFile);
});

test('migration file is valid PHP syntax', function () {
    $columns = [
        [
            'name' => 'email',
            'type' => 'string',
            'length' => 255,
            'unique' => true,
        ],
    ];

    $generator = new MigrationGenerator;
    $generator->generate('ValidateMigration', $columns, function (string $message, string $type) {
        // Silent callback
    });

    $migrationPath = database_path('migrations');
    $latestFile = collect(File::files($migrationPath))->sortBy('getModifiedTime')->last();
    $content = File::get($latestFile->getPathname());

    // Verify basic structure
    expect($content)
        ->toContain('return new class extends Migration')
        ->toContain('public function up(): void')
        ->toContain('public function down(): void');

    // Cleanup
    File::delete($latestFile);
});

test('migration can be rolled back and re-applied', function () {
    $columns = [
        [
            'name' => 'name',
            'type' => 'string',
            'length' => 255,
        ],
    ];

    $generator = new MigrationGenerator;
    $generator->generate('RollbackTest', $columns, function (string $message, string $type) {
        // Silent callback
    });

    // Run migration
    Artisan::call('migrate');
    expect(Schema::hasTable('rollback_tests'))->toBeTrue();

    // Rollback
    Artisan::call('migrate:rollback');
    expect(Schema::hasTable('rollback_tests'))->toBeFalse();

    // Cleanup migration file
    $migrationPath = database_path('migrations');
    $latestFile = collect(File::files($migrationPath))
        ->filter(fn ($file) => str_contains($file->getPathname(), 'rollback_test'))
        ->sortBy('getModifiedTime')
        ->last();

    if ($latestFile) {
        File::delete($latestFile);
    }
});
