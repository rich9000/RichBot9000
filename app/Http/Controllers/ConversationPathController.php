<?php

namespace App\Http\Controllers;

use App\Models\ConversationPath;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ConversationPathController extends Controller
{
    /**
     * Get all conversation paths for the authenticated user
     */
    public function index(): JsonResponse
    {
        $paths = ConversationPath::where('user_id', auth()->id())->get();
        return response()->json($paths);
    }

    /**
     * Get a specific conversation path
     */
    public function show(int $id): JsonResponse
    {
        $path = ConversationPath::where('user_id', auth()->id())
            ->findOrFail($id);
        return response()->json($path);
    }

    /**
     * Create a new conversation path
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nodes' => 'required|array'
        ]);

        $path = ConversationPath::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'nodes' => $validated['nodes'],
            'user_id' => auth()->id()
        ]);

        return response()->json($path, 201);
    }

    /**
     * Update a conversation path
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $path = ConversationPath::where('user_id', auth()->id())
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nodes' => 'required|array'
        ]);

        $path->update($validated);

        return response()->json($path);
    }

    /**
     * Delete a conversation path
     */
    public function destroy(int $id): JsonResponse
    {
        $path = ConversationPath::where('user_id', auth()->id())
            ->findOrFail($id);
            
        $path->delete();

        return response()->json(['message' => 'Conversation path deleted successfully']);
    }

    /**
     * Get the current step of a conversation
     */
    public function getCurrentStep(int $pathId, int $conversationId): JsonResponse
    {
        // Mock data for current step
        return response()->json([
            'step_id' => 1,
            'question' => 'What is your business type?',
            'type' => 'multiple_choice',
            'options' => ['Retail', 'Service', 'Manufacturing', 'Other'],
            'next_step' => 2
        ]);
    }

    /**
     * Submit an answer for the current step
     */
    public function submitAnswer(Request $request, int $pathId, int $conversationId): JsonResponse
    {
        // Mock response for submitting an answer
        return response()->json([
            'success' => true,
            'next_step' => 2,
            'message' => 'Answer recorded successfully'
        ]);
    }
} 