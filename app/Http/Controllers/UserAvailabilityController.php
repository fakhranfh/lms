<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\Support\CurrentSchool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class UserAvailabilityController extends Controller implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:users.create|users.edit'),
        ];
    }

    /**
     * Check whether a name or email is already taken, for client-side (JS)
     * live validation on the user create/edit form.
     */
    public function check(Request $request, UserService $userService, CurrentSchool $currentSchool): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', Rule::in(['name', 'email'])],
            'value' => ['required', 'string', 'max:255'],
            'ignore_id' => ['nullable', 'string'],
        ]);

        $ignoreId = $validated['ignore_id'] ?? null;

        // auth()->user()->school_id only reflects school_user membership;
        // School Admins are attached via a separate school_admins pivot and
        // would otherwise resolve to null, hiding the "another school" message.
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;

        $message = $validated['field'] === 'email'
            ? $userService->emailConflictMessage($validated['value'], $schoolId, $ignoreId)
            : $userService->nameConflictMessage($validated['value'], $ignoreId);

        return response()->json(['available' => $message === null, 'message' => $message]);
    }
}
