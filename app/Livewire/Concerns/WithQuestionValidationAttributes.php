<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Str;

trait WithQuestionValidationAttributes
{
    /**
     * Builds validator "attributes" overrides for a `questions.*` repeater so
     * a failed-validation message reads "The question 2 points field is
     * required." rather than the raw dotted path "questions.1.points".
     *
     * @param  array<int, mixed>  $questions
     * @param  array<int, string>  $fields  Field names under each question, e.g. ['description', 'points'].
     * @return array<string, string>
     */
    protected function questionValidationAttributes(array $questions, array $fields): array
    {
        $attributes = [];

        foreach (array_keys($questions) as $index) {
            $number = $index + 1;

            foreach ($fields as $field) {
                $attributes["questions.{$index}.{$field}"] = "question {$number} ".Str::snake($field, ' ');
            }
        }

        return $attributes;
    }
}
