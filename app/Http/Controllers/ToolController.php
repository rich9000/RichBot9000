<?php

namespace App\Http\Controllers;

use App\Models\Tool;
use App\Models\Parameter;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    // Fetch all tools with their parameters
    public function index(Request $request)
    {
        $tools = Tool::with('parameters')->get();
        return response()->json($tools);
    }

    // Fetch a specific tool by ID
    public function show($id)
    {
        $tool = Tool::with('parameters')->findOrFail($id);
        return response()->json($tool);
    }

    // Create a new tool with parameters
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'strict' => 'required|boolean',
            'parameters' => 'required|array',
            'parameters.*.name' => 'required|string|max:255',
            'parameters.*.type' => 'required|string|max:255',
            'parameters.*.description' => 'nullable|string',
            'parameters.*.required' => 'required|boolean',
        ]);

        $tool = Tool::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'strict' => $validatedData['strict'],
        ]);

        foreach ($validatedData['parameters'] as $paramData) {
            $tool->parameters()->create([
                'name' => $paramData['name'],
                'type' => $paramData['type'],
                'description' => $paramData['description'] ?? null,
                'required' => $paramData['required'],
            ]);
        }

        return response()->json(['message' => 'Tool created successfully', 'tool' => $tool->load('parameters')], 201);
    }

    // Update an existing tool and its parameters
    public function update(Request $request, $id)
    {
        $tool = Tool::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'strict' => 'sometimes|boolean',
            'parameters' => 'sometimes|array',
            'parameters.*.id' => 'nullable|exists:parameters,id',
            'parameters.*.name' => 'required|string|max:255',
            'parameters.*.type' => 'required|string|max:255',
            'parameters.*.description' => 'nullable|string',
            'parameters.*.required' => 'required|boolean',
        ]);

        $tool->update($validatedData);

        if (isset($validatedData['parameters'])) {

            $tool->parameters()->delete();

            foreach ($validatedData['parameters'] as $paramData) {
                if (isset($paramData['id'])) {
                    // Update existing parameter
                    $parameter = Parameter::findOrFail($paramData['id']);
                    $parameter->update($paramData);
                } else {
                    // Check for existing parameter
                    $existingParameter = $tool->parameters()->where('name', $paramData['name'])->first();
                    if (!$existingParameter) {
                        // Create a new parameter if it doesn't exist
                        $tool->parameters()->create($paramData);
                    } else {
                        // Handle the case when a duplicate is found (e.g., skip or log)
                        // Example: continue; // skip duplicates
                    }
                }
            }
        }

        return response()->json(['message' => 'Tool updated successfully', 'tool' => $tool->load('parameters')]);
    }

    // Delete a tool and its parameters
    public function destroy($id)
    {
        $tool = Tool::findOrFail($id);
        $tool->delete();

        return response()->json(['message' => 'Tool deleted successfully']);
    }

    // Remove a specific parameter from a tool
    public function deleteParameter($toolId, $paramId)
    {
        $parameter = Parameter::where('tool_id', $toolId)->findOrFail($paramId);
        $parameter->delete();

        return response()->json(['message' => 'Parameter deleted successfully']);
    }
}
