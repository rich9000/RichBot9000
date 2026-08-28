<?php

namespace App\Http\Controllers;

use App\Models\PhoneTree;
use App\Models\PhoneTreeMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PhoneTreeMenuController extends Controller
{
    public function index(PhoneTree $phoneTree)
    {
        $menus = $phoneTree->menus()
            ->with(['parentMenu', 'childMenus', 'options'])
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $menus
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_tree_id' => 'required|exists:phone_trees,id',
            'parent_menu_id' => 'nullable|exists:phone_tree_menus,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prompt_message' => 'nullable|string',
            'timeout_message' => 'nullable|string',
            'invalid_input_message' => 'nullable|string',
            'max_retries' => 'required|integer|min:1',
            'timeout_seconds' => 'required|integer|min:1',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'welcome_audio_id' => 'nullable|exists:audio_files,id',
            'welcome_message' => 'nullable|string',
            'prompt_audio_id' => 'nullable|exists:audio_files,id',
            'finish_audio_id' => 'nullable|exists:audio_files,id',
            'finish_message' => 'nullable|string',
            'finish_menu_id' => 'nullable|exists:phone_tree_menus,id',
            'websocket_id' => 'nullable|exists:phone_tree_websockets,id',
            'disconnect_on_finish' => 'boolean',
            'transfer_number' => 'nullable|string|max:20',
            'websocket_fail_menu_id' => 'nullable|exists:phone_tree_menus,id',
            'script_id' => 'nullable|exists:phone_tree_scripts,id',
            'assistant_id' => 'nullable|exists:assistants,id',
            'pipeline_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $menu = PhoneTreeMenu::create($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $menu->load([
                'parentMenu',
                'childMenus',
                'options',
                'welcomeAudio',
                'promptAudio',
                'finishAudio',
                'finishMenu',
                'websocket',
                'websocketFailMenu',
                'script',
                'assistant'
            ])
        ]);
    }

    public function show(PhoneTree $phoneTree, PhoneTreeMenu $menu)
    {
        return response()->json([
            'success' => true,
            'data' => $menu->load([
                'parentMenu',
                'childMenus',
                'options',
                'welcomeAudio',
                'promptAudio',
                'finishAudio',
                'finishMenu',
                'websocket',
                'websocketFailMenu',
                'script',
                'assistant'
            ])
        ]);
    }

    public function update(Request $request,PhoneTree $phoneTree, PhoneTreeMenu $menu)
    {



       

        $validator = Validator::make($request->all(), [
            'parent_menu_id' => 'nullable|exists:phone_tree_menus,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prompt_message' => 'nullable|string',
            'timeout_message' => 'nullable|string',
            'invalid_input_message' => 'nullable|string',
            'max_retries' => 'required|integer|min:1',
            'timeout_seconds' => 'required|integer|min:1',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'welcome_audio_id' => 'nullable|exists:audio_files,id',
            'welcome_message' => 'nullable|string',
            'prompt_audio_id' => 'nullable|exists:audio_files,id',
            'finish_audio_id' => 'nullable|exists:audio_files,id',
            'finish_message' => 'nullable|string',
            'finish_menu_id' => 'nullable|exists:phone_tree_menus,id',
            'websocket_id' => 'nullable|exists:phone_tree_websockets,id',
            'disconnect_on_finish' => 'boolean',
            'transfer_number' => 'nullable|string|max:20',
            'websocket_fail_menu_id' => 'nullable|exists:phone_tree_menus,id',
            'script_id' => 'nullable|exists:phone_tree_scripts,id',
            'assistant_id' => 'nullable|exists:assistants,id',
            'pipeline_id' => 'nullable|exists:pipelines,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }


       

        $menu->update($validator->validated());



        return response()->json([
            'success' => true,
            'data' => $menu->load([
                'parentMenu',
                'childMenus',
                'options',
                'welcomeAudio',
                'promptAudio',
                'finishAudio',
                'finishMenu',
                'websocket',
                'websocketFailMenu',
                'script',
                'assistant'
            ])
        ]);
    }

    public function destroy(PhoneTree $phoneTree, PhoneTreeMenu $menu)
    {
        $menu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully'
        ]);
    }
} 