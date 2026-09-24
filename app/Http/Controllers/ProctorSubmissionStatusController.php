<?php

namespace App\Http\Controllers;

use App\Enums\ProctorSessionStatus;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Services\ProctorSessionService;
use App\Services\ProctorSessionStatusService;
use App\Support\Sse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProctorSubmissionStatusController extends Controller
{
    /**
     * Streams the proctor session status for the authenticated student's
     * latest attempt on this assessment as Server-Sent Events, so the
     * "processing submission" screen (shown while an exam submission or
     * disqualification is being finalized by a queued job) can move on the
     * moment that job finishes, without polling from the client.
     */
    public function stream(Assessment $assessment, ProctorSessionStatusService $statusService, ProctorSessionService $proctorSessionService): StreamedResponse
    {
        $this->authorizeAccess();

        return Sse::response(function () use ($assessment, $statusService, $proctorSessionService): void {
            set_time_limit(0);

            $maxIterations = 150;

            for ($i = 0; $i < $maxIterations; $i++) {
                if (connection_aborted()) {
                    break;
                }

                $session = $statusService->latestSessionForAssessment($assessment->id, auth()->id());
                $submitting = $session !== null && $proctorSessionService->isSubmitting($session->id);
                $status = $submitting ? ProctorSessionStatus::Submitting : $session?->status;

                Sse::emitData(['status' => $status?->value]);

                if (! $submitting) {
                    break;
                }

                sleep(2);
            }
        });
    }

    /**
     * JSON equivalent of stream(), used by clients that poll via AJAX when
     * SSE is disabled (FEATURE_SSE_ENABLED=false), since some hosting setups
     * don't support long-lived streamed connections.
     */
    public function status(Assessment $assessment, ProctorSessionStatusService $statusService, ProctorSessionService $proctorSessionService): JsonResponse
    {
        $this->authorizeAccess();

        return response()->json([
            'status' => $this->resolveStatus($assessment, $statusService, $proctorSessionService)?->value,
        ]);
    }

    private function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('assessment.view'), 403);
        abort_unless(auth()->user()->hasRole(RoleName::Student), 403);
    }

    private function resolveStatus(Assessment $assessment, ProctorSessionStatusService $statusService, ProctorSessionService $proctorSessionService): ?ProctorSessionStatus
    {
        $session = $statusService->latestSessionForAssessment($assessment->id, auth()->id());
        $submitting = $session !== null && $proctorSessionService->isSubmitting($session->id);

        return $submitting ? ProctorSessionStatus::Submitting : $session?->status;
    }
}
