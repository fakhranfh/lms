<?php

namespace App\Console\Commands;

use App\Models\LessonMaterial;
use App\Services\LessonMaterialService;
use Illuminate\Console\Command;

class DeleteR2Material extends Command
{
    protected $signature = 'r2:delete-material {material-id : The ID of the material to delete}';

    protected $description = 'Delete a material from R2 (use with caution!)';

    public function handle(): int
    {
        $materialId = $this->argument('material-id');

        try {
            $material = LessonMaterial::findOrFail($materialId);

            $this->warn('⚠️  You are about to delete this material:');
            $this->line('');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Material ID', $material->id],
                    ['Title', $material->title],
                    ['Type', $material->type->value],
                    ['File URL', $material->file_url],
                ]
            );

            if (! $this->confirm('Are you sure you want to delete this material from R2?')) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }

            $this->line('');
            $this->info('🗑️  Deleting from R2...');

            $service = app(LessonMaterialService::class);
            $service->delete($materialId);

            $this->info('✅ Material deleted successfully!');
            $this->line('');
            $this->info('This file has been removed from:');
            $this->line('  - R2 storage');
            $this->line('  - Database');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
