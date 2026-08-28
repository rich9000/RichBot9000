<?php


namespace App\Http\Controllers;

use App\Models\ScheduledCronbot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;


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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prompt' => 'required|string',
            'assistant_id' => 'required|exists:assistants,id',
            'tools' => 'nullable|array',
            'tools.*.id' => 'required|exists:tools,id',
            'tools.*.name' => 'required|string',
            'tools.*.parameters' => 'nullable|array',
            
            // Scheduling fields
            'is_repeating' => 'required|boolean',
            'is_active' => 'boolean',
            'schedule' => 'nullable|string',
            'next_run_at' => 'required|date',
            'end_at' => 'nullable|date|after:next_run_at',
            'scheduling_metadata' => 'nullable|array',
            
            // Legacy fields (keeping for backward compatibility)
            'fail_tool_id' => 'nullable|exists:tools,id',
            'success_tool_id' => 'nullable|exists:tools,id',
            'pause_tool_id' => 'nullable|exists:tools,id',
        ]);

        $user = $request->user();

        // Create a new scheduled task
        $cronbot = ScheduledCronbot::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'assistant_id' => $validated['assistant_id'],
            'prompt' => $validated['prompt'],
            'tools' => $validated['tools'] ?? [],
            'is_repeating' => $validated['is_repeating'],
            'schedule' => $validated['schedule'],
            'scheduling_metadata' => $validated['scheduling_metadata'] ?? null,
            'next_run_at' => $validated['next_run_at'],
            'end_at' => $validated['end_at'] ?? null,
            'fail_tool_id' => $validated['fail_tool_id'] ?? null,
            'success_tool_id' => $validated['success_tool_id'] ?? null,
            'pause_tool_id' => $validated['pause_tool_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
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
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'prompt' => 'sometimes|required|string',
            'assistant_id' => 'sometimes|required|exists:assistants,id',
            'tools' => 'nullable|array',
            'tools.*.id' => 'required|exists:tools,id',
            'tools.*.name' => 'required|string',
            'tools.*.parameters' => 'nullable|array',
            
            // Scheduling fields
            'is_repeating' => 'sometimes|required|boolean',
            'is_active' => 'boolean',
            'schedule' => 'nullable|string',
            'next_run_at' => 'sometimes|required|date',
            'end_at' => 'nullable|date|after:next_run_at',
            'scheduling_metadata' => 'nullable|array',
            
            // Legacy fields (keeping for backward compatibility)
            'fail_tool_id' => 'nullable|exists:tools,id',
            'success_tool_id' => 'nullable|exists:tools,id',
            'pause_tool_id' => 'nullable|exists:tools,id',
        ]);

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
        Log::info('Executing cronbot ID: ' . $cronbot->id);
        
        try {
            // Get the base path of the Laravel application
            $basePath = base_path();
            
            // Run the actual cronbot command with the specific ID using exec()
            $command = "cd {$basePath} && php artisan cronbots:run --run-id={$cronbot->id}";
            
            // Execute the command and capture output
            $output = [];
            $returnCode = 0;
            
            exec($command . " 2>&1", $output, $returnCode);
            
            $outputString = implode("\n", $output);
            Log::info('Cronbot command output: ' . $outputString);
            
            if ($returnCode === 0) {
                return [
                    'success' => true,
                    'response' => 'Cronbot executed successfully',
                    'output' => $outputString,
                    'command' => $command,
                    'return_code' => $returnCode
                ];
            } else {
                Log::error('Cronbot command failed with return code: ' . $returnCode);
                return [
                    'success' => false,
                    'response' => 'Cronbot execution failed',
                    'output' => $outputString,
                    'command' => $command,
                    'return_code' => $returnCode
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Error executing cronbot: ' . $e->getMessage());
            return [
                'success' => false,
                'response' => 'Error executing cronbot: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
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
