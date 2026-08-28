<?php

namespace App\Http\Controllers;

use App\Models\PhoneTree;
use App\Models\PhoneTreeCall;
use App\Models\PhoneTreeTranscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhoneTreeTranscriptionController extends Controller
{
    public function index(PhoneTree $phoneTree, PhoneTreeCall $call)
    {
        $transcriptions = $call->transcriptions()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transcriptions
        ]);
    }

    public function store(Request $request, PhoneTree $phoneTree, PhoneTreeCall $call)
    {
        $validated = $request->validate([
            'transcription_sid' => 'required|string',
            'transcription_text' => 'required|string',
            'language' => 'required|string',
            'confidence' => 'required|numeric|min:0|max:1',
            'status' => 'required|string',
            'metadata' => 'nullable|json'
        ]);

        $transcription = $call->transcriptions()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $transcription
        ], 201);
    }

    public function show(PhoneTree $phoneTree, PhoneTreeCall $call, PhoneTreeTranscription $transcription)
    {
        return response()->json([
            'success' => true,
            'data' => $transcription
        ]);
    }

    public function update(Request $request, PhoneTree $phoneTree, PhoneTreeCall $call, PhoneTreeTranscription $transcription)
    {
        $validated = $request->validate([
            'transcription_sid' => 'sometimes|required|string',
            'transcription_text' => 'sometimes|required|string',
            'language' => 'sometimes|required|string',
            'confidence' => 'sometimes|required|numeric|min:0|max:1',
            'status' => 'sometimes|required|string',
            'metadata' => 'nullable|json'
        ]);

        $transcription->update($validated);

        return response()->json([
            'success' => true,
            'data' => $transcription
        ]);
    }

    public function destroy(PhoneTree $phoneTree, PhoneTreeCall $call, PhoneTreeTranscription $transcription)
    {
        $transcription->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transcription deleted successfully'
        ]);
    }
} 