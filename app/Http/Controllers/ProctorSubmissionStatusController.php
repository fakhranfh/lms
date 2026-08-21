<?php

namespace App\Http\Controllers;

use App\Enums\ProctorSessionStatus;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Services\ProctorSessionStatusService;
use App\Support\Sse;
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
    public function stream(Assessment $assessment, ProctorSessionStatusService $statusService): StreamedResponse
    {
        abort_unless(auth()->user()->can('assessment.view'), 403);
        abort_unless(auth()->user()->hasRole(RoleName::Student), 403);

        return Sse::response(function () use ($assessment, $statusService): void {
            set_time_limit(0);

            $maxIterations = 150;

            for ($i = 0; $i < $maxIterations; $i++) {
                if (connection_aborted()) {
                    break;
                }

                $session = $statusService->latestSessionForAssessment($assessment->id, auth()->id());
                $status = $session?->status;

                Sse::emitData(['status' => $status?->value]);

                if ($status !== ProctorSessionStatus::Submitting) {
                    break;
                }

                sleep(2);
            }
        });
    }
}
