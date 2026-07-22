<?php

namespace App\Http\Requests;

use App\Models\Assignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmissionFormRequest extends FormRequest
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
            'assignment_id' => ['required', 'uuid', 'exists:assignments,id'],
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'student_answer' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $assignment = Assignment::find($this->input('assignment_id'));
            $user = $this->user();

            if ($assignment === null || $user === null) {
                return;
            }

            if ($assignment->lesson?->module?->course?->school_id !== $user->school_id) {
                $validator->errors()->add('user_id', 'The user does not belong to the same school as this assignment.');
            }

            if (! $assignment->allow_multiple_submissions) {
                $hasGradedSubmission = $assignment->submissions()
                    ->where('user_id', $this->input('user_id'))
                    ->where('status', 'graded')
                    ->exists();

                if ($hasGradedSubmission) {
                    $validator->errors()->add('assignment_id', 'This assignment does not allow multiple submissions.');
                }
            }
        });
    }
}
