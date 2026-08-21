<?php

namespace App\Http\Controllers;

use App\Enums\ProctorSessionStatus;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Services\ProctorSessionStatusService;
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

        return response()->stream(function () use ($assessment, $statusService) {
            set_time_limit(0);

            $maxIterations = 150;

            for ($i = 0; $i < $maxIterations; $i++) {
                if (connection_aborted()) {
                    break;
                }

                $session = $statusService->latestSessionForAssessment($assessment->id, auth()->id());
                $status = $session?->status;

                echo 'data: '.json_encode(['status' => $status?->value])."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                if ($status !== ProctorSessionStatus::Submitting) {
                    break;
                }

                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
