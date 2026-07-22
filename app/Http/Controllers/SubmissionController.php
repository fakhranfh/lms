<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Http\Requests\OverrideScoreFormRequest;
use App\Http\Requests\SubmissionFormRequest;
use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Http\JsonResponse;

class SubmissionController extends Controller
{
    public function __construct(
        private readonly SubmissionService $submissionService
    ) {}

    public function store(SubmissionFormRequest $request): JsonResponse
    {
        $submission = $this->submissionService->submit($request->validated());

        // TODO: dispatch GradeSubmissionJob (Phase 1.4)

        return response()->json([
            'data' => $submission,
        ], 201);
    }

    public function show(Submission $submission): JsonResponse
    {
        $user = auth()->user();

        abort_unless(
            $submission->user_id === $user->id
                || $user->can('submissions.grade')
                || $user->can('submissions.view'),
            403
        );

        return response()->json([
            'data' => $submission->only(['id', 'status', 'ai_score', 'ai_feedback', 'instructor_score', 'instructor_feedback', 'graded_at']),
        ]);
    }

    public function override(Submission $submission, OverrideScoreFormRequest $request): JsonResponse
    {
        $updated = $this->submissionService->overrideScore(
            $submission->id,
            (float) $request->validated('instructor_score'),
            $request->validated('instructor_feedback'),
            $request->user()
        );

        return response()->json([
            'data' => $updated,
        ]);
    }

    public function retry(Submission $submission): JsonResponse
    {
        abort_unless(auth()->user()->can('submissions.grade'), 403);

        if ($submission->status !== SubmissionStatus::Failed) {
            return response()->json([
                'message' => 'Only failed submissions can be retried.',
            ], 422);
        }

        // NOTE: no automatic redispatch to a grading job exists yet (Phase 1.4). This
        // resets status to pending only; the job dispatch is deferred like store().
        $updated = $this->submissionService->update($submission->id, [
            'status' => SubmissionStatus::Pending,
        ]);

        // TODO: redispatch GradeSubmissionJob (Phase 1.4)

        return response()->json([
            'data' => $updated,
        ]);
    }
}
