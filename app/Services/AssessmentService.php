<?php

namespace App\Services;

use App\Models\Assessment;
use App\Repositories\Assessment\AssessmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssessmentService
{
    public function __construct(
        private AssessmentRepositoryInterface $assessmentRepository
    ) {}

    /**
     * @return Collection<int, Assessment>
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->assessmentRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?Assessment
    {
        return $this->assessmentRepository->find($id, $with);
    }

    public function create(array $data): Assessment
    {
        return $this->assessmentRepository->create($data);
    }

    public function update(string $id, array $data): Assessment
    {
        return $this->assessmentRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->assessmentRepository->delete($id);
    }

    /**
     * @return Collection<int, Assessment>
     */
    public function forCourse(string $courseId): Collection
    {
        return $this->assessmentRepository->forCourse($courseId);
    }

    public function moveOrder(string $assessmentId, string $direction): void
    {
        $assessment = $this->assessmentRepository->find($assessmentId);

        if (! $assessment) {
            return;
        }

        $siblings = $this->assessmentRepository->get([
            'course_id' => $assessment->course_id,
            'type' => $assessment->type->value,
        ])->values();

        $index = $siblings->search(fn (Assessment $a): bool => $a->id === $assessment->id);

        if ($index === false) {
            return;
        }

        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapIndex < 0 || $swapIndex >= $siblings->count()) {
            return;
        }

        $orderedIds = $siblings->pluck('id')->all();
        [$orderedIds[$index], $orderedIds[$swapIndex]] = [$orderedIds[$swapIndex], $orderedIds[$index]];

        $this->reorder($assessment->course_id, $assessment->type->value, $orderedIds);
    }

    /**
     * @param  array<int, string>  $orderedIds
     */
    public function reorder(string $courseId, string $type, array $orderedIds): void
    {
        $this->assessmentRepository->reorder($courseId, $type, $orderedIds);
    }
}
