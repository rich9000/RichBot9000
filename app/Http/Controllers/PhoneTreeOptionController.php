<?php

namespace App\Http\Controllers;

use App\Models\PhoneTree;
use App\Models\PhoneTreeMenu;
use App\Models\PhoneTreeOption;
use App\Models\AudioFile;
use App\Models\PhoneTreeScript;
use App\Models\PhoneTreeWebsocket;
use App\Models\PhoneTreeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class PhoneTreeOptionController extends Controller
{
    public function index(PhoneTree $phoneTree, PhoneTreeMenu $menu)
    {
        $options = $menu->options()
            ->with(['target', 'welcomeAudio', 'finishMenu'])
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $options
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'phone_tree_menu_id' => 'required|exists:phone_tree_menus,id',
            'digit' => 'required|string|max:1',
            'action_type' => 'required|in:menu,audio_file,script,websocket,number,assistant',
            'target_id' => 'required|integer',
            'description' => 'nullable|string',
            'order' => 'required|integer',
            'is_active' => 'boolean',
            'welcome_message' => 'nullable|string',
            'welcome_audio_id' => 'nullable|exists:audio_files,id',
            'finish_menu_id' => 'nullable|exists:phone_tree_menus,id',
            'assistant_id' => 'nullable|exists:assistants,id',
            'pipeline_id' => 'nullable|string'
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $option = PhoneTreeOption::create($validated);

        return response()->json([
            'success' => true,
            'data' => $option->load(['target', 'welcomeAudio', 'finishMenu', 'assistant'])
        ]);
    }

    public function show(PhoneTree $phoneTree, PhoneTreeMenu $menu, PhoneTreeOption $option)
    {
        return response()->json([
            'success' => true,
            'data' => $option->load(['target', 'welcomeAudio', 'finishMenu', 'assistant'])
        ]);
    }

    public function update(Request $request, PhoneTree $phoneTree, PhoneTreeMenu $menu, PhoneTreeOption $option)
    {
        Log::info("PhoneTreeOptionController: update: option", [
            'option' => $option
        ]);
        
        $validated = $request->validate([
            'digit' => 'required|string|max:1',
            'description' => 'required|string|max:255',
            'action_type' => 'required|in:menu,audio_file,script,websocket,number,assistant',
            'target_id' => 'required|integer',
            'order' => 'required|integer',
            'is_active' => 'required|boolean',
            'welcome_message' => 'nullable|string',
            'welcome_audio_id' => 'nullable|exists:audio_files,id',
            'finish_menu_id' => 'nullable|exists:phone_tree_menus,id',
            'assistant_id' => 'nullable|exists:assistants,id',
            'pipeline_id' => 'nullable|string'
        ]);

        $option->update($validated + [
            'updated_by' => $request->user()->id
        ]);

        Log::info("PhoneTreeOptionController: update: option", [
            'option' => $option
        ]);

        return response()->json($option->load(['target', 'welcomeAudio', 'finishMenu', 'assistant']));
    }

    public function destroy(PhoneTree $phoneTree, PhoneTreeMenu $menu, PhoneTreeOption $option)
    {
        $option->delete();

        return response()->json([
            'success' => true,
            'message' => 'Option deleted successfully'
        ]);
    }
} 