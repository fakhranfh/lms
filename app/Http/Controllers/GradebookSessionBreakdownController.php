<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Models\Course;
use App\Services\CoursePersonService;
use App\Services\GradebookScoringService;
use App\Support\CurrentSchool;
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
        CurrentSchool $currentSchool,
    ): JsonResponse {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
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

        $sessions = $gradebookScoringService->sessionBreakdownForType($course, $userId, $assessmentType);

        return response()->json([
            'sessions' => collect($sessions)->values()->map(fn (array $row, int $index) => [
                'index' => $index + 1,
                'delivery_mode' => str($row['session']->delivery_mode->value)->replace('_', ' ')->title()->toString(),
                'weight' => $row['weight'],
                'score' => $row['score'],
            ]),
        ]);
    }
}
