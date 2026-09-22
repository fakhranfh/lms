<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Livewire\Raport\Concerns\BuildsRaportViewData;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseService;
use App\Services\GradebookScoringService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RaportExportController extends Controller
{
    use BuildsRaportViewData;

    public function exportSelf(CourseService $courseService, GradebookScoringService $gradebookScoringService): StreamedResponse
    {
        abort_unless(auth()->user()->can('raport.view'), 403);

        $schoolId = auth()->user()->school_id;
        $courses = $courseService->get(['enrolled_user_id' => auth()->id(), 'school_id' => $schoolId]);

        return $this->download(auth()->user(), $courses, $gradebookScoringService);
    }

    public function exportStudent(
        User $student,
        CourseService $courseService,
        GradebookScoringService $gradebookScoringService,
    ): StreamedResponse {
        abort_unless(auth()->user()->can('raport.view'), 403);
        abort_unless(auth()->user()->hasRole(RoleName::SchoolAdmin), 403);

        $schoolId = auth()->user()->school_id;
        $courses = $courseService->get(['enrolled_user_id' => $student->id, 'school_id' => $schoolId]);

        return $this->download($student, $courses, $gradebookScoringService);
    }

    /**
     * @param  Collection<int, Course>  $courses
     */
    private function download(User $student, $courses, GradebookScoringService $gradebookScoringService): StreamedResponse
    {
        $entries = $courses->map(function (Course $course) use ($student, $gradebookScoringService) {
            $result = $gradebookScoringService->computeForUser($course, $student->id);

            return [
                'course' => $course,
                'finalScore' => $result['final']['score'],
                'finalGrade' => $this->letterGrade($course, $result['final']['score']),
                'typeRows' => $this->typeRows($result),
            ];
        })->values();

        $overallScore = $this->overallScore($entries);
        $overallGrade = $this->overallGrade($overallScore);

        $filename = 'raport-'.str($student->name)->slug().'-'.now()->format('Y-m-d').'.pdf';
        $output = Pdf::loadView('exports.raport-pdf', [
            'student' => $student,
            'courses' => $entries,
            'overallScore' => $overallScore,
            'overallGrade' => $overallGrade,
        ])->output();

        return Response::streamDownload(function () use ($output) {
            echo $output;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
