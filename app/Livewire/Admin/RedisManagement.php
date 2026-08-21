<?php

namespace App\Livewire\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Redis;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class RedisManagement extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public ?string $selectedKey = null;

    public ?string $selectedType = null;

    public ?int $selectedTtl = null;

    public string $editValue = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->successMessage = session('success');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function selectKey(string $key): void
    {
        $this->selectedKey = $key;
        $this->errorMessage = null;
        $this->successMessage = null;

        if (! Redis::exists($key)) {
            $this->errorMessage = __('Key no longer exists.');
            $this->selectedKey = null;

            return;
        }

        $this->selectedType = $this->typeLabel(Redis::type($key));
        $ttl = Redis::ttl($key);
        $this->selectedTtl = $ttl >= 0 ? $ttl : null;
        $this->editValue = $this->readValue($key, $this->selectedType);
    }

    public function closeKey(): void
    {
        $this->selectedKey = null;
        $this->selectedType = null;
        $this->selectedTtl = null;
        $this->editValue = '';
    }

    public function save(): void
    {
        if (! $this->selectedKey || $this->selectedType !== 'string') {
            $this->errorMessage = __('Only string values can be edited.');

            return;
        }

        $ttl = Redis::ttl($this->selectedKey);
        Redis::set($this->selectedKey, $this->editValue);

        if ($ttl > 0) {
            Redis::expire($this->selectedKey, $ttl);
        }

        $this->successMessage = __('Key updated successfully.');
    }

    public function deleteKey(string $key): void
    {
        Redis::del($key);

        if ($this->selectedKey === $key) {
            $this->closeKey();
        }

        $this->successMessage = __('Key deleted successfully.');
    }

    public function flushDatabase(): void
    {
        Redis::flushdb();
        $this->closeKey();
        $this->resetPage();

        $this->successMessage = __('Redis database flushed successfully.');
    }

    /**
     * @return array<int, string>
     */
    private function matchingKeys(): array
    {
        $pattern = $this->search !== '' ? "*{$this->search}*" : '*';

        return collect(Redis::keys($pattern))
            ->map(fn (string $key) => $this->stripPrefix($key))
            ->sort()
            ->values()
            ->all();
    }

    private function stripPrefix(string $key): string
    {
        $prefix = config('database.redis.options.prefix', '');

        return $prefix !== '' && str_starts_with($key, $prefix)
            ? substr($key, strlen($prefix))
            : $key;
    }

    private function typeLabel(int|string $type): string
    {
        if (is_string($type)) {
            return $type;
        }

        return match ($type) {
            1 => 'string',
            2 => 'set',
            3 => 'list',
            4 => 'zset',
            5 => 'hash',
            6 => 'stream',
            default => 'none',
        };
    }

    private function readValue(string $key, string $type): string
    {
        return match ($type) {
            'string' => (string) Redis::get($key),
            'list' => json_encode(Redis::lrange($key, 0, -1), JSON_PRETTY_PRINT),
            'set' => json_encode(Redis::smembers($key), JSON_PRETTY_PRINT),
            'zset' => json_encode(Redis::zrange($key, 0, -1, ['withscores' => true]), JSON_PRETTY_PRINT),
            'hash' => json_encode(Redis::hgetall($key), JSON_PRETTY_PRINT),
            default => '',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function serverInfo(): array
    {
        $info = Redis::info();
        $keyspace = $info['db'.config('database.redis.default.database', 0)] ?? null;

        return [
            'used_memory_human' => $info['used_memory_human'] ?? '-',
            'connected_clients' => $info['connected_clients'] ?? '-',
            'uptime_in_days' => $info['uptime_in_days'] ?? '-',
            'redis_version' => $info['redis_version'] ?? '-',
            'total_keys' => is_string($keyspace) ? (preg_match('/keys=(\d+)/', $keyspace, $m) ? $m[1] : 0) : 0,
        ];
    }

    public function render()
    {
        $keys = $this->matchingKeys();
        $page = $this->getPage();
        $perPage = 25;

        $paginator = new LengthAwarePaginator(
            array_slice($keys, ($page - 1) * $perPage, $perPage),
            count($keys),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );

        return view('livewire.admin.redis-management', [
            'keys' => $paginator,
            'info' => $this->serverInfo(),
        ])
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
