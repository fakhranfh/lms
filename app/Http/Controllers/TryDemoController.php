<?php

namespace App\Http\Controllers;

use App\Repositories\School\SchoolRepositoryInterface;
use App\Services\DemoLmsAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TryDemoController extends Controller
{
    public function __construct(
        private DemoLmsAccessService $demoService,
        private SchoolRepositoryInterface $schoolRepository,
    ) {}

    /**
     * Show the "try demo" role picker.
     */
    public function index(): View
    {
        return view('try-demo');
    }

    /**
     * Generate (or reuse) a demo access link for the given role and redirect to it.
     */
    public function login(Request $request, string $role): RedirectResponse
    {
        $school = $this->schoolRepository->findByDomain('school.'.config('app.domain'));

        abort_if(! $school, 404);

        $access = $this->demoService->getOrCreateDemoAccess($school, $role);

        return redirect()->away(
            $access->getLoginUrl($request->getScheme(), $request->getPort())
        );
    }
}
