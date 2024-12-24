<?php
namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssistantController extends Controller
{
    public function index(Request $request)
    {

        $assistants = Assistant::with(['tools', 'model'])
            ->where('is_public', true)
            ->get();

        if ($assistants->isEmpty()) {
            // Log or handle cases where no public assistants exist
            Log::info('No public assistants found.');
        }

        $user = $request->user();

        if ($user->hasRole('Admin') || $user->hasRole('Editor')) {
            $additionalAssistants = Assistant::with(['tools', 'model'])->get();
            $assistants = $assistants->concat($additionalAssistants);
        } else if ($user->hasRole('User')) {
            $additionalAssistants = $user->assistants()->with(['tools', 'model'])->get();
            $assistants = $assistants->concat($additionalAssistants);
        }

        $assistants = $assistants->unique('id')->values();

        foreach ($assistants as $assistant) {

            $assistant->toolJson = $assistant->toolJson();
        }
        return response()->json(['assistants' => $assistants->toArray()]);

    }

    public function show($id)
    {
        $assistant = Assistant::with(['tools', 'model'])->findOrFail($id);
        $assistant->toolJson = $assistant->toolJson();



        return response()->json($assistant);
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'system_message' => 'required|string',
            'model_id' => 'required|exists:models,id',
            'success_tool_id' => 'nullable|exists:tools,id',
            'type' => 'nullable|string|max:50',
            'interactive' => 'nullable|boolean',
        ]);

        $assistant = Assistant::create([
            'name' => $validatedData['name'],
            'system_message' => $validatedData['system_message'],
            'user_id' => $request->user()->id,
            'model_id' => $validatedData['model_id'],
            'success_tool_id' => $validatedData['success_tool_id'] ?? null,
            'type' => $validatedData['type'] ?? '',
            'interactive' => $validatedData['interactive'] ?? false,
        ]);

        if ($request->has('tool_ids')) {
            $assistant->tools()->sync($request->input('tool_ids'));
        }

        return response()->json(['message' => 'Assistant created successfully', 'assistant' => $assistant->load('tools')], 201);
    }


    public function update(Request $request, $id)
    {
        $assistant = Assistant::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',

            'system_message' => 'sometimes|string',
            'model_id' => 'sometimes|exists:models,id',
            'success_tool_id' => 'nullable|exists:tools,id',
            'type' => 'nullable|string|max:50',
            'interactive' => 'nullable|boolean',
        ]);

        $assistant->update([
            'name' => $validatedData['name'] ?? $assistant->name,
            'system_message' => $validatedData['system_message'] ?? $assistant->system_message,
            'model_id' => $validatedData['model_id'] ?? $assistant->model_id,
            'success_tool_id' => $validatedData['success_tool_id'] ?? $assistant->success_tool_id,
            'type' => $validatedData['type'] ?? $assistant->type,
            'user_id' => $assistant->user_id ?? $request->user->id,
            'interactive' => $validatedData['interactive'] ?? $assistant->interactive,
        ]);

        if ($request->has('tool_ids')) {
            $assistant->tools()->sync($request->input('tool_ids'));
        }

        return response()->json(['message' => 'Assistant updated successfully', 'assistant' => $assistant->load('tools')]);
    }
    // Delete an assistant
    public function destroy($id)
    {
        $assistant = Assistant::findOrFail($id);
        $assistant->delete();

        return response()->json(['message' => 'Assistant deleted successfully']);
    }

    // Add tools to an assistant
    public function addTool(Request $request, $id)
    {
        $assistant = Assistant::findOrFail($id);

        $validatedData = $request->validate([
            'tool_ids' => 'required|array',
            'tool_ids.*' => 'exists:tools,id',
        ]);

      //  dd($validatedData);


        // Attach tools to the assistant (will not duplicate if already attached)
        $assistant->tools()->sync($validatedData['tool_ids']);

        return response()->json(['message' => 'Tools added successfully', 'assistant' => $assistant->load('tools')]);
    }

    public function updateTools(Request $request, $id)
    {
        $assistant = Assistant::findOrFail($id);

        $validatedData = $request->validate([
            'tool_ids' => 'required|array',
            'tool_ids.*' => 'exists:tools,id',
        ]);

        //  dd($validatedData);


        // Attach tools to the assistant (will not duplicate if already attached)
        $assistant->tools()->sync($validatedData['tool_ids']);

        return response()->json(['message' => 'Tools added successfully', 'assistant' => $assistant->load('tools')]);
    }
}
