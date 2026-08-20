<?php

namespace App\Repositories\ProctorSnapshot;

use App\Enums\ProctorSnapshotType;
use App\Models\ProctorSnapshot;
use Illuminate\Database\Eloquent\Collection;

class ProctorSnapshotRepository implements ProctorSnapshotRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = ProctorSnapshot::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?ProctorSnapshot
    {
        return ProctorSnapshot::with($with)->find($id);
    }

    public function create(array $data): ProctorSnapshot
    {
        return ProctorSnapshot::create($data);
    }

    public function update(string $id, array $data): ProctorSnapshot
    {
        $model = ProctorSnapshot::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return ProctorSnapshot::destroy($id);
    }

    public function forSession(string $proctorSessionId): Collection
    {
        return ProctorSnapshot::where('proctor_session_id', $proctorSessionId)->orderBy('captured_at')->get();
    }

    public function screenshotEventTypesForSession(string $proctorSessionId): array
    {
        $types = ProctorSnapshot::query()
            ->join('proctor_events', 'proctor_events.id', '=', 'proctor_snapshots.triggered_by_event_id')
            ->where('proctor_snapshots.proctor_session_id', $proctorSessionId)
            ->where('proctor_snapshots.type', ProctorSnapshotType::Screen->value)
            ->selectRaw('proctor_events.event_type, MIN(proctor_snapshots.captured_at) as first_captured_at')
            ->groupBy('proctor_events.event_type')
            ->orderBy('first_captured_at')
            ->pluck('proctor_events.event_type')
            ->all();

        $hasUntriggered = ProctorSnapshot::query()
            ->where('proctor_session_id', $proctorSessionId)
            ->where('type', ProctorSnapshotType::Screen)
            ->whereNull('triggered_by_event_id')
            ->exists();

        if ($hasUntriggered) {
            $types[] = 'none';
        }

        return $types;
    }

    public function paginateScreenshotsForSession(
        string $proctorSessionId,
        ?string $eventType,
        string $sort,
        int $offset,
        int $limit,
    ): array {
        $query = ProctorSnapshot::query()
            ->where('proctor_session_id', $proctorSessionId)
            ->where('type', ProctorSnapshotType::Screen)
            ->with('triggeredByEvent');

        if ($eventType === 'none') {
            $query->whereNull('triggered_by_event_id');
        } elseif ($eventType !== null) {
            $query->whereHas('triggeredByEvent', fn ($eventQuery) => $eventQuery->where('event_type', $eventType));
        }

        $total = (clone $query)->count();

        $items = $query
            ->orderBy('captured_at', $sort === 'desc' ? 'desc' : 'asc')
            ->skip($offset)
            ->take($limit)
            ->get();

        $this->attachPairedWebcamSnapshots($items, $proctorSessionId);

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Screen and webcam snapshots for the same flagged moment share a
     * `triggered_by_event_id`, so pair each screen shot with its webcam
     * counterpart for the reviewer to see both stacked together.
     *
     * @param  Collection<int, ProctorSnapshot>  $screenSnapshots
     */
    private function attachPairedWebcamSnapshots(Collection $screenSnapshots, string $proctorSessionId): void
    {
        $eventIds = $screenSnapshots->pluck('triggered_by_event_id')->filter()->all();

        if ($eventIds === []) {
            return;
        }

        $webcamByEventId = ProctorSnapshot::query()
            ->where('proctor_session_id', $proctorSessionId)
            ->where('type', ProctorSnapshotType::Webcam)
            ->whereIn('triggered_by_event_id', $eventIds)
            ->get()
            ->keyBy('triggered_by_event_id');

        $screenSnapshots->each(function (ProctorSnapshot $snapshot) use ($webcamByEventId) {
            $snapshot->setRelation('pairedWebcam', $webcamByEventId->get($snapshot->triggered_by_event_id));
        });
    }
}
