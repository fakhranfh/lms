<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\QuizQuestionOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quiz_question_id', 'label', 'is_correct', 'order'])]
class QuizQuestionOption extends Model
{
    /** @use HasFactory<QuizQuestionOptionFactory> */
    use HasFactory, HasUuid;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_correct' => 'boolean',
    ];

    /**
     * @return BelongsTo<QuizQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
