<?php

namespace App\Models;

use App\Models\Concerns\HasViewerTimezoneDates;
use App\Traits\HasUuid;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

#[Fillable(['school_id', 'user_id', 'event', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'ip_address', 'user_agent', 'description'])]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory, HasUuid, HasViewerTimezoneDates;

    public const UPDATED_AT = null;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who made the change.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the audited model.
     *
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeForModel(Builder $query, Model $model): Builder
    {
        return $query->where('auditable_type', $model::class)->where('auditable_id', $model->getKey());
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('auditable_type', $type);
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeByUser(Builder $query, User|string $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeInTenant(Builder $query, ?string $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('Audit logs are immutable and cannot be updated.');
    }

    public function delete(): bool
    {
        throw new RuntimeException('Audit logs are immutable and cannot be deleted individually. Use AuditLogCleanupCommand for retention-based cleanup.');
    }
}
