<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PermissionController extends Controller implements HasMiddleware
{
    public function __construct(protected PermissionService $permissionService) {}

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:permissions.view', only: ['index']),
        ];
    }

    public function index()
    {
        return view('app.permission.index', [
            'groupedPermissions' => $this->permissionService->getAllGrouped(),
        ]);
    }
}
