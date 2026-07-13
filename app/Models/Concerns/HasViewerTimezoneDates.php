<?php

namespace App\Models\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Exposes a `{column}_display` virtual attribute for any Carbon-castable
 * column (e.g. `created_at_display`), returning it converted to the
 * viewing user's timezone. The underlying `{column}` attribute is left
 * untouched and keeps returning the value in config('app.timezone').
 */
trait HasViewerTimezoneDates
{
    public function getAttribute($key)
    {
        if (is_string($key) && Str::endsWith($key, '_display')) {
            $column = Str::beforeLast($key, '_display');
            $value = parent::getAttribute($column);

            if ($value instanceof CarbonInterface) {
                return $value->clone()->setTimezone($this->viewerTimezone());
            }

            return $value;
        }

        return parent::getAttribute($key);
    }

    protected function viewerTimezone(): string
    {
        return auth()->user()?->timezone ?: config('app.timezone');
    }
}
