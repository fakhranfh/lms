<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Models\Course;
use App\Services\CoursePersonService;
use App\Services\GradebookScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradebookSessionBreakdownController extends Controller
{
    /**
     * Lazily loaded by the Gradebook accordion's JS on expand, so expanding
     * a type row doesn't require a full Livewire round-trip.
     */
    public function __invoke(
        Request $request,
        Course $course,
        string $type,
        GradebookScoringService $gradebookScoringService,
        CoursePersonService $coursePersonService,
    ): JsonResponse {
        $schoolId = auth()->user()->school_id;
        abort_unless(auth()->user()->can('gradebook.view') && $course->school_id === $schoolId, 403);

        $assessmentType = AssessmentType::tryFrom($type);
        abort_if($assessmentType === null, 404);

        $isStudent = auth()->user()->hasRole(RoleName::Student);
        $userId = auth()->id();

        if (! $isStudent) {
            abort_unless(auth()->user()->can('gradebook.manage'), 403);

            $requestedUserId = $request->query('student_id');

            if ($requestedUserId) {
                abort_unless($coursePersonService->isEnrolledAsStudent($course->id, $requestedUserId), 404);
                $userId = $requestedUserId;
            }
        }

        if (in_array($assessmentType, [AssessmentType::Attendance, AssessmentType::ForumDiscussion], true)) {
            $sessions = $gradebookScoringService->sessionBreakdownForType($course, $userId, $assessmentType);

            return response()->json([
                'items' => collect($sessions)->values()->map(fn (array $row) => [
                    'label' => 'Session '.$row['session']->order.' - '.str($row['session']->delivery_mode->value)->replace('_', ' ')->title(),
                    'weight' => $row['weight'],
                    'score' => $row['score'],
                ]),
            ]);
        }

        $assessments = $gradebookScoringService->assessmentBreakdownForType($course, $userId, $assessmentType);

        return response()->json([
            'items' => collect($assessments)->values()->map(fn (array $row) => [
                'label' => $row['assessment']->title,
                'weight' => $row['weight'],
                'score' => $row['score'],
            ]),
        ]);
    }
}
