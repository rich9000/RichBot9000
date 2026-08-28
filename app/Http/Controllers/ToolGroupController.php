<?php

namespace App\Http\Controllers;

use App\Models\ToolGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToolGroupController extends Controller
{
    public function index()
    {
        $groups = ToolGroup::with(['user', 'tools'])
            ->orderBy('name')
            ->get();

        return response()->json($groups);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tool_ids' => 'nullable|array',
            'tool_ids.*' => 'exists:tools,id'
        ]);

        $group = ToolGroup::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'user_id' => $request->user()->id
        ]);

        if (isset($validated['tool_ids'])) {
            $group->tools()->sync($validated['tool_ids']);
        }

        return response()->json($group->load('tools'));
    }

    public function update(Request $request, ToolGroup $toolGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tool_ids' => 'nullable|array',
            'tool_ids.*' => 'exists:tools,id'
        ]);

        $toolGroup->update([
            'name' => $validated['name'],
            'description' => $validated['description']
        ]);

        if (isset($validated['tool_ids'])) {
            $toolGroup->tools()->sync($validated['tool_ids']);
        }

        return response()->json($toolGroup->load('tools'));
    }

    public function destroy(ToolGroup $toolGroup)
    {
        $toolGroup->delete();
        return response()->json(['message' => 'Tool group deleted successfully']);
    }
} 