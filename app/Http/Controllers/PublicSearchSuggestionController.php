<?php

namespace App\Http\Controllers;

use App\Services\PublicSearchSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicSearchSuggestionController extends Controller
{
    public function __invoke(Request $request, PublicSearchSuggestionService $suggestions): JsonResponse
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:120']]);

        return response()->json([
            'suggestions' => $suggestions->suggestions((string) ($validated['q'] ?? ''), $request->user()?->id),
        ]);
    }
}
