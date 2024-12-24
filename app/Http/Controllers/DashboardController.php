<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assistant;
use App\Models\ScheduledCronbot;
use App\Models\Tool;
use App\Models\Conversation;
use App\Models\EventLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getStats()
    {
        return response()->json([
            'activeAssistants' => Assistant::count(),
            'activeCronbots' => ScheduledCronbot::where('is_active', true)->count(),
            'availableTools' => Tool::count(),
            'totalChats' => Conversation::count(),
        ]);
    }

    public function getActivity()
    {
        $activities = EventLog::with(['user', 'loggable'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->log_type,
                    'description' => $activity->message,
                    'status' => $activity->status,
                    'created_at' => $activity->created_at,
                    'user' => $activity->user ? $activity->user->name : 'System'
                ];
            });

        return response()->json($activities);
    }

    public function getUpcomingCronbots()
    {
        $cronbots = ScheduledCronbot::with('assistant')
            ->where('next_run_at', '>', Carbon::now())
            ->where('is_active', true)
            ->orderBy('next_run_at', 'asc')
            ->take(5)
            ->get()
            ->map(function ($cronbot) {
                return [
                    'id' => $cronbot->id,
                    'assistant_name' => $cronbot->assistant->name,
                    'prompt' => $cronbot->prompt,
                    'next_run_at' => $cronbot->next_run_at,
                    'status' => $cronbot->status,
                    'repeat_interval' => $cronbot->repeat_interval
                ];
            });

        return response()->json($cronbots);
    }
} 