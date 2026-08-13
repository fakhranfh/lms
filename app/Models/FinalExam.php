<?php

namespace App\Models;

use App\Enums\FinalExamType;
use App\Traits\HasUuid;
use Database\Factories\FinalExamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assessment_id', 'period_id', 'exam_type', 'start_date', 'end_date', 'allow_local_files', 'allow_internet', 'instructions'])]
class FinalExam extends Model
{
    /** @use HasFactory<FinalExamFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'exam_type' => FinalExamType::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'allow_local_files' => 'boolean',
        'allow_internet' => 'boolean',
    ];

    /**
     * @return BelongsTo<Assessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * @return BelongsTo<Period, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
}
