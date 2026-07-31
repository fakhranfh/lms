<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait TracksPublishedAt
{
    protected static function bootTracksPublishedAt(): void
    {
        static::saving(function (Model $model) {
            if ($model->isDirty('is_published')) {
                $model->setAttribute('published_at', $model->getAttribute('is_published') ? now() : null);
            }
        });
    }
}
