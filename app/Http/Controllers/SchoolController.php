<?php

namespace App\Http\Controllers;

use App\Http\Requests\School\StoreSchoolRequest;
use App\Services\SchoolService;
use Illuminate\Http\RedirectResponse;

class SchoolController extends Controller
{
    public function __construct(private SchoolService $schoolService) {}

    public function store(StoreSchoolRequest $request): RedirectResponse
    {
        $school = $this->schoolService->create($request->validated());

        $registerUrl = $this->schoolService->buildRegisterUrl(
            $school,
            $request->getScheme(),
            $request->getPort()
        );

        return redirect()->away($registerUrl)
            ->with('status', "School registered! Go to {$registerUrl} to create your account.");
    }
}
