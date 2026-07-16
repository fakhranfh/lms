<?php

namespace App\Http\Controllers;

use App\Models\DemoLmsAccess;
use App\Services\DemoLmsAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DemoLmsController extends Controller
{
    public function __construct(private DemoLmsAccessService $demoService) {}

    /**
     * Show demo LMS access information.
     */
    public function show(Request $request): View
    {
        $school = auth()->user()->school;

        $validAccess = DemoLmsAccess::where('school_id', $school->id)
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();

        return view('demo-lms.show', [
            'school' => $school,
            'demoAccess' => $validAccess,
        ]);
    }

    /**
     * Generate new demo credentials.
     */
    public function generate(Request $request): RedirectResponse
    {
        $school = auth()->user()->school;

        $demoAccess = $this->demoService->getOrCreateDemoAccess($school);

        return redirect()
            ->route('demo-lms.show')
            ->with('success', 'Demo access credentials generated successfully.');
    }

    /**
     * Auto-login with demo token.
     */
    public function login(Request $request, string $token): RedirectResponse
    {
        $demoAccess = DemoLmsAccess::where('access_token', $token)->first();

        if (! $demoAccess || ! $this->demoService->isDemoAccessValid($demoAccess)) {
            return redirect()
                ->route('login')
                ->with('error', 'Invalid or expired demo access token.');
        }

        $user = $demoAccess->user;

        Auth::login($user);

        $demoAccess->update(['accessed_at' => now()]);

        return redirect()->route('dashboard');
    }
}
