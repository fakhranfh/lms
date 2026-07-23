<?php

use App\Http\Requests\AssignmentFormRequest;
use App\Http\Requests\SubmissionFormRequest;
use App\Models\Lesson;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

function validateAssignmentRequest(array $data): Illuminate\Contracts\Validation\Validator
{
    $request = AssignmentFormRequest::create('/', 'POST', $data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    return $validator;
}

test('assignment rejects passing_score greater than max_score', function () {
    $lesson = Lesson::factory()->create();

    $validator = validateAssignmentRequest([
        'lesson_id' => $lesson->id,
        'title' => 'Essay Assignment',
        'prompt_question' => 'Explain X.',
        'max_score' => 80,
        'passing_score' => 90,
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('passing_score'))->toBeTrue();
});

test('assignment accepts passing_score less than or equal to max_score', function () {
    $lesson = Lesson::factory()->create();

    $validator = validateAssignmentRequest([
        'lesson_id' => $lesson->id,
        'title' => 'Essay Assignment',
        'prompt_question' => 'Explain X.',
        'max_score' => 80,
        'passing_score' => 80,
    ]);

    expect($validator->fails())->toBeFalse();
});

test('submission rejects empty student_answer', function () {
    $request = new SubmissionFormRequest;
    $validator = Validator::make([
        'assignment_id' => (string) Str::uuid(),
        'user_id' => (string) Str::uuid(),
        'student_answer' => '',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('student_answer'))->toBeTrue();
});
