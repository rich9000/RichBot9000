<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIAssistant;
use Illuminate\Support\Facades\Log;

class ApiAssistantsController extends Controller
{
    protected $openAIAssistant;

    public function __construct(OpenAIAssistant $openAIAssistant)
    {
        $this->openAIAssistant = $openAIAssistant;
    }

    public function index()
    {
        try {
            $assistants = $this->openAIAssistant->list_assistants();
            return response()->json(['assistants' => $assistants]);
        } catch (\Exception $e) {
            Log::error("Error listing assistants: " . $e->getMessage());
            return response()->json(['error' => 'Failed to list assistants.'], 500);
        }
    }



    public function listAssistants()
    {
        try {
            $assistants = $this->openAIAssistant->list_assistants();
            return response()->json(['assistants' => $assistants]);
        } catch (\Exception $e) {
            Log::error("Error listing assistants: " . $e->getMessage());
            return response()->json(['error' => 'Failed to list assistants.'], 500);
        }
    }

    public function storeAssistant(Request $request)
    {
        $assistantName = $request->input('name');
        $model = $request->input('model');
        $description = $request->input('description');
        $instructions = $request->input('instructions');
        $selectedFunctions = $request->input('functions');
        $selectedFiles = $request->input('files') ?? [];
        $selectedOnlineFiles = $request->input('onlineFiles') ?? [];

        try {
            // Use OpenAIAssistant service to create the assistant
            $assistantId = $this->openAIAssistant->create_assistant(
                $assistantName,
                $instructions,
                $selectedFunctions,
                $selectedOnlineFiles
            );

            return response()->json(['success' => 'Assistant created with ID: ' . $assistantId]);
        } catch (\Exception $e) {
            Log::error("Error creating assistant: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create assistant.'], 500);
        }
    }

    public function deleteAssistant($id)
    {
        try {
            $this->openAIAssistant->delete_assistant($id);
            return response()->json(['success' => 'Assistant deleted successfully.']);
        } catch (\Exception $e) {
            Log::error("Error deleting assistant: " . $e->getMessage());
            return response()->json(['error' => 'Failed to delete assistant.'], 500);
        }
    }

    public function listFunctions()
    {
        try {
            $functions = AssistantFunction::all();
            return response()->json(['functions' => $functions]);
        } catch (\Exception $e) {
            Log::error("Error listing functions: " . $e->getMessage());
            return response()->json(['error' => 'Failed to list functions.'], 500);
        }
    }

    // Other methods related to assistant management...
}

