<?php

namespace App\Console\Commands\Helpers;

use Illuminate\Support\Facades\Schema;

class SchemaHelper
{
    public static function getTableColumns(string $name): array
    {
        $modelClass = "App\\Models\\$name";
        if (! class_exists($modelClass)) {
            return [];
        }
        $model = new $modelClass;
        $table = $model->getTable();
        if (! Schema::hasTable($table)) {
            return [];
        }
        $columns = Schema::getColumnListing($table);
        $types = [];
        foreach ($columns as $col) {
            $types[$col] = Schema::getColumnType($table, $col);
        }

        return $types;
    }

    /**
     * @return array<string, array{table: string, column: string}>
     */
    public static function getForeignKeys(string $name): array
    {
        $foreignKeys = [];
        $modelClass = "App\\Models\\$name";

        if (! class_exists($modelClass)) {
            return $foreignKeys;
        }

        try {
            $model = new $modelClass;
            $table = $model->getTable();

            if (! Schema::hasTable($table)) {
                return $foreignKeys;
            }

            foreach (Schema::getForeignKeys($table) as $fk) {
                foreach ($fk['columns'] as $index => $localCol) {
                    $foreignKeys[$localCol] = [
                        'table' => $fk['foreign_table'],
                        'column' => $fk['foreign_columns'][$index],
                    ];
                }
            }
        } catch (\Exception $e) {
            return $foreignKeys;
        }

        return $foreignKeys;
    }
}
