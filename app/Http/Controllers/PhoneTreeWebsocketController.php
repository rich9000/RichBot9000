<?php

namespace App\Http\Controllers;

use App\Models\PhoneTree;
use App\Models\PhoneTreeWebsocket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhoneTreeWebsocketController extends Controller
{
    public function index(PhoneTree $phoneTree)
    {
        $websockets = $phoneTree->websockets()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $websockets
        ]);
    }

    public function store(Request $request, PhoneTree $phoneTree)
    {
        $validated = $request->validate([
            'endpoint_url' => 'required|url',
            'connection_type' => 'required|string|in:public,private',
            'authentication_type' => 'required|string|in:none,basic,token',
            'authentication_credentials' => 'required_if:authentication_type,basic,token|nullable|json',
            'is_active' => 'boolean'
        ]);

        $websocket = $phoneTree->websockets()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $websocket
        ], 201);
    }

    public function show(PhoneTree $phoneTree, PhoneTreeWebsocket $websocket)
    {
        return response()->json([
            'success' => true,
            'data' => $websocket
        ]);
    }

    public function update(Request $request, PhoneTree $phoneTree, PhoneTreeWebsocket $websocket)
    {
        $validated = $request->validate([
            'endpoint_url' => 'sometimes|required|url',
            'connection_type' => 'sometimes|required|string|in:public,private',
            'authentication_type' => 'sometimes|required|string|in:none,basic,token',
            'authentication_credentials' => 'required_if:authentication_type,basic,token|nullable|json',
            'is_active' => 'boolean'
        ]);

        $websocket->update($validated);

        return response()->json([
            'success' => true,
            'data' => $websocket
        ]);
    }

    public function destroy(PhoneTree $phoneTree, PhoneTreeWebsocket $websocket)
    {
        $websocket->delete();

        return response()->json([
            'success' => true,
            'message' => 'WebSocket connection deleted successfully'
        ]);
    }
} 