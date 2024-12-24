<?php


namespace App\Http\Controllers;

use App\Models\ScheduledCronbot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ScheduledCronbotController extends Controller
{
    /**
     * Display a listing of the scheduled cronbots.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all cronbots for the authenticated user
        $cronbots = ScheduledCronbot::where('user_id', $user->id)->get();

        return response()->json($cronbots, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string',
            'assistant_id' => 'required|exists:assistants,id',

            'is_repeating' => 'required|boolean',
            'repeat_interval' => 'nullable|string|in:hourly,twice_daily,daily,weekly,monthly',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'fail_tool_id' => 'nullable|exists:tools,id',
            'success_tool_id' => 'nullable|exists:tools,id',
            'pause_tool_id' => 'nullable|exists:tools,id',
        ]);

        $user = $request->user();

        // Generate the cron expression if repeating
        $schedule = null;
        if ($validated['is_repeating'] && $validated['repeat_interval']) {
            $schedule = $this->generateCronExpression($validated['repeat_interval'], $validated['start_time']);
        }

        // Create a new scheduled task
        $cronbot = ScheduledCronbot::create([
            'user_id' => $user->id,
            'assistant_id' => $validated['assistant_id'],
            'prompt' => $validated['prompt'],
            'is_repeating' => $validated['is_repeating'],
            'schedule' => $schedule,
            'next_run_at' => $validated['start_time'],
            'end_at' => $validated['end_time'] ?? null,
            'fail_tool_id' => $validated['fail_tool_id'] ?? null,
            'success_tool_id' => $validated['success_tool_id'] ?? null,
            'pause_tool_id' => $validated['pause_tool_id'] ?? null,
            'is_active' => true,
        ]);

        return response()->json($cronbot, 201);
    }


    /**
     * Display the specified scheduled cronbot.
     */
    public function show(ScheduledCronbot $scheduledCronbot)
    {
        return response()->json($scheduledCronbot, 200);
    }

    /**
     * Update the specified scheduled cronbot.
     */
    public function update(Request $request, ScheduledCronbot $scheduled_cronbot)
    {
        $validated = $request->validate([
            'prompt' => 'required|string',
            'assistant_id' => 'required|exists:assistants,id',
            'is_repeating' => 'required|boolean',
            'repeat_interval' => 'nullable|string|in:hourly,twice_daily,daily,weekly,monthly',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'fail_tool_id' => 'nullable|exists:tools,id',
            'success_tool_id' => 'nullable|exists:tools,id',
            'pause_tool_id' => 'nullable|exists:tools,id',
            'is_active' => 'nullable|boolean',
        ]);

        // Update next_run_at based on start_time
        $validated['next_run_at'] = $validated['start_time'];

        // If repeating and interval provided, generate a new cron expression
        if ($validated['is_repeating'] && isset($validated['repeat_interval'])) {
            $validated['schedule'] = $this->generateCronExpression(
                $validated['repeat_interval'], 
                $validated['start_time']
            );
        } else {
            $validated['schedule'] = null;
            $validated['repeat_interval'] = null;
        }

        $scheduled_cronbot->update($validated);

        return response()->json($scheduled_cronbot->fresh(), 200);
    }


    /**
     * Remove the specified scheduled cronbot.
     */
    public function destroy(ScheduledCronbot $scheduledCronbot)
    {
       // $this->authorize('delete', $scheduledCronbot);

        $scheduledCronbot->delete();

        return response()->json(['message' => 'Cronbot deleted successfully.'], 200);
    }

    /**
     * Manually trigger a cronbot for testing or execution.
     */
    public function trigger(Request $request, ScheduledCronbot $scheduledCronbot)
    {
       // $this->authorize('view', $scheduledCronbot);

        if (!$scheduledCronbot->is_active) {
            return response()->json(['message' => 'Cronbot is inactive.'], 400);
        }

        // Execute the cronbot logic (this is a placeholder)
        $result = $this->executeCronbot($scheduledCronbot);

        return response()->json([
            'message' => 'Cronbot triggered successfully.',
            'result' => $result,
        ], 200);
    }

    /**
     * Execute the logic for a scheduled cronbot.
     */
    protected function executeCronbot(ScheduledCronbot $cronbot)
    {
        // Placeholder for the actual assistant logic
        $assistantResponse = "Executed prompt: {$cronbot->prompt}";

        // Simulate checking success or failure
        $isSuccess = rand(0, 1) === 1;

        if ($isSuccess) {
            // Handle success_tool_id
            if ($cronbot->success_tool_id) {
                // Execute success tool logic here
            }
        } else {
            // Handle fail_tool_id
            if ($cronbot->fail_tool_id) {
                // Execute fail tool logic here
            }
        }

        // Optionally handle pause or self-destruction
        if (!$cronbot->is_repeating) {
            $cronbot->update(['is_active' => false]); // Disable single-run tasks after execution
        }

        return [
            'success' => $isSuccess,
            'response' => $assistantResponse,
        ];
    }

    protected function generateCronExpression($repeatInterval, $startTime)
    {
        $time = Carbon::parse($startTime); // Use Carbon to handle date parsing

        switch ($repeatInterval) {
            case 'hourly':
                return sprintf('%d * * * *', $time->minute);
            case 'twice_daily':
                return sprintf('%d 0,12 * * *', $time->minute);
            case 'daily':
                return sprintf('%d %d * * *', $time->minute, $time->hour);
            case 'weekly':
                return sprintf('%d %d * * %d', $time->minute, $time->hour, $time->dayOfWeek);
            case 'monthly':
                return sprintf('%d %d %d * *', $time->minute, $time->hour, $time->day);
            default:
                throw new InvalidArgumentException('Invalid repeat interval.');
        }
    }
}
