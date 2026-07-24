<?php

namespace App\Http\Controllers;

use App\Repositories\DemoLmsAccess\DemoLmsAccessRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private DemoLmsAccessRepositoryInterface $demoLmsAccessRepository,
    ) {}

    public function logout(): RedirectResponse
    {
        $isDemoUser = $this->demoLmsAccessRepository->existsForUser(Auth::id());

        $this->authService->logout();

        return redirect()->route($isDemoUser ? 'try-demo' : 'login');
    }
}
