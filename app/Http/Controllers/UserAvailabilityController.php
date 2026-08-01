<?php

namespace App\Http\Controllers;

use App\Models\Scopes\SchoolScope;
use App\Models\User;
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
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', Rule::in(['name', 'email'])],
            'value' => ['required', 'string', 'max:255'],
            'ignore_id' => ['nullable', 'string'],
        ]);

        // Matches the scope of the server-side Rule::unique check on save
        // (global uniqueness, excluding soft-deleted users), not just the
        // current school, so the live JS check never disagrees with submit.
        $exists = User::withoutGlobalScope(SchoolScope::class)
            ->where($validated['field'], $validated['value'])
            ->when($validated['ignore_id'] ?? null, fn ($query, $id) => $query->where('id', '!=', $id))
            ->exists();

        return response()->json(['available' => ! $exists]);
    }
}
