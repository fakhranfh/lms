<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignmentFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lesson_id' => ['required', 'uuid', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:255'],
            'prompt_question' => ['required', 'string'],
            'rubric' => ['nullable', 'array'],
            'max_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'passing_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_published' => ['boolean'],
            'allow_multiple_submissions' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $maxScore = $this->input('max_score');
            $passingScore = $this->input('passing_score');

            if ($passingScore !== null && $maxScore !== null && (float) $passingScore > (float) $maxScore) {
                $validator->errors()->add('passing_score', 'The passing score must not be greater than the max score.');
            }
        });
    }
}
