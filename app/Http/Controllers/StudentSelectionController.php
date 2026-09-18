<?php

namespace App\Http\Controllers;

use App\Services\StudentSelectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backs the Students index bulk-select checkboxes. Called via plain fetch()
 * from the page so checking/unchecking a row stays instant and decoupled
 * from Livewire's full component re-render cycle, while the selection
 * itself is persisted in Redis so it survives pagination and page refreshes.
 * Permission is enforced by the `permission:students.view` route middleware.
 */
class StudentSelectionController extends Controller
{
    public function show(StudentSelectionService $studentSelectionService): JsonResponse
    {
        return response()->json(['selected' => $studentSelectionService->all(auth()->id())]);
    }

    public function update(Request $request, StudentSelectionService $studentSelectionService): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string'],
            'checked' => ['required', 'boolean'],
        ]);

        $studentSelectionService->set(auth()->id(), $validated['id'], $validated['checked']);

        return response()->json(['selected' => $studentSelectionService->all(auth()->id())]);
    }

    public function updateMany(Request $request, StudentSelectionService $studentSelectionService): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['string'],
            'checked' => ['required', 'boolean'],
        ]);

        $studentSelectionService->setMany(auth()->id(), $validated['ids'], $validated['checked']);

        return response()->json(['selected' => $studentSelectionService->all(auth()->id())]);
    }

    public function clear(StudentSelectionService $studentSelectionService): JsonResponse
    {
        $studentSelectionService->clear(auth()->id());

        return response()->json(['selected' => []]);
    }
}
