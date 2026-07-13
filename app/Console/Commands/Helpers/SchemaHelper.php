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
            $connection = Schema::getConnection();

            if (! method_exists($connection, 'getDoctrineConnection') || ! Schema::hasTable($table)) {
                return $foreignKeys;
            }

            $sm = $connection->getDoctrineSchemaManager();
            $doctrineTable = $sm->introspectTable($table);

            foreach ($doctrineTable->getForeignKeys() as $fk) {
                foreach ($fk->getLocalColumns() as $localCol) {
                    $foreignKeys[$localCol] = [
                        'table' => $fk->getForeignTableName(),
                        'column' => $fk->getForeignColumns()[0],
                    ];
                }
            }
        } catch (\Exception $e) {
            return $foreignKeys;
        }

        return $foreignKeys;
    }
}
