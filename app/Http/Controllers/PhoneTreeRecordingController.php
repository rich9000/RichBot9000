<?php

namespace App\Http\Controllers;

use App\Models\PhoneTree;
use App\Models\PhoneTreeCall;
use App\Models\PhoneTreeRecording;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhoneTreeRecordingController extends Controller
{
    public function index(PhoneTree $phoneTree, PhoneTreeCall $call)
    {
        $recordings = $call->recordings()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $recordings
        ]);
    }

    public function store(Request $request, PhoneTree $phoneTree, PhoneTreeCall $call)
    {
        $validated = $request->validate([
            'recording_sid' => 'required|string',
            'recording_url' => 'required|url',
            'duration' => 'required|integer|min:0',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'status' => 'required|string',
            'metadata' => 'nullable|json'
        ]);

        $recording = $call->recordings()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $recording
        ], 201);
    }

    public function show(PhoneTree $phoneTree, PhoneTreeCall $call, PhoneTreeRecording $recording)
    {
        return response()->json([
            'success' => true,
            'data' => $recording
        ]);
    }

    public function update(Request $request, PhoneTree $phoneTree, PhoneTreeCall $call, PhoneTreeRecording $recording)
    {
        $validated = $request->validate([
            'recording_sid' => 'sometimes|required|string',
            'recording_url' => 'sometimes|required|url',
            'duration' => 'sometimes|required|integer|min:0',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date',
            'status' => 'sometimes|required|string',
            'metadata' => 'nullable|json'
        ]);

        $recording->update($validated);

        return response()->json([
            'success' => true,
            'data' => $recording
        ]);
    }

    public function destroy(PhoneTree $phoneTree, PhoneTreeCall $call, PhoneTreeRecording $recording)
    {
        $recording->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recording deleted successfully'
        ]);
    }
} 