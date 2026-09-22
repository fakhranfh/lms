<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuditableObserver
{
    /**
     * Attributes that never count as a "change" on their own.
     *
     * @var list<string>
     */
    private const IGNORED_ATTRIBUTES = ['updated_at', 'created_at'];

    public function created(Model $model): void
    {
        $this->record($model, 'created', old: null, new: $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $this->relevantChanges($model->getChanges());

        if (empty($changes)) {
            return;
        }

        $original = array_intersect_key($model->getOriginal(), $changes);

        $this->record($model, 'updated', old: $original, new: $changes);
    }

    public function deleted(Model $model): void
    {
        $event = method_exists($model, 'trashed') && $model->trashed() ? 'deleted' : 'deleted';

        $this->record($model, $event, old: $model->getAttributes(), new: null);
    }

    public function restored(Model $model): void
    {
        $this->record($model, 'restored', old: null, new: $model->getAttributes());
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    private function record(Model $model, string $event, ?array $old, ?array $new): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $auditLog = AuditLog::create([
            'school_id' => $model->school_id ?? auth()->user()?->school_id,
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => sprintf('%s %s', class_basename($model), $event),
        ]);

        Log::channel('audit')->info('Model audited', [
            'audit_log_id' => $auditLog->id,
            'event' => $event,
            'model' => $model::class,
            'model_id' => $model->getKey(),
            'user_id' => auth()->id(),
            'changes' => $new,
        ]);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function relevantChanges(array $changes): array
    {
        return array_diff_key($changes, array_flip(self::IGNORED_ATTRIBUTES));
    }
}
