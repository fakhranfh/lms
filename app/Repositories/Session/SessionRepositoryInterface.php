<?php

namespace App\Repositories\Session;

use App\Models\Session;
use Illuminate\Database\Eloquent\Collection;

interface SessionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection;

    /**
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Session;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Session;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Session;

    public function delete(string $id): int;

    /**
     * @param  array<string>  $with
     * @return Collection<int, Session>
     */
    public function forCourse(string $courseId, array $with = []): Collection;
}
