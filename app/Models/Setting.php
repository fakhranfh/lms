<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    private const CACHE_PREFIX = 'setting:';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever(self::CACHE_PREFIX.$key, function () use ($key, $default) {
            return static::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget(self::CACHE_PREFIX.$key);
    }
}
