<?php

namespace App\Models;

use App\Enums\SyllabusPolicyScope;
use App\Traits\HasUuid;
use Database\Factories\SyllabusClassPolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['syllabus_id', 'scope', 'content', 'order'])]
class SyllabusClassPolicy extends Model
{
    /** @use HasFactory<SyllabusClassPolicyFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'scope' => SyllabusPolicyScope::class,
    ];

    /**
     * @return BelongsTo<Syllabus, $this>
     */
    public function syllabus(): BelongsTo
    {
        return $this->belongsTo(Syllabus::class);
    }
}
