<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * @property string $id
 */
trait HasUuid
{
    /**
     * Boot the trait and attach the UUID generation hook.
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model): void {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Initialize the trait for an instance.
     */
    public function initializeHasUuid(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }
}
