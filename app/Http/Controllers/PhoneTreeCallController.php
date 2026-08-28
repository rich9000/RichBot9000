<?php

namespace App\Http\Controllers;

use App\Models\PhoneTree;
use App\Models\PhoneTreeCall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhoneTreeCallController extends Controller
{
    public function index(PhoneTree $phoneTree)
    {
        $calls = $phoneTree->calls()
            ->with(['currentMenu', 'recordings', 'transcriptions'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $calls
        ]);
    }

    public function show(PhoneTree $phoneTree, PhoneTreeCall $call)
    {
        return response()->json([
            'success' => true,
            'data' => $call->load(['currentMenu', 'recordings', 'transcriptions'])
        ]);
    }

    public function update(Request $request, PhoneTree $phoneTree, PhoneTreeCall $call)
    {
        $validated = $request->validate([
            'status' => 'sometimes|required|string',
            'current_menu_id' => 'nullable|exists:phone_tree_menus,id',
            'last_input' => 'nullable|string',
            'metadata' => 'nullable|json'
        ]);

        $call->update($validated);

        return response()->json([
            'success' => true,
            'data' => $call->load(['currentMenu', 'recordings', 'transcriptions'])
        ]);
    }

    public function destroy(PhoneTree $phoneTree, PhoneTreeCall $call)
    {
        $call->delete();

        return response()->json([
            'success' => true,
            'message' => 'Call deleted successfully'
        ]);
    }
} 