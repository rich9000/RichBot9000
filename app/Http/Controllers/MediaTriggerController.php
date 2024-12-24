<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MediaTrigger;
use App\Models\RemoteRichbot;

class MediaTriggerController extends Controller
{
    // Display a listing of the triggers for a given Richbot
    public function index($richbotId)
    {
        $richbot = RemoteRichbot::findOrFail($richbotId);
        $triggers = $richbot->mediaTriggers;

        return response()->json($triggers);
    }

    // Store a newly created trigger
    public function store(Request $request, $richbotId)
    {
        $richbot = RemoteRichbot::findOrFail($richbotId);

        $validated = $request->validate([
            'type' => 'required|string',
            'prompt' => 'required|string',
            'action' => 'required|string',
        ]);

        $trigger = $richbot->mediaTriggers()->create($validated);

        return response()->json($trigger, 201);
    }

    // Show a specific trigger
    public function show($richbotId, $triggerId)
    {
        $trigger = MediaTrigger::where('richbot_id', $richbotId)->findOrFail($triggerId);

        return response()->json($trigger);
    }

    // Update a trigger
    public function update(Request $request, $richbotId, $triggerId)
    {
        $trigger = MediaTrigger::where('richbot_id', $richbotId)->findOrFail($triggerId);

        $validated = $request->validate([
            'type' => 'required|string',
            'prompt' => 'required|string',
            'action' => 'required|string',
        ]);

        $trigger->update($validated);

        return response()->json($trigger);
    }

    // Delete a trigger
    public function destroy($richbotId, $triggerId)
    {
        $trigger = MediaTrigger::where('richbot_id', $richbotId)->findOrFail($triggerId);

        $trigger->delete();

        return response()->json(null, 204);
    }
}
