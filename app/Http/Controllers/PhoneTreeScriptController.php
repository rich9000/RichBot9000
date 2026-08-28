<?php

namespace App\Http\Controllers;

use App\Models\PhoneTree;
use App\Models\PhoneTreeScript;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PhoneTreeScriptController extends Controller
{
    public function index(PhoneTree $phoneTree)
    {
        $scripts = $phoneTree->scripts()
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $scripts
        ]);
    }

    public function store(Request $request, PhoneTree $phoneTree)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'path' => 'required|string|max:255',
            'parameters' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $script = $phoneTree->scripts()->create([
            ...$validator->validated(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'data' => $script
        ], 201);
    }

    public function show(PhoneTree $phoneTree, PhoneTreeScript $script)
    {
        return response()->json([
            'success' => true,
            'data' => $script
        ]);
    }

    public function update(Request $request, PhoneTree $phoneTree, PhoneTreeScript $script)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'path' => 'sometimes|required|string|max:255',
            'parameters' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $script->update([
            ...$validator->validated(),
            'updated_by' => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'data' => $script
        ]);
    }

    public function destroy(PhoneTree $phoneTree, PhoneTreeScript $script)
    {
        $script->delete();

        return response()->json([
            'success' => true,
            'message' => 'Script deleted successfully'
        ]);
    }
} 