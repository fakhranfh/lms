<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait TracksPublishedAt
{
    protected static function bootTracksPublishedAt(): void
    {
        static::saving(function (Model $model) {
            if ($model->isDirty('is_published')) {
                $model->published_at = $model->is_published ? now() : null;
            }
        });
    }
}
