<?php

namespace App\Http\Controllers;

use App\Models\PhoneTree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhoneTreeController extends Controller
{
    public function index()
    {
        $phoneTrees = PhoneTree::with([
            'numbers', 
            'menus.options',
            'menus.options.target',
            'menus.options.welcomeAudio',
            'menus.options.finishMenu',
            'menus.options.targetMenu',
            'menus.options.targetAudio',
            'menus.options.targetScript',
            'menus.options.targetWebSocket',
            'menus.options.targetNumber',
          
            'websockets', 
            'calls.recordings', 
            'calls.transcriptions',
            'scripts'
        ])->get();

        return response()->json([
            'success' => true,
            'data' => $phoneTrees
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'welcome_message' => 'required|string',
            'timeout_message' => 'required|string',
            'invalid_input_message' => 'required|string',
            'max_retries' => 'required|integer|min:1',
            'timeout_seconds' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'root_menu_id' => 'nullable|exists:phone_tree_menus,id'
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $phoneTree = PhoneTree::create($validated);

        return response()->json([
            'success' => true,
            'data' => $phoneTree->load('rootMenu')
        ]);
    }

    public function show(PhoneTree $phoneTree)
    {
        return response()->json([
            'success' => true,
            'data' => $phoneTree->load([
                'numbers', 
                'menus.options.target',
                'menus.options.welcomeAudio',
                'menus.options.finishMenu',
                'menus.websocket',
                'menus.websocketFailMenu',
                'websockets', 
                'calls.recordings', 
                'calls.transcriptions',
                'scripts'
            ])
        ]);
    }

    public function update(Request $request, PhoneTree $phoneTree)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'welcome_message' => 'required|string',
            'welcome_audio_id' => 'nullable|exists:audio_files,id',            
            'timeout_message' => 'required|string',
            'invalid_input_message' => 'required|string',
            'max_retries' => 'required|integer|min:1',
            'timeout_seconds' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'root_menu_id' => 'nullable|exists:phone_tree_menus,id'
        ]);

        $validated['updated_by'] = auth()->id();

        $phoneTree->update($validated);

        return response()->json([
            'success' => true,
            'data' => $phoneTree->load('rootMenu')
        ]);
    }

    public function destroy(PhoneTree $phoneTree)
    {
        $phoneTree->delete();

        return response()->json([
            'success' => true,
            'message' => 'Phone tree deleted successfully'
        ]);
    }
} 